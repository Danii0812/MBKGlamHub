<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $bookingId = intval($_POST['booking_id']);
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        try {
            // Update booking status to 'canceled'
           $stmt = $pdo->prepare("UPDATE bookings SET is_confirmed = 2, payment_status = 'paid' WHERE booking_id = ? AND user_id = ?");
            $stmt->execute([$bookingId, $userId]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "Booking has been canceled.";
            } else {
                $_SESSION['error'] = "Booking not found or already canceled.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "You must be logged in to cancel a booking.";
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: appointments.php");
exit();
