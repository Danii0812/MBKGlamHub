<?php
session_start(); // 🔧 REQUIRED to use $_SESSION
require 'db.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($password !== $confirm) {
        $_SESSION['error'] = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $_SESSION['error'] = "Password must be at least 8 characters, include uppercase, lowercase, number, and symbol.";
    } else {
        $currentTime = date("Y-m-d H:i:s");

        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > ?");
        $stmt->execute([$token, $currentTime]);
        $reset = $stmt->fetch();

        if ($reset) {
            $email = $reset['email'];
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hashed, $email]);
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            $_SESSION['success'] = "Your password has been reset.";
        } else {
            $_SESSION['error'] = "Invalid or expired reset link.";
        }
    }

    // 🔁 Redirect to display SweetAlert
    header("Location: reset_password.php?token=" . urlencode($token));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password | MBK GlamHub</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-white min-h-screen flex items-center justify-center">

  <div class="bg-white rounded-3xl shadow-2xl p-10 max-w-md w-full border border-purple-200">
    <div class="flex flex-col items-center mb-8">
      <img src="logo.png" alt="Logo" class="w-20 h-20 mb-4">
      <h2 class="text-3xl font-bold text-violet-900">Reset Password</h2>
      <p class="text-gray-500 text-center mt-2">Enter your new password</p>
    </div>

    <form method="POST" class="space-y-6">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
        <input name="password" type="password" placeholder="New password" required
               class="w-full px-4 py-3 border border-purple-300 rounded-lg focus:outline-none focus:border-violet-500 transition-colors">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
        <input name="confirm" type="password" placeholder="Confirm password" required
               class="w-full px-4 py-3 border border-purple-300 rounded-lg focus:outline-none focus:border-violet-500 transition-colors">
      </div>
      <button type="submit"
              class="w-full bg-[#E6E6FA] text-plum-900 hover:bg-[#4B145B] hover:text-white py-3 rounded-lg font-medium transition-all duration-300 hover:shadow-lg">
        <i class="fas fa-key mr-2"></i>
        Reset Password
      </button>
    </form>
  </div>

  <!-- SweetAlert Handling -->
<script>
  <?php if (!empty($_SESSION['error'])): ?>
    Swal.fire({
      icon: 'error',
      title: 'Error',
      html: '<?= addslashes($_SESSION["error"]) ?>',
      confirmButtonColor: '#804f7e'
    });
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['success'])): ?>
    Swal.fire({
      icon: 'success',
      title: 'Success',
      html: '<?= addslashes($_SESSION["success"]) ?>',
      confirmButtonColor: '#804f7e'
    }).then(() => {
      window.location.href = 'login.php';
    });
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>
</script>


</body>
</html>