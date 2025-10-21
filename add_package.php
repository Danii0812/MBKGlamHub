<?php
require 'db.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['name']) || empty($data['price']) || empty($data['description'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $name = $data['name'];
    $description = $data['description'];
    $event_type = $data['event_type'];
    $price_range = $data['price_range'];
    $price = $data['price'];

    $stmt = $pdo->prepare("
        INSERT INTO packages (name, description, event_type, price_range, price)
        VALUES (:name, :description, :event_type, :price_range, :price)
    ");
    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':event_type' => $event_type,
        ':price_range' => $price_range,
        ':price' => $price
    ]);

    echo json_encode(['success' => true, 'message' => 'Package added successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
