<?php
session_start();


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mbk_db"; // Your database name

// Create connection
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
        $first_name = $_POST['first_name'];
        $middle_name = $_POST['middle_name'];
        $last_name = $_POST['last_name'];
        $birth_date = $_POST['birth_date'];
        $sex = $_POST['sex'];
        $contact_no = $_POST['contact_no'];
        $email = $_POST['email'];

        // Basic validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($contact_no)) {
            $message = "Please fill in all required fields.";
            $message_type = "error";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                // Re-fetch data to show updated values immediately
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
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];

        // Validate passwords
        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
            $message = "Please fill in all password fields.";
            $message_type = "error";
        } elseif ($new_password !== $confirm_new_password) {
            $message = "New password and confirmation do not match.";
            $message_type = "error";
        } elseif (strlen($new_password) < 8) { // Example: minimum 8 characters
            $message = "New password must be at least 8 characters long.";
            $message_type = "error";
        } else {
            // Verify current password
            if (password_verify($current_password, $user_data['password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password in DB
                $update_pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update_pass_stmt->bind_param("si", $hashed_password, $user_id);

                if ($update_pass_stmt->execute()) {
                    $message = "Password updated successfully!";
                    $message_type = "success";
                    // Update the user_data array with the new hashed password
                    $user_data['password'] = $hashed_password;
                } else {
                    $message = "Error updating password: " . $conn->error;
                    $message_type = "error";
                }
                $update_pass_stmt->close();
            } else {
                $message = "Current password is incorrect.";
                $message_type = "error";
            }
        }
    }
}

$conn->close();
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
      <a href="homepage.php"><img src="logo.png" alt="Make up By Kyleen Logo" class="h-10 w-auto"></a>
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

    <main class="flex-1 container mx-auto px-4 py-8 flex flex-col items-center">
        <h1 class="text-4xl font-heading font-bold text-plum-700 mb-8">Profile Settings</h1>

        <?php if ($message): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    Swal.fire({
                        icon: '<?= $message_type === 'success' ? 'success' : 'error' ?>',
                        title: '<?= $message_type === 'success' ? 'Success' : 'Oops!' ?>',
                        text: <?= json_encode($message) ?>,
                        confirmButtonColor: '#a06c9e',
                        background: '#fff',
                        customClass: {
                            popup: 'font-body'
                        }
                    });
                });
            </script>
        <?php endif; ?>


        <div class="w-full max-w-2xl bg-white rounded-xl shadow-lg border border-lavender-100 p-8 space-y-8">
            <!-- Personal Information Section -->
<div>
    <h2 class="text-2xl font-heading font-semibold text-plum-600 mb-6 border-b pb-3 border-lavender-100">Personal Information</h2>
    <form action="profile_settings.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user_data['first_name'] ?? '') ?>" required
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                       focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
        </div>
        <div>
            <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
            <input type="text" id="middle_name" name="middle_name" value="<?= htmlspecialchars($user_data['middle_name'] ?? '') ?>"
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                       focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
        </div>
        <div>
            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user_data['last_name'] ?? '') ?>" required
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                       focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
        </div>
        <div>
            <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">Birth Date</label>
            <input type="date" id="birth_date" name="birth_date" value="<?= htmlspecialchars($user_data['birth_date'] ?? '') ?>"
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                       focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
        </div>
        <div>
            <label for="sex" class="block text-sm font-medium text-gray-700 mb-1">Sex</label>
            <select id="sex" name="sex"
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                       focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
                <option value="">Select Sex</option>
                <option value="Male" <?= (isset($user_data['sex']) && $user_data['sex'] == 'Male') ? 'selected' : '' ?>>Male</option>
                <option value="Female" <?= (isset($user_data['sex']) && $user_data['sex'] == 'Female') ? 'selected' : '' ?>>Female</option>
                <option value="Other" <?= (isset($user_data['sex']) && $user_data['sex'] == 'Other') ? 'selected' : '' ?>>Other</option>
            </select>
        </div>
        <div>
            <label for="contact_no" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
            <input type="tel" id="contact_no" name="contact_no" value="<?= htmlspecialchars($user_data['contact_no'] ?? '') ?>" required
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                       focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
        </div>
        <div class="md:col-span-2">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required readonly
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                       focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white cursor-not-allowed">
        </div>
        <div class="md:col-span-2 flex justify-end">
            <button type="submit" name="update_profile"
                class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium 
                       rounded-md text-white bg-plum-500 hover:bg-plum-600 focus:outline-none 
                       focus:ring-2 focus:ring-offset-2 focus:ring-plum-500 transition-all">
                Save Changes
            </button>
        </div>
    </form>
</div>

<!-- Change Password Section -->
<div>
    <h2 class="text-2xl font-heading font-semibold text-plum-600 mb-6 border-b pb-3 border-lavender-100">Change Password</h2>
    <form action="profile_settings.php" method="POST" class="space-y-6">
        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
            <input type="password" id="current_password" name="current_password" required
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                       focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
        </div>
        <div>
            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
            <input type="password" id="new_password" name="new_password" required
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                       focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
        </div>
        <div>
            <label for="confirm_new_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
            <input type="password" id="confirm_new_password" name="confirm_new_password" required
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none
                       focus:ring-2 focus:ring-plum-500 focus:border-plum-300 sm:text-sm bg-white">
        </div>
        <div class="flex justify-end">
            <button type="submit" name="change_password"
                class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium 
                       rounded-md text-white bg-plum-500 hover:bg-plum-600 focus:outline-none 
                       focus:ring-2 focus:ring-offset-2 focus:ring-plum-500 transition-all">
                Change Password
            </button>
        </div>
    </form>
</div>



        </div>
    </main>
</body>
</html>