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
  <link rel="icon" type="image/png" href="mbk_logo.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            lavender: {
              50: '#F8F5FA',
              100: '#F0EBF5',
              200: '#E0D6EB',
              300: '#D0C1E1',
              400: '#C0ACD7',
              500: '#a06c9e',
              600: '#804f7e',
              700: '#60325e',
              800: '#40153e',
              900: '#20001e',
            },
            plum: {
              50: '#F5F0F5',
              100: '#EBE0EB',
              200: '#D7C0D7',
              300: '#C3A0C3',
              400: '#AF80AF',
              500: '#4b2840',
              600: '#673f68',
              700: '#4A2D4B',
              800: '#2E1B2E',
              900: '#120912',
            },
          },
        },
      },
    };
  </script>

  <style>
    .gradient-bg {
      background: linear-gradient(to right, #a06c9e, #4b2840);
    }
    .gradient-bg:hover {
      background: linear-gradient(to right, #804f7e, #673f68);
    }
  </style>
</head>

<body class="min-h-screen flex items-center justify-center bg-white font-sans">

  <div class="bg-white rounded-3xl shadow-2xl p-10 max-w-md w-full border border-lavender-200">
    <!-- Logo + Header -->
    <div class="flex flex-col items-center mb-8">
      <img src="mbk_logo.png" alt="Makeup by Kyleen Logo" class="w-20 h-20 mb-4">
      <h2 class="text-3xl font-bold text-plum-700 font-heading">Reset Password</h2>
      <p class="text-gray-500 text-center mt-2">Enter your new password below to continue.</p>
    </div>

    <!-- Form -->
    <form method="POST" class="space-y-6">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
        <input name="password" type="password" placeholder="Enter new password" required
               class="w-full px-4 py-3 border border-lavender-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-plum-400 focus:border-plum-400 transition-all">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
        <input name="confirm" type="password" placeholder="Confirm password" required
               class="w-full px-4 py-3 border border-lavender-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-plum-400 focus:border-plum-400 transition-all">
      </div>

      <!-- Submit -->
      <button type="submit"
              class="w-full gradient-bg text-white py-3 rounded-lg font-medium transition-all duration-300 hover:shadow-lg flex items-center justify-center gap-2">
        <i class="fas fa-key"></i>
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