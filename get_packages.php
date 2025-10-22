<?php
include 'db.php';
header('Content-Type: application/json');

try {
    session_start();
    $event  = $_GET['event_type']  ?? ($_SESSION['preferred_event']  ?? null);
    $range  = $_GET['price_range'] ?? ($_SESSION['price_range_auto'] ?? null);

    if ($event && $range) {
        $event  = $_GET['event_type']  ?? ($_SESSION['preferred_event']  ?? null);
$range  = $_GET['price_range'] ?? ($_SESSION['price_range_auto'] ?? null);
if ($event && $range) {
  $stmt = $conn->prepare("SELECT * FROM packages WHERE event_type = ? AND price_range = ?");
  $stmt->bind_param("ss", $event, $range);
} elseif ($event) {
  $stmt = $conn->prepare("SELECT * FROM packages WHERE event_type = ?");
  $stmt->bind_param("s", $event);
} else {
  $stmt = $conn->prepare("SELECT * FROM packages");
}
        $stmt->bind_param("ss", $event, $range);
    } elseif ($event) {
        $event  = $_GET['event_type']  ?? ($_SESSION['preferred_event']  ?? null);
$range  = $_GET['price_range'] ?? ($_SESSION['price_range_auto'] ?? null);
if ($event && $range) {
  $stmt = $conn->prepare("SELECT * FROM packages WHERE event_type = ? AND price_range = ?");
  $stmt->bind_param("ss", $event, $range);
} elseif ($event) {
  $stmt = $conn->prepare("SELECT * FROM packages WHERE event_type = ?");
  $stmt->bind_param("s", $event);
} else {
  $stmt = $conn->prepare("SELECT * FROM packages");
}
        $stmt->bind_param("s", $event);
    } else {
        $event  = $_GET['event_type']  ?? ($_SESSION['preferred_event']  ?? null);
$range  = $_GET['price_range'] ?? ($_SESSION['price_range_auto'] ?? null);
if ($event && $range) {
  $stmt = $conn->prepare("SELECT * FROM packages WHERE event_type = ? AND price_range = ?");
  $stmt->bind_param("ss", $event, $range);
} elseif ($event) {
  $stmt = $conn->prepare("SELECT * FROM packages WHERE event_type = ?");
  $stmt->bind_param("s", $event);
} else {
  $stmt = $conn->prepare("SELECT * FROM packages");
}
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $packages = [];
    while ($row = $result->fetch_assoc()) {
        $packages[] = [
            'id'          => (int)$row['package_id'],
            'name'        => $row['name'],
            'description' => $row['description'],
            'event_type'  => $row['event_type'],
            'price_range' => $row['price_range'],
            'price'       => (float)$row['price'],
        ];
    }

    echo json_encode(['success' => true, 'data' => $packages]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching packages: ' . $e->getMessage()]);
}
?>