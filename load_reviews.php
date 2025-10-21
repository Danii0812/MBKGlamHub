<?php
session_start();
$host = 'localhost';
$db   = 'mbk_db';
$user = 'root';  
$pass = '';       
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$pdo = new PDO($dsn, $user, $pass, $options);

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$stmt = $pdo->prepare("
    SELECT r.*, u.first_name, u.last_name 
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.is_verified = 1
    ORDER BY r.created_at DESC
    LIMIT 4 OFFSET :offset
");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reviews = $stmt->fetchAll();

$sentimentColors = [
    'positive' => 'bg-green-100 text-green-700',
    'neutral' => 'bg-yellow-100 text-yellow-700',
    'negative' => 'bg-red-100 text-red-700'
];

foreach ($reviews as $review):
    $tagClass = $sentimentColors[$review['sentiment']] ?? 'bg-gray-100 text-gray-700';
    ?>
    <div class="review-card bg-white rounded-2xl p-8 shadow-lg border border-lavender-200 hover:border-plum-300">
        <div class="flex items-start justify-between mb-6">
            <div class="flex items-center">
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-lavender-100 text-plum-700 mr-4">
                    <i class="fas fa-user text-xl"></i>
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
