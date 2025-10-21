<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = $_POST['review_id'] ?? null;

    if (!$review_id) {
        echo json_encode(['success' => false, 'message' => 'Missing review ID']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE reviews SET is_verified = 0 WHERE review_id = ?");
    if ($stmt->execute([$review_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
