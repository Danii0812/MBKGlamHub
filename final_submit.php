<?php
session_start();



$isLoggedIn = isset($_SESSION['user_id']);
$greetingName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';

$conn = new mysqli("localhost", "root", "", "mbk_db");
if ($conn->connect_error) {
    die("Database error: " . $conn->connect_error);
}

$confirmed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_id = $_SESSION['selected_team_id'] ?? null; // Use session team_id if available
    if (isset($_POST['team_id'])) { // Fallback to POST if session not set (e.g., direct access)
        $team_id = intval($_POST['team_id']);
    }

    $package_id = intval($_POST['package_id']);

    if (!isset($_SESSION['booking_id'])) {
        // This might happen if the user refreshes or accesses directly after clearing session
        // For now, we'll just show the error message.
        // In a real app, you might redirect to a booking start page.
    } else {
        $booking_id = $_SESSION['booking_id'];

        // Update team_id for all booking clients under this booking
        // Note: This assumes booking_clients are already created in a previous step
        $stmt1 = $conn->prepare("UPDATE booking_clients SET team_id = ? WHERE booking_id = ?");
        if ($stmt1) {
            $stmt1->bind_param("ii", $team_id, $booking_id);
            $stmt1->execute();
            $stmt1->close();
        } else {
            // Handle prepare error
            error_log("Prepare failed for booking_clients update: " . $conn->error);
        }

        // Update booking with selected package and confirm the booking
        $stmt2 = $conn->prepare("UPDATE bookings SET package_id = ?, is_confirmed = 0 WHERE booking_id = ?");
        if ($stmt2) {
            $stmt2->bind_param("ii", $package_id, $booking_id);
            $stmt2->execute();
            if ($stmt2->affected_rows > 0) {
                $confirmed = true;
            }
            $stmt2->close();
        } else {
            // Handle prepare error
            error_log("Prepare failed for bookings update: " . $conn->error);
        }

        // Clear session variables related to booking
        unset($_SESSION['booking_id'], $_SESSION['selected_team_id'], $_SESSION['preferred_event'], $_SESSION['preferred_price'], $_SESSION['price_range_auto'], $_SESSION['client_count']);
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
 <!-- Multi-Step Progress Indicator -->
<div class="w-full max-w-4xl mx-auto mt-8 mb-10 px-4">
    <div class="flex items-center justify-center space-x-8">
        <!-- Step 1: Booking Details (Completed) -->
        <div class="flex flex-col items-center">
            <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white font-bold text-lg shadow-md">
                <i class="fas fa-check text-sm"></i>
            </div>
            <span class="mt-2 text-sm text-green-700 font-bold">Booking Details</span>
        </div>

        <!-- Line between steps -->
        <div class="flex-1 h-0.5 bg-gray-300"></div>

        <!-- Step 2: Team Selection (Completed) -->
        <div class="flex flex-col items-center">
            <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white font-bold text-lg shadow-md">
                <i class="fas fa-check text-sm"></i>
            </div>
            <span class="mt-2 text-sm text-green-700 font-bold">Team Selection</span>
        </div>

        <!-- Line between steps -->
        <div class="flex-1 h-0.5 bg-gray-300"></div>

        <!-- Step 3: Package Selection (Completed) -->
        <div class="flex flex-col items-center">
            <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white font-bold text-lg shadow-md">
                <i class="fas fa-check text-sm"></i>
            </div>
            <span class="mt-2 text-sm text-green-700 font-bold">Package Selection</span>
        </div>

        <!-- Line between steps -->
        <div class="flex-1 h-0.5 bg-gray-300"></div>

        <!-- Step 4: Confirmation (Active) -->
        <div class="flex flex-col items-center">
            <div class="w-10 h-10 rounded-full bg-plum-500 flex items-center justify-center text-white font-bold text-lg shadow-md">
                4
            </div>
            <span class="mt-2 text-sm text-plum-700 font-bold">Confirmation</span>
        </div>
    </div>
</div>


    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center p-4">
        <main class="w-full max-w-md p-8 bg-white rounded-xl shadow-lg border border-lavender-100 text-center">
            <?php if ($confirmed): ?>
                <i class="fas fa-check-circle text-green-500 text-6xl mb-6"></i>
                <h2 class="text-3xl font-heading font-bold text-plum-700 mb-4">Booking Confirmed!</h2>
                <p class="text-gray-700 text-lg mb-6">Your glam team and selected service package have been successfully saved.</p>
             <div class="flex flex-col sm:flex-row sm:justify-center gap-4">
                <a href="group_booking.php" class="w-full sm:w-auto bg-plum-600 hover:bg-plum-700 text-white py-3 px-8 rounded-lg hover:shadow-md transition-all text-lg font-semibold">
                    <i class="fas fa-plus-circle mr-2"></i>Book Another
                </a>
                <a href="appointments.php" class="w-full sm:w-auto bg-gray-200 text-gray-700 py-3 px-8 rounded-lg hover:bg-gray-300 transition-all text-lg font-semibold">
                    <i class="fas fa-calendar-alt mr-2"></i>Check My Appointments
                </a>
            </div>
            <?php else: ?>
                <i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-6"></i>
                <h2 class="text-3xl font-heading font-bold text-red-700 mb-4">Something went wrong.</h2>
                <p class="text-gray-600 text-lg mt-2 mb-6">There was an issue finalizing your booking. Please try again or contact support.</p>
                <a href="javascript:history.back();" class="inline-block w-full sm:w-auto bg-gray-200 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-300 transition-all text-lg font-semibold">
                    <i class="fas fa-arrow-left mr-2"></i>Go Back
                </a>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>