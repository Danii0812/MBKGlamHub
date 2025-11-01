<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mbk_db"; // Your database name

// Create connection (MySQLi)
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'] ?? null;
$isLoggedIn = isset($_SESSION['user_id']);
$greetingName = $_SESSION['user_name'] ?? 'Guest';

$message = '';
$message_type = ''; // 'success' or 'error'

// Redirect if not logged in
if (!$isLoggedIn) {
    header("Location: login.php"); // Assuming you have a login page
    exit();
}

// Fetch user data
$user_data = [];
$stmt = $conn->prepare("SELECT first_name, middle_name, last_name, birth_date, sex, contact_no, email, password FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    $message = "User data not found.";
    $message_type = "error";
}
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $first_name = $_POST['first_name'] ?? '';
        $middle_name = $_POST['middle_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $birth_date = $_POST['birth_date'] ?? '';
        $sex = $_POST['sex'] ?? '';
        $contact_no = $_POST['contact_no'] ?? '';
        $email = $_POST['email'] ?? '';

        // Basic validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($contact_no)) {
            $message = "Please fill in all required fields.";
            $message_type = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email format.";
            $message_type = "error";
        } else {
            // Update user information
            $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ?, birth_date = ?, sex = ?, contact_no = ?, email = ? WHERE user_id = ?");
            $update_stmt->bind_param("sssssssi", $first_name, $middle_name, $last_name, $birth_date, $sex, $contact_no, $email, $user_id);

            if ($update_stmt->execute()) {
                $message = "Profile updated successfully!";
                $message_type = "success";
                // Update session name if first/last name changed
                $_SESSION['user_name'] = trim($first_name . ' ' . $last_name);
                // Refresh local copy
                $user_data['first_name'] = $first_name;
                $user_data['middle_name'] = $middle_name;
                $user_data['last_name'] = $last_name;
                $user_data['birth_date'] = $birth_date;
                $user_data['sex'] = $sex;
                $user_data['contact_no'] = $contact_no;
                $user_data['email'] = $email;
            } else {
                $message = "Error updating profile: " . $conn->error;
                $message_type = "error";
            }
            $update_stmt->close();
        }

    } elseif (isset($_POST['change_password'])) {
    // Ensure session & user id exist
    if (session_status() === PHP_SESSION_NONE) session_start();
    $user_id = $_SESSION['user_id'] ?? null;

    $current_password     = trim($_POST['current_password'] ?? '');
    $new_password         = trim($_POST['new_password'] ?? '');
    $confirm_new_password = trim($_POST['confirm_new_password'] ?? '');

    // Basic validation
    if (!$user_id) {
        $message = "You must be logged in to change your password.";
        $message_type = "error";
    } elseif ($current_password === '' || $new_password === '' || $confirm_new_password === '') {
        $message = "Please fill in all password fields.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_new_password) {
        $message = "New password and confirmation do not match.";
        $message_type = "error";
    } elseif (strlen($new_password) < 8) {
        $message = "New password must be at least 8 characters long.";
        $message_type = "error";
    } else {
        // Transaction for consistency
        $conn->begin_transaction();
        try {
            // Lock and fetch current hash
            $sel = $conn->prepare("SELECT password FROM users WHERE user_id = ? FOR UPDATE");
            $sel->bind_param("i", $user_id);
            $sel->execute();
            $selRes = $sel->get_result();
            $row = $selRes->fetch_assoc();
            $sel->close();

            if (!$row) {
                $conn->rollback();
                $message = "User not found.";
                $message_type = "error";
            } elseif (!password_verify($current_password, $row['password'])) {
                // WRONG PASSWORD → show message
                $conn->rollback();
                $message = "Current password is incorrect.";
                $message_type = "error";
            } elseif (password_verify($new_password, $row['password'])) {
                // Prevent reusing the same password
                $conn->rollback();
                $message = "New password must be different from your current password.";
                $message_type = "error";
            } else {
                $newHash = password_hash($new_password, PASSWORD_DEFAULT);

                // If you don't have updated_at, remove it from the query
                $upd = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
                $upd->bind_param("si", $newHash, $user_id);
                $ok = $upd->execute();
                $upd->close();

                if ($ok) {
                    $conn->commit();

                    // Optional: refresh local hash if kept in memory
                    $user_data['password'] = $newHash;

                    // SECURITY: end session and redirect to login
                    session_regenerate_id(true);
                    $_SESSION = [];
                    if (ini_get("session.use_cookies")) {
                        $params = session_get_cookie_params();
                        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
                    }
                    session_destroy();

                    header("Location: login.php?password_changed=1");
                    exit();
                } else {
                    $conn->rollback();
                    $message = "Error updating password: " . $conn->error;
                    $message_type = "error";
                }
            }
        } catch (Throwable $e) {
            $conn->rollback();
            $message = "Error updating password.";
            $message_type = "error";
            // Optionally log $e->getMessage()
        }
    }
}
}

// Close MySQLi connection (optional at script end)
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MBK GlamHub | Profile Settings</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="icon" type="image/png" href="mbk_logo.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Use the same palette as your homepage -->
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

  <!-- Brand helpers -->
  <style>
    .gradient-text {
      background: linear-gradient(to right, #a06c9e, #4b2840);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .gradient-bg {
      background: linear-gradient(to right, #a06c9e, #4b2840);
      transition: filter .2s ease;
    }
    .gradient-bg:hover { filter: brightness(0.95); }
    html { scroll-behavior: smooth; }
  </style>
</head>

<body class="min-h-screen bg-white opacity-0 translate-y-4 transition-all duration-700 ease-out" onload="document.body.classList.remove('opacity-0','translate-y-4')">

  <!-- Header -->
  <header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-lavender-200">
    <div class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <a href="homepage.php">
            <img src="mbk_logo.png" alt="Make up By Kyleen Logo" class="h-14 w-auto">
          </a>
        </div>

        <nav class="hidden md:flex items-center space-x-8">
          <a href="homepage.php#services" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Services</a>
          <a href="homepage.php#about" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">About</a>
          <a href="artist_portfolio.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Portfolio</a>
          <a href="reviews.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Reviews</a>

 <!-- Dropdown -->
<div class="relative group inline-block text-left">
  <span class="gradient-bg text-white px-6 py-2 rounded-md font-medium transition-all inline-flex items-center gap-2 cursor-pointer">
    Hello, <?php echo htmlspecialchars($greetingName ?? 'Guest'); ?>
    <i class="fas fa-chevron-down text-white text-xs"></i>
  </span>

  <div class="absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-md shadow-lg opacity-0 group-hover:opacity-100 invisible group-hover:visible transition-all z-50">
    <?php if (!empty($isLoggedIn)): ?>
      <a 
        href="appointments.php" 
        class="block px-4 py-2 transition-all duration-200 
        <?php echo (basename($_SERVER['PHP_SELF']) === 'appointments.php') 
          ? 'bg-lavender-100 text-[#4b2840] font-semibold border-l-4 border-[#a06c9e] pl-3' 
          : 'text-gray-700 hover:bg-gray-100 border-l-4 border-transparent pl-3'; ?>">
        My Appointments
      </a>

      <a 
        href="profile_settings.php" 
        class="block px-4 py-2 transition-all duration-200 
        <?php echo (basename($_SERVER['PHP_SELF']) === 'profile_settings.php') 
          ? 'bg-lavender-100 text-[#4b2840] font-semibold border-l-4 border-[#a06c9e] pl-3' 
          : 'text-gray-700 hover:bg-gray-100 border-l-4 border-transparent pl-3'; ?>">
        Profile Settings
      </a>

      <a 
        href="logout.php" 
        class="block px-4 py-2 text-gray-700 hover:bg-gray-100 border-l-4 border-transparent pl-3 transition-all duration-200">
        Sign Out
      </a>
    <?php else: ?>
      <a href="login.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 border-l-4 border-transparent pl-3 transition-all duration-200">
        Log In
      </a>
    <?php endif; ?>
  </div>
</div>

        </nav>
      </div>
    </div>
  </header>

  <!-- Title band (white background) -->
  <section class="py-10 bg-white">
    <div class="container mx-auto px-4 text-center">


      <!-- size exactly as requested -->
      <h2 class="text-4xl lg:text-5xl font-bold mb-3 gradient-text">
        Profile Settings
      </h2>

      <!-- underline accent using same gradient -->
      <div class="mx-auto h-1.5 w-24 rounded-full gradient-bg"></div>
    </div>
  </section>

 

  <main class="container mx-auto px-4 py-10">
    <?php if (!empty($message)): ?>
      <script>
        document.addEventListener("DOMContentLoaded", function () {
          Swal?.fire?.({
            icon: '<?= $message_type === 'success' ? 'success' : 'error' ?>',
            title: '<?= $message_type === 'success' ? 'Success' : 'Oops!' ?>',
            text: <?= json_encode($message) ?>,
            confirmButtonColor: '#a06c9e',
            background: '#fff'
          });
        });
      </script>
    <?php endif; ?>

    <!-- Form card -->
    <div class="w-full max-w-3xl mx-auto bg-white rounded-2xl shadow-lg border border-lavender-100 p-8 space-y-10">
      <!-- Personal Information -->
      <section>
        <h3 class="text-xl md:text-2xl font-semibold text-plum-700 mb-6 border-b pb-3 border-lavender-100">
          Personal Information
        </h3>

        <form action="profile_settings.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
            <input
              type="text" id="first_name" name="first_name"
              value="<?= htmlspecialchars($user_data['first_name'] ?? '') ?>" required
              class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                     focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
          </div>

          <div>
            <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
            <input
              type="text" id="middle_name" name="middle_name"
              value="<?= htmlspecialchars($user_data['middle_name'] ?? '') ?>"
              class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                     focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
          </div>

          <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
            <input
              type="text" id="last_name" name="last_name"
              value="<?= htmlspecialchars($user_data['last_name'] ?? '') ?>" required
              class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                     focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
          </div>

          <div>
            <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">Birth Date</label>
            <input
              type="date" id="birth_date" name="birth_date"
              value="<?= htmlspecialchars($user_data['birth_date'] ?? '') ?>"
              class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                     focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
          </div>

          <div>
            <label for="sex" class="block text-sm font-medium text-gray-700 mb-1">Sex</label>
            <select
              id="sex" name="sex"
              class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                     focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
              <option value="">Select Sex</option>
              <option value="Male"   <?= (isset($user_data['sex']) && $user_data['sex'] === 'Male')   ? 'selected' : '' ?>>Male</option>
              <option value="Female" <?= (isset($user_data['sex']) && $user_data['sex'] === 'Female') ? 'selected' : '' ?>>Female</option>
              <option value="Other"  <?= (isset($user_data['sex']) && $user_data['sex'] === 'Other')  ? 'selected' : '' ?>>Other</option>
            </select>
          </div>

          <div>
            <label for="contact_no" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
            <input
              type="tel" id="contact_no" name="contact_no"
              value="<?= htmlspecialchars($user_data['contact_no'] ?? '') ?>" required
              class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                     focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
          </div>

          <div class="md:col-span-2">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <input
              type="email" id="email" name="email"
              value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required readonly
              class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                     focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white cursor-not-allowed">
          </div>

          <div class="md:col-span-2 flex justify-end">
            <button
              type="submit" name="update_profile"
              class="inline-flex items-center gap-2 py-2 px-6 rounded-md text-white gradient-bg shadow-sm focus:outline-none
                     focus:ring-2 focus:ring-offset-2 focus:ring-plum-500 transition-all">
              <i class="fa-solid fa-floppy-disk text-sm"></i>
              Save Changes
            </button>
          </div>
        </form>
      </section>
<!-- Change Password (completely hidden by default) -->
<section>
  <div class="flex justify-end mb-3">
    <button
      id="togglePassword"
      type="button"
      class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-white gradient-bg shadow-sm focus:outline-none
             focus:ring-2 focus:ring-offset-2 focus:ring-plum-500 transition-all"
      aria-expanded="false"
      aria-controls="passwordPanel">
      <i class="fa-solid fa-key text-sm"></i>
      Change Password
    </button>
  </div>

  <!-- Hidden panel -->
  <div
    id="passwordPanel"
    class="hidden border border-lavender-100 rounded-xl p-6 bg-white"
    role="region"
    aria-labelledby="togglePassword">

    <!-- Hidden title (appears when expanded) -->
    <h3 class="text-xl md:text-2xl font-semibold text-plum-700 mb-6 border-b pb-3 border-lavender-100">
      Change Password
    </h3>

    <form action="profile_settings.php" method="POST" class="space-y-6">
      <div>
        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
        <input
          type="password" id="current_password" name="current_password" required
          class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                 focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
      </div>
      <div>
        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
        <input
          type="password" id="new_password" name="new_password" required
          class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                 focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
      </div>
      <div>
        <label for="confirm_new_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
        <input
          type="password" id="confirm_new_password" name="confirm_new_password" required
          class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                 focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
      </div>

      <div class="flex items-center justify-between pt-2">
        <button
          type="button"
          id="cancelPassword"
          class="px-4 py-2 rounded-md border border-lavender-200 text-gray-700 hover:bg-lavender-50 transition">
          Cancel
        </button>

        <button
          type="submit" name="change_password"
          class="inline-flex items-center gap-2 py-2 px-6 rounded-md text-white gradient-bg shadow-sm focus:outline-none
                 focus:ring-2 focus:ring-offset-2 focus:ring-plum-500 transition-all">
          <i class="fa-solid fa-lock text-sm"></i>
          Update Password
        </button>
      </div>
    </form>
  </div>
</section>

    </div>
  </main>

  <!-- Toggle script for Change Password -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const toggleBtn = document.getElementById('togglePassword');
      const panel     = document.getElementById('passwordPanel');
      const cancelBtn = document.getElementById('cancelPassword');

      function togglePanel(open) {
        const willOpen = (typeof open === 'boolean') ? open : panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !willOpen);
        toggleBtn.setAttribute('aria-expanded', String(willOpen));
        toggleBtn.innerHTML = willOpen
          ? '<i class="fa-solid fa-xmark text-sm"></i> Close'
          : '<i class="fa-solid fa-key text-sm"></i> Change Password';
      }

      toggleBtn.addEventListener('click', () => togglePanel());
      cancelBtn?.addEventListener('click', () => togglePanel(false));
    });
  </script>
</body>
</html>
