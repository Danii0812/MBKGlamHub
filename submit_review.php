<?php
session_start();
require 'db.php'; 

function respondError($msg, $code = 400) {
    http_response_code($code);
    echo $msg;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondError('Invalid request method', 405);
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) respondError('Not logged in', 401);

$booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$mua_rating = isset($_POST['mua_rating']) ? (int)$_POST['mua_rating'] : 0;
$hair_rating = isset($_POST['hair_rating']) ? (int)$_POST['hair_rating'] : 0;
$overall_rating = isset($_POST['overall_rating']) ? (int)$_POST['overall_rating'] : 0;
$mua_comment = trim($_POST['mua_comment'] ?? '');
$hair_comment = trim($_POST['hair_comment'] ?? '');

$comment = trim($mua_comment . "\n\n" . $hair_comment);

// overall_rating
if ($overall_rating >= 1 && $overall_rating <= 5) {
    $rating = $overall_rating;
} else {
    $sum = 0; $count = 0;
    if ($mua_rating >= 1 && $mua_rating <=5) { $sum += $mua_rating; $count++; }
    if ($hair_rating >= 1 && $hair_rating <=5) { $sum += $hair_rating; $count++; }
    $rating = $count ? (int)round($sum / $count) : 0;
}

if (!$booking_id || !$rating || $comment === '') {
    respondError('Missing required fields');
}

// prevent duplicate review for same booking
$chk = $conn->prepare("SELECT review_id FROM reviews WHERE booking_id = ?");
$chk->bind_param('i', $booking_id);
$chk->execute();
$chkRes = $chk->get_result();
if ($chkRes->num_rows > 0) respondError('Review already submitted for this booking', 409);

// classify sentiment (uses training_data table if available; falls back to lexicon)
$sentiment = classifySentiment($comment, $conn);

// insert review with is_verified = 0
$ins = $conn->prepare("INSERT INTO reviews (booking_id, user_id, rating, comment, sentiment, is_verified) VALUES (?, ?, ?, ?, ?, 0)");
$ins->bind_param('iiiss', $booking_id, $user_id, $rating, $comment, $sentiment);
if (!$ins->execute()) {
    respondError('DB insert failed: ' . $conn->error, 500);
}

echo 'success';
exit();


function classifySentiment($text, $conn) {
    $tokens = tokenize($text);
    if (empty($tokens)) return 'neutral';

    // total words per class
    $q = "SELECT 
            COALESCE(SUM(positive_count),0) AS pos_sum, 
            COALESCE(SUM(neutral_count),0) AS neu_sum, 
            COALESCE(SUM(negative_count),0) AS neg_sum, 
            COUNT(*) AS vocab_count
          FROM training_data";
    $res = $conn->query($q);
    if (!$res) return lexiconFallback($tokens); // fallback
    $row = $res->fetch_assoc();
    $pos_total = (int)$row['pos_sum'];
    $neu_total = (int)$row['neu_sum'];
    $neg_total = (int)$row['neg_sum'];
    $V = (int)$row['vocab_count'];

    // If no training data, fallback to lexicon
    if ($V === 0 || ($pos_total + $neu_total + $neg_total) === 0) {
        return lexiconFallback($tokens);
    }

    $q2 = "SELECT 
              COUNT(*) AS total_reviews,
              SUM(sentiment = 'positive') AS pos_reviews,
              SUM(sentiment = 'neutral') AS neu_reviews,
              SUM(sentiment = 'negative') AS neg_reviews
           FROM reviews";
    $r2 = $conn->query($q2);
    $priors = $r2 ? $r2->fetch_assoc() : null;
    $total_reviews = $priors ? (int)$priors['total_reviews'] : 0;
    $pos_reviews = $priors ? (int)$priors['pos_reviews'] : 0;
    $neu_reviews = $priors ? (int)$priors['neu_reviews'] : 0;
    $neg_reviews = $priors ? (int)$priors['neg_reviews'] : 0;

    // smoothing for priors
    $prior_pos = ($pos_reviews + 1) / ($total_reviews + 3);
    $prior_neu = ($neu_reviews + 1) / ($total_reviews + 3);
    $prior_neg = ($neg_reviews + 1) / ($total_reviews + 3);

    $logPos = log($prior_pos);
    $logNeu = log($prior_neu);
    $logNeg = log($prior_neg);

    // Prepare statement to fetch word counts
    $stmt = $conn->prepare("SELECT positive_count, neutral_count, negative_count FROM training_data WHERE word = ?");
    if (!$stmt) return lexiconFallback($tokens);

    // Count tokens frequency
    $wordCounts = [];
    foreach ($tokens as $w) $wordCounts[$w] = ($wordCounts[$w] ?? 0) + 1;

    $unknown_count = 0;
    $total_tokens = array_sum($wordCounts);

    foreach ($wordCounts as $word => $count) {
        $stmt->bind_param('s', $word);
        $stmt->execute();
        $resw = $stmt->get_result();

        if ($resw && $resw->num_rows > 0) {
            $wr = $resw->fetch_assoc();
            $pc = (int)$wr['positive_count'];
            $nc = (int)$wr['neutral_count'];
            $ngc = (int)$wr['negative_count'];
        } else {
            $pc = $nc = $ngc = 0;
            $unknown_count += $count; // count words not in training data
        }

        $pw_pos = ($pc + 1) / ($pos_total + $V);
        $pw_neu = ($nc + 1) / ($neu_total + $V);
        $pw_neg = ($ngc + 1) / ($neg_total + $V);

        $logPos += $count * log($pw_pos);
        $logNeu += $count * log($pw_neu);
        $logNeg += $count * log($pw_neg);
    }

    if ($total_tokens > 0 && ($unknown_count / $total_tokens) >= 0.5) { // Adjust threshold as needed. This means 50% or more unknown words result in neutral
    return 'neutral';
    }

    $maxLog = max($logPos, $logNeu, $logNeg);
    $expPos = exp($logPos - $maxLog);
    $expNeu = exp($logNeu - $maxLog);
    $expNeg = exp($logNeg - $maxLog);
    $sumExp = $expPos + $expNeu + $expNeg;

    $pPos = $expPos / $sumExp;
    $pNeu = $expNeu / $sumExp;
    $pNeg = $expNeg / $sumExp;

    $threshold = 0.60; // require >= 60% to accept positive/negative
    $best = max($pPos, $pNeu, $pNeg);
    if ($best < $threshold) return 'neutral';

    if ($pPos >= $pNeg && $pPos >= $pNeu) return 'positive';
    if ($pNeg >= $pPos && $pNeg >= $pNeu) return 'negative';
    return 'neutral';
}

function tokenize($text) {
    $text = mb_strtolower(trim(strip_tags($text)), 'UTF-8');
    // remove punctuation (keep unicode letters & digits)
    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
    $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    if (!$tokens) return [];

    // Basic stopwords
    $stopwords = [
        'the','and','a','an','is','it','was','were','to','for','of',
        'my','i','me','we','you','they','their','in','on','at','with',
        'as','that','this','be','by','from','has','have','had','but','or','not'
    ];

    $out = [];
    foreach ($tokens as $t) {
        $t = trim($t);
        if ($t === '') continue;
        if (mb_strlen($t, 'UTF-8') < 2) continue;
        if (ctype_digit($t)) continue;
        if (in_array($t, $stopwords)) continue;
        $out[] = $t;
    }
    return $out;
}

function lexiconFallback($tokens) {
    // small built-in word lists for initial fallback
    $positiveWords = ['great','amazing','beautiful','professional','happy','excellent','love','nice','friendly','fast','on-time','onetime','prompt','skilled'];
    $negativeWords = ['bad','terrible','late','unprofessional','disappointed','poor','ugly','rude','slow','late','didnt','didn\'t','not'];

    $score = 0;
    foreach ($tokens as $t) {
        if (in_array($t, $positiveWords)) $score++;
        if (in_array($t, $negativeWords)) $score--;
    }
    if ($score >= 2) return 'positive';
    if ($score <= -2) return 'negative';
    return 'neutral';
}
