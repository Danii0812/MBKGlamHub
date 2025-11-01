<?php
// upload_payment_proof.php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Basic validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}
if (empty($_POST['booking_id']) || !isset($_FILES['gcash_proof'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing booking_id or file']);
    exit;
}

$booking_id = (int)$_POST['booking_id'];

// DB connect (mysqli or PDO — I’ll match your page’s mysqli)
$conn = new mysqli("localhost", "root", "", "mbk_db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connect failed']);
    exit;
}

// Ensure the booking belongs to this user (prevents tampering)
$own = $conn->prepare("SELECT 1 FROM bookings WHERE booking_id = ? AND user_id = ?");
$own->bind_param("ii", $booking_id, $user_id);
$own->execute();
$res = $own->get_result();
if ($res->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized booking']);
    exit;
}
$own->close();

// Validate file
$file = $_FILES['gcash_proof'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Upload error']);
    exit;
}

// Limit size (~5MB)
$maxBytes = 5 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File too large (max 5MB)']);
    exit;
}

// Allow only images
$allowedExt = ['jpg','jpeg','png','gif','webp'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit;
}

// Make uploads dir if needed
$uploadDir = __DIR__ . '/uploads/payment_proofs';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0775, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create upload dir']);
        exit;
    }
}

// Safer filename
$basename = 'booking_' . $booking_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destPath = $uploadDir . '/' . $basename;
$publicPath = 'uploads/payment_proofs/' . $basename; // what you’ll store in DB

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

// Update DB: store proof path and timestamp
$now = date('Y-m-d H:i:s');
$upd = $conn->prepare("
    UPDATE bookings
       SET payment_proof_path = ?, payment_proof_uploaded_at = ?, payment_status = 'pending'
     WHERE booking_id = ? AND user_id = ?
");
$upd->bind_param("ssii", $publicPath, $now, $booking_id, $user_id);
$ok = $upd->execute();
$upd->close();

if (!$ok) {
    // Rollback file if DB failed
    @unlink($destPath);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update booking']);
    exit;
}

echo json_encode([
    'success' => true,
    'path' => $publicPath,
    'uploaded_at' => $now
]);
