<?php
include('db.php');
require 'send_otp_email.php';
$isLoggedIn = isset($_SESSION['user_id']);
$greetingName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName  = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name']);
    $lastName   = trim($_POST['last_name']);
    $birthDate  = $_POST['birth_date'];
    $sex        = $_POST['sex'];
    $contact    = trim($_POST['contact_no']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $confirmPwd = $_POST['confirm_password'];

    // Password complexity check
    $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";
    if (!preg_match($pattern, $password)) {
        header("Location: signup.php?status=weak_password");
        exit;
    }


    // Escape for later use in URL
    $safeEmail = urlencode($email);
    $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');

    if (empty($firstName) || empty($lastName) || empty($birthDate) || empty($sex) || empty($contact) || empty($email) || empty($password)) {
        header("Location: signup.php?status=incomplete");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: signup.php?status=invalid_email");
        exit;
    }

    if ($password !== $confirmPwd) {
        header("Location: signup.php?status=password_mismatch");
        exit;
    }

    // Check for duplicate email
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header("Location: signup.php?status=email_exists&email=$safeEmail");
        exit;
    }

    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $otp = rand(100000, 999999);
        $defaultRole = 'user';

        $stmt = $pdo->prepare("INSERT INTO users (first_name, middle_name, last_name, birth_date, sex, contact_no, email, password, otp, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $firstName, $middleName, $lastName, $birthDate, $sex, $contact, $email, $hashedPassword, $otp, $defaultRole
        ]);

        $sent = sendOtpEmail($email, $firstName, $otp);

        if ($sent === true) {
            header("Location: signup.php?status=success&email=$safeEmail");
            exit;
        } else {
            $errorMsg = urlencode($sent);
            header("Location: signup.php?status=email_failed&message=$errorMsg");
            exit;
        }

    } catch (PDOException $e) {
        $err = urlencode($e->getMessage());
        header("Location: signup.php?status=server_error&message=$err");
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MBK GlamHub</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            lavender: {
              50: '#fafaff',
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
            }
          }
        }
      }
    }
  </script>
  <style>
    .gradient-text {
      background: linear-gradient(to right, #a06c9e, #4b2840);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .gradient-bg {
      background: linear-gradient(to right, #a06c9e, #4b2840);
    }
    .gradient-bg:hover {
      background: linear-gradient(to right, #804f7e, #673f68);
    }
    html {
      scroll-behavior: smooth;
    }
  </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-lavender-50 via-lavender-100 to-lavender-200">

<!-- Header -->
<header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-lavender-200">
  <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
    <div class="flex items-center space-x-2">
      <a href="homepage.php"><img src="mbk_logo.png" alt="Make up By Kyleen Logo" class="h-14 w-auto"></a>
    </div>

      <nav class="hidden md:flex items-center space-x-8">
        <a href="#services" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Services</a>
        <a href="#about" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">About</a>
        <a href="artist_portfolio.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Portfolio</a>
        <a href="reviews.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Reviews</a>
<!-- Dropdown Menu Container -->
<div class="relative group inline-block text-left">
  <span class="gradient-bg text-white px-6 py-2 rounded-md font-medium transition-all inline-block cursor-pointer">
    Hello, <?php echo htmlspecialchars($greetingName); ?>
    <i class="fas fa-chevron-down text-white text-sm"></i>
  </span>

  <!-- Dropdown Items -->
  <div class="absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-md shadow-lg opacity-0 group-hover:opacity-100 invisible group-hover:visible transition-all z-50">
    <?php if ($isLoggedIn): ?>
      <a href="appointments.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">My Appointments</a>
      <a href="profile_settings.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile Settings</a>
      <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Sign Out</a>
    <?php else: ?>
      <a href="login.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Log In</a>
    <?php endif; ?>
  </div>
</div>



      </nav>
    </div>
  </div>
</header>

<!-- Main Content -->
<div class="min-h-screen flex items-center justify-center py-12 px-4">
  <div class="w-full max-w-6xl">
    <!-- Sign Up Form -->
    <div class="bg-white rounded-3xl shadow-2xl border border-lavender-200 p-12">
      <!-- Header -->
      <div class="text-center mb-8">

        <h2 class="text-3xl font-bold text-center mt-6 gradient-text font-poppins">Create an Account</h2>
      </div>

      <form method="POST" action="signup.php" class="space-y-6">
        <!-- Name Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
            <input type="text" id="first_name" name="first_name" required
              class="w-full px-4 py-3 border border-lavender-300 rounded-lg bg-white focus:outline-none focus:border-plum-500 focus:ring-2 focus:ring-plum-200 transition-all duration-300">
          </div>
          <div>
            <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
            <input type="text" id="middle_name" name="middle_name"
              class="w-full px-4 py-3 border border-lavender-300 rounded-lg bg-white focus:outline-none focus:border-plum-500 focus:ring-2 focus:ring-plum-200 transition-all duration-300">
          </div>
          <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
            <input type="text" id="last_name" name="last_name" required
              class="w-full px-4 py-3 border border-lavender-300 rounded-lg bg-white focus:outline-none focus:border-plum-500 focus:ring-2 focus:ring-plum-200 transition-all duration-300">
          </div>
        </div>

        <!-- Birth Date + Sex -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-2">Birth Date *</label>
            <input type="date" id="birth_date" name="birth_date" required
              class="w-full px-4 py-3 border border-lavender-300 rounded-lg bg-white focus:outline-none focus:border-plum-500 focus:ring-2 focus:ring-plum-200 transition-all duration-300">
          </div>
          <div>
            <label for="sex" class="block text-sm font-medium text-gray-700 mb-2">Sex *</label>
            <select id="sex" name="sex" required
              class="w-full px-4 py-3 border border-lavender-300 rounded-lg bg-white focus:outline-none focus:border-plum-500 focus:ring-2 focus:ring-plum-200 transition-all duration-300">
              <option value="">-- Select --</option>
              <option value="Female">Female</option>
              <option value="Male">Male</option>
              <option value="Non-binary">Non-binary</option>
              <option value="Prefer not to say">Prefer not to say</option>
            </select>
          </div>
        </div>

        <!-- Contact + Email -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="contact_no" class="block text-sm font-medium text-gray-700 mb-2">Contact No *</label>
            <input type="tel" id="contact_no" name="contact_no" required
              class="w-full px-4 py-3 border border-lavender-300 rounded-lg bg-white focus:outline-none focus:border-plum-500 focus:ring-2 focus:ring-plum-200 transition-all duration-300">
          </div>
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
            <input type="email" id="email" name="email" required
              class="w-full px-4 py-3 border border-lavender-300 rounded-lg bg-white focus:outline-none focus:border-plum-500 focus:ring-2 focus:ring-plum-200 transition-all duration-300">
          </div>
        </div>

        <!-- Password + Confirm -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
            <div class="relative">
              <input type="password" id="password" name="password" required
                class="w-full px-4 py-3 border border-lavender-300 rounded-lg bg-white pr-12 focus:outline-none focus:border-plum-500 focus:ring-2 focus:ring-plum-200 transition-all duration-300">
              <button type="button" onclick="togglePassword('password')"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-plum-600 transition-colors">
                <i class="fas fa-eye" id="password-eye"></i>
              </button>
            </div>
          </div>
          <div>
            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password *</label>
            <div class="relative">
              <input type="password" id="confirm_password" name="confirm_password" required
                class="w-full px-4 py-3 border border-lavender-300 rounded-lg bg-white pr-12 focus:outline-none focus:border-plum-500 focus:ring-2 focus:ring-plum-200 transition-all duration-300">
              <button type="button" onclick="togglePassword('confirm_password')"
                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-plum-600 transition-colors">
                <i class="fas fa-eye" id="confirm_password-eye"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <button type="submit"
          class="w-full gradient-bg text-white py-4 text-lg rounded-lg font-medium transition-all duration-300 hover:shadow-lg transform hover:scale-102">
          <i class="fas fa-user-plus mr-2"></i> Sign Up
        </button>
      </form>

      <!-- Already Have Account -->
      <div class="mt-8 text-center">
        <p class="text-gray-600">
          Already have an account?
          <a href="login.html" class="text-plum-600 hover:text-plum-700 font-medium transition-colors">
            Sign In
          </a>
        </p>
      </div>

      <!-- Divider -->
      <div class="mt-8">
        <div class="relative">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-lavender-300"></div>
          </div>
          <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white text-gray-500">Or continue with</span>
          </div>
        </div>

        <!-- Social Buttons -->
        <div class="mt-6 grid grid-cols-2 gap-3">
          <button class="w-full inline-flex justify-center py-3 px-4 border border-lavender-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-lavender-50 transition-colors">
            <i class="fab fa-google text-red-500 mr-2"></i> Google
          </button>
          <button class="w-full inline-flex justify-center py-3 px-4 border border-lavender-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-lavender-50 transition-colors">
            <i class="fab fa-facebook text-blue-600 mr-2"></i> Facebook
          </button>
        </div>
      </div>

      <!-- Terms -->
        <div class="mt-8 text-center text-sm text-gray-600">
          <p>
            By signing up, you agree to our
            <button onclick="openModal('termsModal')" 
                    class="text-plum-600 hover:text-plum-700 transition-colors underline">
              Terms of Service
            </button>
            and
            <button onclick="openModal('privacyModal')" 
                    class="text-plum-600 hover:text-plum-700 transition-colors underline">
              Privacy Policy
            </button>.
          </p>
        </div>

        <!-- Modal Background -->
        <div id="termsModal" class="fixed inset-0 hidden items-center justify-center bg-black bg-opacity-50 z-50">
          <div class="bg-white w-full max-w-lg mx-4 rounded-xl shadow-lg p-6 relative">
            <h2 class="text-xl font-bold text-plum-700 mb-4">Terms of Service</h2>
            <div class="text-gray-700 max-h-80 overflow-y-auto">
                  <p>
                    Welcome to MBK GlamHub! By using our website, products, or services, you agree to comply with the following Terms of Service.
                  </p>

                  <h3 class="font-bold mt-3">1. Acceptance of Terms</h3>
                  <p>
                    By creating an account, booking a service, or otherwise accessing our platform, you acknowledge that you have read, understood, and agreed to these Terms.
                  </p>

                  <h3 class="font-bold mt-3">2. Use of Services</h3>
                  <p>
                    You agree to use our services only for lawful purposes and not for any fraudulent or unauthorized activities.
                  </p>

                  <h3 class="font-bold mt-3">4. Bookings & Payments</h3>
                  <p>
                    All bookings are subject to availability. Payments must be completed as indicated during checkout. Refunds or cancellations may be subject to specific conditions.
                  </p>

                  <h3 class="font-bold mt-3">5. Limitation of Liability</h3>
                  <p>
                    We are not responsible for any indirect, incidental, or consequential damages arising from the use of our services.
                  </p>

                  <h3 class="font-bold mt-3">6. Changes to Terms</h3>
                  <p>
                    We may update these Terms from time to time. Continued use of our services after updates constitutes acceptance of the revised Terms.
                  </p>

            </div>
            <div class="mt-4 flex justify-end">
              <button onclick="closeModal('termsModal')" class="px-4 py-2 bg-plum-600 text-white rounded-lg hover:bg-plum-700">Close</button>
            </div>
          </div>
        </div>

        <!-- Privacy Policy Modal -->
        <div id="privacyModal" class="fixed inset-0 hidden items-center justify-center bg-black bg-opacity-50 z-50">
          <div class="bg-white w-full max-w-lg mx-4 rounded-xl shadow-lg p-6 relative">
            <h2 class="text-xl font-bold text-plum-700 mb-4">Privacy Policy</h2>
            <div class="text-gray-700 max-h-80 overflow-y-auto">
            <p>
              Your privacy is important to us. This Privacy Policy explains how we collect, use, and protect your information.
            </p>

            <h3 class="font-bold mt-3">1. Information We Collect</h3>
            <p>
              We may collect personal information such as your name, email address, phone number, and booking details when you use our services.
            </p>

            <h3 class="font-bold mt-3">2. How We Use Your Information</h3>
            <p>
              We use your information to process bookings, improve our services, send notifications, and communicate with you about your account.
            </p>

            <h3 class="font-bold mt-3">3. Data Protection</h3>
            <p>
              We implement reasonable security measures to protect your personal data. However, no method of transmission over the Internet is 100% secure.
            </p>

            <h3 class="font-bold mt-3">4. Sharing of Information</h3>
            <p>
              We do not sell or trade your personal information. We may share data with trusted partners only when necessary to deliver services.
            </p>

            <h3 class="font-bold mt-3">5. Cookies</h3>
            <p>
              Our website may use cookies to enhance user experience. You can disable cookies in your browser settings, but some features may not work properly.
            </p>

            <h3 class="font-bold mt-3">6. Your Rights</h3>
            <p>
              You have the right to access, update, or request deletion of your personal data. Contact us if you wish to exercise these rights.
            </p>

            <h3 class="font-bold mt-3">7. Updates to Policy</h3>
            <p>
              We may revise this Privacy Policy occasionally. Changes will be effective once posted on this page.
            </p>

            </div>
            <div class="mt-4 flex justify-end">
              <button onclick="closeModal('privacyModal')" class="px-4 py-2 bg-plum-600 text-white rounded-lg hover:bg-plum-700">Close</button>
            </div>
          </div>
        </div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const params = new URLSearchParams(window.location.search);
    const status = params.get('status');
    const email = params.get('email');
    const message = params.get('message');

    if (status === 'success' && email) {
        Swal.fire({
            icon: 'success',
            title: 'Account Created Successfully!',
            html: `A verification code has been sent to <strong>${email}</strong><br><br>Please check your email and verify your account.`,
            confirmButtonText: 'Verify Now',
            confirmButtonColor: '#804f7e',
            allowOutsideClick: false
        }).then(() => {
            window.location.href = 'verify.php';
        });
    }

    if (status === 'email_failed' && message) {
        Swal.fire({
            icon: 'error',
            title: 'Email Sending Failed',
            html: `<strong>Error:</strong> ${decodeURIComponent(message)}`,
            confirmButtonColor: '#804f7e'
        });
    }

    if (status === 'email_exists' && email) {
        Swal.fire({
            icon: 'error',
            title: 'Email Already Registered',
            text: `The email address ${email} is already in use.`,
            confirmButtonColor: '#804f7e'
        });
    }

    if (status === 'invalid_email') {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Email',
            text: 'Please enter a valid email address.',
            confirmButtonColor: '#804f7e'
        });
    }

    if (status === 'weak_password') {
    Swal.fire({
        icon: 'error',
        title: 'Weak Password',
        html: 'Your password must contain at least:<br><ul style="text-align:left;"><li>8 characters</li><li>1 uppercase letter</li><li>1 lowercase letter</li><li>1 number</li><li>1 special character</li></ul>',
        confirmButtonColor: '#804f7e'
    });
}

    if (status === 'password_mismatch') {
        Swal.fire({
            icon: 'error',
            title: 'Password Mismatch',
            text: 'Passwords do not match.',
            confirmButtonColor: '#804f7e'
        });
    }

    if (status === 'incomplete') {
        Swal.fire({
            icon: 'warning',
            title: 'Incomplete Form',
            text: 'Please fill in all required fields.',
            confirmButtonColor: '#804f7e'
        });
    }

    if (status === 'server_error' && message) {
        Swal.fire({
            icon: 'error',
            title: 'Server Error',
            html: `<strong>Error:</strong> ${decodeURIComponent(message)}`,
            confirmButtonColor: '#804f7e'
        });
    }

    // Clean the URL to prevent repeated alerts
    if (status) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
     function openModal(id) {
    document.getElementById(id).classList.remove("hidden");
    document.getElementById(id).classList.add("flex");
  }

  function closeModal(id) {
    document.getElementById(id).classList.remove("flex");
    document.getElementById(id).classList.add("hidden");
  }
</script>



    <script>
        // Password visibility toggle
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(fieldId + '-eye');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    const complexityPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

    if (password !== confirmPassword) {
        e.preventDefault();
        Swal.fire({
            title: 'Oops!',
            text: 'Passwords do not match. Please try again.',
            icon: 'error',
            customClass: {
                popup: 'swal-popup-custom',
                title: 'swal-title-custom',
                confirmButton: 'swal-button-custom'
            }
        });
        return false;
    }

    if (!complexityPattern.test(password)) {
        e.preventDefault();
        Swal.fire({
            title: 'Weak Password',
            html: 'Password must be at least 8 characters and include:<br><ul style="text-align:left;"><li>1 uppercase letter</li><li>1 lowercase letter</li><li>1 number</li><li>1 special character</li></ul>',
            icon: 'error',
            customClass: {
                popup: 'swal-popup-custom',
                title: 'swal-title-custom',
                confirmButton: 'swal-button-custom'
            }
        });
        return false;
    }
});


        
    </script>
</body>
</html>