<?php
session_start();
require 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Optional caching (refresh every 30s)
if (isset($_SESSION['notifications_cache']) && isset($_SESSION['notifications_cache_time'])) {
    if (time() - $_SESSION['notifications_cache_time'] < 30) {
        $cached = $_SESSION['notifications_cache'];
    }
}

$notifications = [];

// --- BOOKINGS ---
$bookingsQuery = "
    SELECT 
        booking_id AS id, 
        user_id, 
        CONCAT(booking_date, ' ', booking_time) AS created_at,
        'booking' AS type,
        CONCAT('New booking scheduled (ID: ', booking_id, ') on ', booking_date, ' at ', booking_time) AS message
    FROM bookings 
    WHERE is_confirmed != 2
";

// --- CANCELLATIONS ---
$cancellationsQuery = "
    SELECT 
        booking_id AS id, 
        user_id, 
        CONCAT(booking_date, ' ', booking_time) AS created_at,
        'cancellation' AS type,
        CONCAT('Booking #', booking_id, ' was cancelled (', booking_date, ' ', booking_time, ')') AS message
    FROM bookings 
    WHERE is_confirmed = 2
";

// --- REVIEWS ---
$reviewsQuery = "
    SELECT 
        review_id AS id, 
        user_id, 
        created_at, 
        'review' AS type, 
        CONCAT('New review: ', LEFT(comment, 50)) AS message
    FROM reviews
";

$all = [];
foreach ([$bookingsQuery, $cancellationsQuery, $reviewsQuery] as $query) {
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $all[] = $row;
    }
}

// Sort newest first
usort($all, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

// Slice for pagination
$total = count($all);
$paged = array_slice($all, $offset, $limit);
$hasMore = ($offset + $limit) < $total;

$response = [
    'notifications' => $paged,
    'hasMore' => $hasMore
];

$_SESSION['notifications_cache'] = $all;
$_SESSION['notifications_cache_time'] = time();

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
