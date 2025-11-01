<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

$userId = (int)$_SESSION['user_id'];
$bookingId = (int)($_GET['booking_id'] ?? 0);

if ($bookingId <= 0) {
    http_response_code(400);
    exit('Invalid request');
}

// DB connect
$conn = new mysqli("localhost", "root", "", "mbk_db");
if ($conn->connect_error) {
    http_response_code(500);
    exit('DB connection failed');
}

$stmt = $conn->prepare("
    SELECT payment_proof_path
    FROM bookings
    WHERE booking_id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $bookingId, $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row || empty($row['payment_proof_path'])) {
    http_response_code(404);
    exit('No proof found');
}

// resolve actual file path
$relativePath = $row['payment_proof_path'];
$baseDir = __DIR__ . '/uploads/payment_proofs/'; // ✅ matches your actual folder
$file = realpath($baseDir . basename($relativePath));

if (!$file || !file_exists($file)) {
    http_response_code(404);
    exit('File missing: ' . htmlspecialchars($file));
}

// detect mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file);
finfo_close($finfo);

// serve inline
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($file));
header('Content-Disposition: inline; filename="' . basename($file) . '"');
readfile($file);
?>
