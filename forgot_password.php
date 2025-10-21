<?php
require 'db.php';
require 'vendor/autoload.php'; // Make sure PHPMailer is loaded

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);

        $resetLink = "http://localhost/MBK GlamHub/reset_password.php?token=$token"; // ✅ Update if using a real domain

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';         // Replace with your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'mbkglamhub@gmail.com';        // SMTP email
            $mail->Password = 'ieik xdvx dxxo ttvb';          // SMTP password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('yourgmail@gmail.com', 'MBK GlamHub');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "
                <h3>Hi {$user['first_name']},</h3>
                <p>You requested a password reset for your MBK GlamHub account.</p>
                <p>
                  <a href='{$resetLink}' style='color: white; background-color: #804f7e; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Your Password</a>
                </p>
                <p>This link will expire in 1 hour.</p>
                <br>
                <p>If you didn’t request this, you can safely ignore this email.</p>
            ";

            $mail->send();
            $message = "Password reset link sent to your email.";
        } catch (Exception $e) {
            $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password | MBK GlamHub</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            lavender: {
              50: '#ffffff',
              100: '#f5f5fa',
              200: '#ececf7',
              300: '#e6e6fa',
              400: '#d8d1e8',
              500: '#c2b6d9',
              600: '#a79dbf',
              700: '#8e83a3',
              800: '#756a86',
              900: '#5d516c'
            },
            plum: {
              50: '#f9f2f7',
              100: '#f1e3ef',
              200: '#e0c5dc',
              300: '#c89ac1',
              400: '#a06c9e',
              500: '#804f7e',
              600: '#673f68',
              700: '#4b2840',
              800: '#3c1f33',
              900: '#2c1726'
            },
          }
        }
      }
    }
  </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-lavender-50 via-lavender-100 to-lavender-200 flex items-center justify-center">

  <div class="bg-white rounded-3xl shadow-2xl p-10 max-w-md w-full border border-plum-200">
    <div class="flex flex-col items-center mb-8">
      <img src="mbk_logo.png" alt="Logo" class="w-25 h-20 mb-4">
      <h2 class="text-3xl font-bold text-plum-800">Forgot Password</h2>
      <p class="text-lavender-800 text-center mt-2">Enter your email to receive a reset link</p>
    </div>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-sm font-medium text-plum-700 mb-2">Email</label>
        <input name="email" type="email" placeholder="Enter your email" required
               class="w-full px-4 py-3 border border-plum-300 rounded-lg focus:outline-none focus:border-plum-500 transition-colors bg-lavender-50">
      </div>
      <button type="submit"
              class="w-full bg-plum-500 text-white hover:bg-plum-700 py-3 rounded-lg font-medium transition-all duration-300 hover:shadow-lg">
        <i class="fas fa-envelope mr-2"></i>
        Send Reset Link
      </button>
    </form>

    <div class="mt-6 text-center text-sm text-plum-700">
      <a href="login.php" class="text-plum-500 hover:text-plum-700 hover:underline">Back to Login</a>
    </div>
  </div>

  <script>
  <?php if (!empty($message)): ?>
    Swal.fire({
      icon: 'success',
      title: 'Email Sent',
      text: '<?= htmlspecialchars($message) ?>',
      confirmButtonColor: '#804f7e'
    });
  <?php elseif (!empty($error)): ?>
    Swal.fire({
      icon: 'error',
      title: 'Oops!',
      text: '<?= htmlspecialchars($error) ?>',
      confirmButtonColor: '#804f7e'
    });
  <?php endif; ?>
  </script>

</body>
</html>
