<?php
include 'db.php';

header('Content-Type: application/json');

try {
    $query = "SELECT * FROM packages";
    $result = $conn->query($query);

    $packages = [];
    while ($row = $result->fetch_assoc()) {
        $packages[] = [
            'id' => $row['package_id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'event_type' => $row['event_type'],
            'price_range' => $row['price_range'],
            'price' => (float)$row['price']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $packages
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching packages: ' . $e->getMessage()
    ]);
}
?>
