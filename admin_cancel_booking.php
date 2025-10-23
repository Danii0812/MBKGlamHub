<?php
session_start();
require 'db.php';
require 'vendor/autoload.php'; // <-- adjust this if PHPMailer is in another folder

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure only admin can cancel
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "mbk_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle cancel POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $bookingId = intval($_POST['cancel_booking_id']);
    $reason = $conn->real_escape_string($_POST['cancel_reason']);
    $note = $conn->real_escape_string($_POST['cancel_note']);

    // Fetch booking + client info
    $bookingQuery = "
        SELECT b.*, u.email, u.first_name, u.last_name
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_id = $bookingId
    ";
    $bookingResult = $conn->query($bookingQuery);
    $booking = $bookingResult->fetch_assoc();

    if ($booking) {
        // Update booking record
        $conn->query("
            UPDATE bookings 
            SET is_confirmed = 2, 
                payment_status = 'cancelled',
                cancel_reason = '$reason',
                cancel_note = '$note'
            WHERE booking_id = $bookingId
        ");

        // Prepare email
        $to = $booking['email'];
        $clientName = $booking['first_name'] . ' ' . $booking['last_name'];
        $subject = "Your Booking Has Been Cancelled - MBK Services";
        $date = date("F j, Y", strtotime($booking['booking_date']));
        $time = date("g:i A", strtotime($booking['booking_time']));
        $address = $booking['booking_address'];

        $message = "
        <html>
        <head>
            <title>Booking Cancellation Notice</title>
        </head>
        <body style='font-family: Arial, sans-serif; background-color:#f9f9f9; padding:20px;'>
            <div style='max-width:600px; margin:auto; background:#fff; border-radius:8px; padding:20px;'>
                <h2 style='color:#a06c9e; text-align:center;'>Booking Cancellation Notice</h2>
                <p>Dear <strong>$clientName</strong>,</p>
                <p>We regret to inform you that your booking has been <strong style='color:#d9534f;'>cancelled</strong>.</p>

                <h3 style='color:#a06c9e;'>Booking Details</h3>
                <ul>
                    <li><strong>Date:</strong> $date</li>
                    <li><strong>Time:</strong> $time</li>
                    <li><strong>Address:</strong> $address</li>
                </ul>

                <h3 style='color:#a06c9e;'>Reason for Cancellation</h3>
                <p><strong>$reason</strong></p>
                " . (!empty($note) ? "<p><em>Additional note:</em> $note</p>" : "") . "

                <p>If you have any questions, please contact our support team.</p>
                <p>Kind regards,<br><strong>MBK Admin Team</strong></p>
            </div>
        </body>
        </html>
        ";

        // Send email via PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // your SMTP host
            $mail->SMTPAuth = true;
            $mail->Username = 'mbkglamhub@gmail.com';        // SMTP email
            $mail->Password = 'ieik xdvx dxxo ttvb';          // SMTP password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Sender & Recipient
            $mail->setFrom('no-reply@mbkservices.com', 'MBK Admin');
            $mail->addAddress($to, $clientName);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
        }
    }

    header("Location: admin_manage_bookings.php?cancelled=1");
    exit;
} else {
    header("Location: admin_manage_bookings.php");
    exit;
}
