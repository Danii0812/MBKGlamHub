<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_POST['review_id'], $_POST['sentiment'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit;
}

$review_id = (int)$_POST['review_id'];
$sentiment = $_POST['sentiment'];

try {
    // 1. Fetch the review
    $stmt = $pdo->prepare("SELECT comment FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        throw new Exception("Review not found.");
    }

    $comment = $review['comment'];

    // 2. Tokenize the comment
    $words = preg_split('/\s+/', strtolower($comment));

    foreach ($words as $word) {
        $word = preg_replace('/[^a-z0-9]/i', '', $word); // remove punctuation
        if (!$word) continue;

        // Check if word exists
        $checkStmt = $pdo->prepare("SELECT * FROM training_data WHERE word = ?");
        $checkStmt->execute([$word]);
        $row = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Word exists, increment counts
            $col = $sentiment . '_count';
            $updateStmt = $pdo->prepare("UPDATE training_data SET $col = $col + 1, total_count = total_count + 1 WHERE word = ?");
            $updateStmt->execute([$word]);
        } else {
            // Insert new word
            $positive = $sentiment === 'positive' ? 1 : 0;
            $neutral = $sentiment === 'neutral' ? 1 : 0;
            $negative = $sentiment === 'negative' ? 1 : 0;

            $insertStmt = $pdo->prepare("
                INSERT INTO training_data (word, positive_count, neutral_count, negative_count, total_count)
                VALUES (?, ?, ?, ?, 1)
            ");
            $insertStmt->execute([$word, $positive, $neutral, $negative]);
        }
    }

    // 3. Mark the review as verified and save sentiment
    $verifyStmt = $pdo->prepare("UPDATE reviews SET is_verified = 1, sentiment = ? WHERE review_id = ?");
    $verifyStmt->execute([$sentiment, $review_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
