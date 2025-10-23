<?php
session_start();

$host = 'localhost';
$db   = 'mbk_db';
$user = 'root';  
$pass = '';       
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$isLoggedIn = isset($_SESSION['user_id']);
$greetingName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews - MBK GlamHub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        lavender: {
                            50: '#fafaff',
                            100: '#f5f5fa',
                            200: '#ececf7',
                            300: '#e6e6fa',
                            400: '#d8d1e8',
                            500: '#c2b6d9',
                            600: '#a79dbf',
                            700: '#8e83a3',
                            800: '#756a86',
                            900: '#5d516c'
                        },
                        plum: {
                            50: '#f9f2f7',
                            100: '#f1e3ef',
                            200: '#e0c5dc',
                            300: '#c89ac1',
                            400: '#a06c9e',
                            500: '#804f7e',
                            600: '#673f68',
                            700: '#4b2840',
                            800: '#3c1f33',
                            900: '#2c1726'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-text {
            background: linear-gradient(to right, #804f7e, #673f68);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .gradient-bg {
        background: linear-gradient(to right, #a06c9e, #4b2840);
        }
        .gradient-bg:hover {
        background: linear-gradient(to right, #804f7e, #673f68);
        }
        .backdrop-blur {
            backdrop-filter: blur(12px);
        }
        html {
            scroll-behavior: smooth;
        }
        .star-rating {
            display: flex;
            gap: 2px;
        }
        .star {
            color: #fbbf24;
            font-size: 1.2rem;
        }
        .review-card {
            transition: all 0.3s ease;
        }
        .review-card:hover {
            transform: translateY(-5px);
        }
        .filter-btn.active {
            background: linear-gradient(to right, #804f7e, #673f68);
            color: white;
        }
    </style>
</head>
<body>
<header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-lavender-200">
  <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <a href="homepage.php"><img src="mbk_logo.png" alt="Make up By Kyleen Logo" class="h-14 w-auto"></a>
      </div>
      <nav class="hidden md:flex items-center space-x-8">
        <a href="homepage.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Services</a>
        <a href="#about" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">About</a>
        <a href="artist_portfolio.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Portfolio</a>
        <a href="reviews.php" class="text-plum-600 font-medium">Reviews</a>
        <div class="relative group inline-block text-left">
            <span class="gradient-bg text-white px-6 py-2 rounded-md font-medium transition-all inline-block cursor-pointer">
                Hello, <?= htmlspecialchars($greetingName); ?>
                <i class="fas fa-chevron-down text-white text-sm ml-1"></i>
            </span>
            <div class="absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-md shadow-lg opacity-0 group-hover:opacity-100 invisible group-hover:visible transition-all z-50">
                <?php if ($isLoggedIn): ?>
                    <a href="appointments.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">My Appointments</a>
                    <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Sign Out</a>
                <?php else: ?>
                    <a href="login.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Log In</a>
                <?php endif; ?>
            </div>
        </div>
      </nav>
    </div>
  </div>
</header>



<!-- Reviews Intro Section -->
<section class="py-32 bg-white">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-5xl lg:text-6xl font-bold mb-8 gradient-text tracking-wide leading-tight">
            What Our Clients Say
        </h1>
        <p class="text-gray-700 max-w-3xl mx-auto text-lg lg:text-xl leading-relaxed">
            At MBK GlamHub, we value every client experience. Here you can read <span class="font-semibold text-plum-600">verified reviews</span> from clients who trusted us for their special moments.
            Honest feedback helps us continue improving our services and ensures that every client feels confident in choosing us.
        </p>
    </div>
</section>


<!-- Tailwind custom animation -->
<style>
    @keyframes fadeIn {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fadeIn 1s ease-out forwards;
    }
</style>


<!-- Reviews Section -->
<section class="py-20">
    <div class="container mx-auto px-4">
        <div class="flex justify-center mb-8">
    <div class="flex items-center gap-2">
        <span class="text-gray-700 font-medium">Filter by rating:</span>
        <div id="star-filter" class="flex items-center gap-1 cursor-pointer">
            <?php
            $selectedRating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
            for ($i = 1; $i <= 5; $i++):
                $isSelected = $i <= $selectedRating;
            ?>
                <i class="fa fa-star text-2xl <?= $isSelected ? 'text-yellow-400' : 'text-gray-300' ?>" data-value="<?= $i ?>"></i>
            <?php endfor; ?>
        </div>
        <button 
            id="show-all-btn"
            class="ml-4 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium px-3 py-1 rounded transition">
            Show All Reviews
        </button>
    </div>
</div>

        <div class="grid lg:grid-cols-2 gap-8" id="reviews-container">
            <?php
            // Fetch first 4 verified reviews
$ratingFilter = isset($_GET['rating']) && is_numeric($_GET['rating']) ? intval($_GET['rating']) : null;

$sql = "
    SELECT r.*, u.first_name, u.last_name 
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.is_verified = 1
";

if ($ratingFilter) {
    $sql .= " AND r.rating = :rating";
}

$sql .= " ORDER BY r.created_at DESC LIMIT 4";

$stmt = $pdo->prepare($sql);
if ($ratingFilter) {
    $stmt->bindParam(':rating', $ratingFilter, PDO::PARAM_INT);
}
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt->execute();
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($reviews as $review):
                $sentimentColors = [
                    'positive' => 'bg-green-100 text-green-700',
                    'neutral' => 'bg-yellow-100 text-yellow-700',
                    'negative' => 'bg-red-100 text-red-700'
                ];
                $tagClass = $sentimentColors[$review['sentiment']] ?? 'bg-gray-100 text-gray-700';

                // Generate initials
                $firstInitial = strtoupper(substr($review['first_name'], 0, 1));
                $lastInitial = strtoupper(substr($review['last_name'], 0, 1));
                $initials = $firstInitial . $lastInitial;
            ?>
            <div class="review-card bg-white rounded-2xl p-8 shadow-lg border border-lavender-200 hover:border-plum-300">
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 flex items-center justify-center rounded-full bg-gradient-to-r from-lavender-400 to-plum-500 text-white font-semibold mr-4">
                            <?= $initials; ?>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h3>
                            <p class="text-sm text-gray-600"><?= date('M d, Y', strtotime($review['created_at'])); ?></p>
                        </div>
                    </div>
                    <div class="star-rating">
                        <?php
                        $rating = (int)$review['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $rating
                                ? '<i class="fas fa-star star"></i>'
                                : '<i class="far fa-star star"></i>';
                        }
                        ?>
                    </div>
                </div>
                <div class="mb-4">
                    <span class="inline-block <?= $tagClass ?> px-3 py-1 rounded-full text-sm font-medium">
                        <?= ucfirst($review['sentiment']); ?>
                    </span>
                </div>
                <p class="text-gray-700 leading-relaxed mb-6"><?= htmlspecialchars($review['comment']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Load More Button -->
        <div class="text-center mt-12">
            <button id="load-more" class="border-2 border-lavender-300 text-plum-700 hover:bg-lavender-50 px-8 py-4 text-lg rounded-lg font-medium transition-all bg-transparent">
                <i class="fas fa-plus mr-2"></i> Load More Reviews
            </button>
        </div>
    </div>
</section>




      <!-- Footer -->
      <footer class="bg-gray-900 text-white py-16">
          <div class="container mx-auto px-4">
              <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                  <div class="space-y-4">
              <div class="flex items-center space-x-2">
                <img src="mbk_white.png" alt="Make up By Kyleen Logo" class="h-10 w-auto">
              </div>

                      <p class="text-gray-400">
                          Elevating beauty through professional makeup artistry and hair styling services.
                      </p>
                      <div class="flex space-x-4">
                          <a href="https://www.instagram.com/makeupby_kyleen/" class="text-gray-400 hover:text-lavender-400 transition-colors">
                              <i class="fab fa-instagram text-xl"></i>
                          </a>
                          <a href="https://www.facebook.com/bianca.mendoza2" class="text-gray-400 hover:text-lavender-400 transition-colors">
                              <i class="fab fa-facebook text-xl"></i>
                          </a>
                          <a href="https://mail.google.com/mail/u/0/#inbox?compose=CllgCJfmrJhzqpXPCkWVMPcGRqJXnXJzgxqrpcHbXwdSJpRglHbHvnmpVqspJhQnRtMmsDztXlq" class="text-gray-400 hover:text-lavender-400 transition-colors">
                              <i class="fas fa-envelope"></i>
                          </a>
                      </div>
                  </div>

                  <div>
                      <h3 class="text-lg font-semibold mb-4">Services</h3>
                      <ul class="space-y-2 text-gray-400">
                          <li><a href="#" class="hover:text-lavender-400 transition-colors">Bridal Makeup</a></li>
                          <li><a href="#" class="hover:text-lavender-400 transition-colors">Event Makeup</a></li>
                          <li><a href="#" class="hover:text-lavender-400 transition-colors">Graduation</a></li>
                      </ul>
                  </div>

                  <div>
                      <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                      <ul class="space-y-2 text-gray-400">
                          <li><a href="#about" class="hover:text-lavender-400 transition-colors">About</a></li>
                          <li><a href="#portfolio" class="hover:text-lavender-400 transition-colors">Portfolio</a></li>
                          <li><a href="#testimonials" class="hover:text-lavender-400 transition-colors">Reviews</a></li>
                          <li><a href="#" class="hover:text-lavender-400 transition-colors">Contact</a></li>
                      </ul>
                  </div>

                  <div>
                      <h3 class="text-lg font-semibold mb-4">Contact Info</h3>
                      <div class="space-y-3 text-gray-400">
                          <div class="flex items-center">
                              <i class="fas fa-phone text-lavender-400 mr-3"></i>
                              <span>09777612938</span>
                          </div>
                          <div class="flex items-center">
                              <i class="fas fa-map-marker-alt text-lavender-400 mr-3"></i>
                              <span>123 Beauty Ave, City, ST 12345</span>
                          </div>
                      </div>
                  </div>
              </div>

              <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
                  <p>&copy; 2025 Make up By Kyleen. All rights reserved.</p>
              </div>
          </div>
      </footer>


<script>
let offset = 4;
const loadMoreBtn = document.getElementById('load-more');
loadMoreBtn.addEventListener('click', () => {
    fetch(`load_reviews.php?offset=${offset}`)
        .then(res => res.text())
        .then(html => {
            if(html.trim() === '') {
                loadMoreBtn.disabled = true;
                loadMoreBtn.innerText = 'No more reviews';
                return;
            }
            document.getElementById('reviews-container').insertAdjacentHTML('beforeend', html);
            offset += 4;
        });
});
</script>
<script>
document.querySelectorAll('#star-filter .fa-star').forEach(star => {
    star.addEventListener('click', () => {
        const selectedValue = star.getAttribute('data-value');
        const currentUrl = new URL(window.location.href);

        // If same rating is clicked again → clear filter
        if (currentUrl.searchParams.get('rating') == selectedValue) {
            currentUrl.searchParams.delete('rating');
        } else {
            currentUrl.searchParams.set('rating', selectedValue);
        }

        window.location.href = currentUrl.toString();
    });
});

document.getElementById('show-all-btn').addEventListener('click', () => {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.delete('rating');
    window.location.href = currentUrl.toString();
});
</script>

</html>