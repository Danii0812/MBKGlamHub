<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';


function sendOtpEmail($toEmail, $toName, $otp) {
  $mail = new PHPMailer(true);

  try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';         // Replace with your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'mbkglamhub@gmail.com';        // SMTP email
    $mail->Password = 'ieik xdvx dxxo ttvb';          // SMTP password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('your_email@gmail.com', 'Make Up by Kyleen');
    $mail->addAddress($toEmail, $toName);

    $mail->isHTML(true);
    $mail->Subject = 'Verify Your Email - Make Up by Kyleen';
    $mail->Body    = "
      <h2>Hi $toName,</h2>
      <p>Thank you for signing up! Please use the OTP below to verify your email:</p>
      <h1 style='color:#7d3c58;'>$otp</h1>
      <p>Don't share this code with anyone.</p>
    ";

    $mail->send();
    return true;
  } catch (Exception $e) {
    return 'Mailer Error: ' . $mail->ErrorInfo;
  }
}
