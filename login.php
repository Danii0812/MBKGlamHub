<?php
session_start();

require 'db.php';

$error = '';

// Redirect already logged-in users
if (isset($_SESSION['user_id'])) {
  if ($_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
  } elseif ($_SESSION['role'] === 'artist') {
    header("Location: artist_dashboard.php");
    exit;
  } elseif ($_SESSION['role'] === 'user') {
    header("Location: homepage.php");
    exit;
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password'])) {
    if ($user['is_verified']) {
      // Set session variables
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['user_name'] = $user['first_name'];
      $_SESSION['role'] = $user['role'];

      // Redirect based on role
      if ($user['role'] === 'admin') {
        header("Location: admin_dashboard.php");
      } elseif ($user['role'] === 'artist') {
        header("Location: artist_dashboard.php");
      } elseif ($user['role'] === 'user') {
        header("Location: homepage.php");
      }
      exit;

    } else {
      $error = "Please verify your email before logging in.";
    }
  } else {
    $error = "Invalid email or password.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login | MBK GlamHub</title>
  <link rel="icon" type="image/png" href="mbk_logo.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
  <script src="https://cdn.tailwindcss.com"></script>
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
<body
  class="min-h-screen bg-gradient-to-br from-lavender-50 via-lavender-100 to-lavender-200 flex items-center justify-center relative opacity-0 translate-y-5"
  onload="document.body.classList.add('slide-up-finish')"
>     <div class="absolute top-4 left-4">
          <a href="homepage.php" class="text-plum-700 hover:text-plum-900 text-lg font-semibold flex items-center">
            <i class="fas fa-arrow-left mr-2 text-base"></i>
            Back
          </a>
        </div>
  <div class="bg-white rounded-3xl shadow-2xl p-10 max-w-md w-full relative">

  
    <div class="flex flex-col items-center mb-8">
      <img src="mbk_logo.png" alt="Logo" class="w-30 h-20 mb-4">
      <h2 class="text-3xl font-bold text-plum-500">Welcome Back</h2>
      <p class="text-plum-700 text-center mt-2">Sign in to your account to book your beauty sessions</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="bg-plum-100 text-plum-700 p-3 rounded-lg mb-4 text-center border border-plum-300">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form class="space-y-6" method="POST" action="">
      <div>
        <label class="block text-sm font-medium text-plum-700 mb-2">Email</label>
        <input name="email" type="email" placeholder="Enter your email" required
               class="w-full px-4 py-3 border border-plum-300 rounded-lg focus:outline-none focus:border-plum-500 transition-colors bg-lavender-50">
      </div>
      <div>
        <label class="block text-sm font-medium text-plum-700 mb-2">Password</label>
        <div class="relative">
          <input id="password" name="password" type="password" placeholder="Enter your password" required
                class="w-full px-4 py-3 border border-plum-300 rounded-lg focus:outline-none focus:border-plum-500 transition-colors bg-lavender-50 pr-10">
          <span id="togglePassword" 
                class="absolute inset-y-0 right-3 flex items-center cursor-pointer text-plum-500 hover:text-plum-700 hidden">
            <i class="fas fa-eye"></i>
          </span>
        </div>
      </div>
      <div class="text-right mt-1">
          <a href="forgot_password.php" class="text-sm text-plum-500 hover:text-plum-700 hover:underline">
            Forgot Password?
        </a>
      </div>

      <button type="submit"
              class="w-full bg-plum-500 text-white hover:bg-plum-700 py-3 rounded-lg font-medium transition-all duration-300 hover:shadow-lg">
        <i class="fas fa-sign-in-alt mr-2"></i>
        Log In
      </button>
    </form>




    <div class="mt-6 text-center text-plum-700 text-sm">
      Don't have an account? <a href="signup.php" class="text-plum-500 hover:text-plum-700 hover:underline">Sign Up</a>
    </div>
  </div>


  <script>
  const togglePassword = document.querySelector("#togglePassword");
  const passwordInput = document.querySelector("#password");

  // Toggle password visibility
  togglePassword.addEventListener("click", () => {
    const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
    passwordInput.setAttribute("type", type);

    // Toggle icon
    togglePassword.innerHTML = type === "password" 
      ? '<i class="fas fa-eye"></i>' 
      : '<i class="fas fa-eye-slash"></i>';
  });

  // Show/hide icon based on input value
  passwordInput.addEventListener("input", () => {
    if (passwordInput.value.length > 0) {
      togglePassword.classList.remove("hidden");
    } else {
      togglePassword.classList.add("hidden");
      // Reset back to eye + password type when cleared
      passwordInput.setAttribute("type", "password");
      togglePassword.innerHTML = '<i class="fas fa-eye"></i>';
    }
  });
</script>
<style>
    /* Smooth slide-up animation */
    @keyframes softSlideUp {
      from {
        opacity: 0;
        transform: translateY(25px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Apply animation once on load */
    body.slide-up-finish {
      animation: softSlideUp 0.6s ease-out forwards;
    }
  </style>
</body>
</html>


