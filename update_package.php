<?php
require 'db.php'; // ✅ ensures $pdo exists
header('Content-Type: application/json');

// Decode JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

$id = $input['id'] ?? null;
$name = $input['name'] ?? '';
$description = $input['description'] ?? '';
$event_type = $input['event_type'] ?? '';
$price_range = $input['price_range'] ?? '';
$price = $input['price'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Missing package ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE packages 
        SET name = :name,
            description = :description,
            event_type = :event_type,
            price_range = :price_range,
            price = :price
        WHERE package_id = :id
    ");

    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':event_type' => $event_type,
        ':price_range' => $price_range,
        ':price' => $price,
        ':id' => $id
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
