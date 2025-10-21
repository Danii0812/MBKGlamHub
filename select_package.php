<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$greetingName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';

$conn = new mysqli("localhost", "root", "", "mbk_db");
if ($conn->connect_error) die("DB connection failed");

// Get number of clients from session (ensure it's set earlier)
$num_clients = $_SESSION['client_count'] ?? 1;

// Auto-determine price range based on number of clients
if ($num_clients <= 5) {
    $price_range = 'Low';
} elseif ($num_clients <= 10) {
    $price_range = 'Medium';
} else {
    $price_range = 'High';
}

// Store in session for consistency
$_SESSION['price_range_auto'] = $price_range;

// Get event type from previous session step (e.g. "Wedding", "Debut", etc.)
$event_type = $_SESSION['preferred_event'] ?? 'Others';

// Fetch matching packages
$stmt = $conn->prepare("SELECT * FROM packages WHERE event_type = ? AND price_range = ?");
$stmt->bind_param("ss", $event_type, $price_range);
$stmt->execute();
$result = $stmt->get_result();
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
      <div class="flex items-center justify-center gap-6 py-8 px-4">
          <!-- Step 1: Booking Details (Completed) -->
          <div class="flex flex-col items-center">
              <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white text-lg font-bold shadow-md">
                  <i class="fas fa-check"></i>
              </div>
              <p class="mt-2 text-sm text-green-700 font-bold">Booking Details</p>
          </div>

          <!-- Divider -->
          <div class="w-8 h-0.5 bg-gray-300"></div>

          <!-- Step 2: Team Selection (Completed) -->
          <div class="flex flex-col items-center">
              <div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center text-white text-lg font-bold shadow-md">
                  <i class="fas fa-check"></i>
              </div>
              <p class="mt-2 text-sm text-green-700 font-bold">Team Selection</p>
          </div>

          <!-- Divider -->
          <div class="w-8 h-0.5 bg-gray-300"></div>

          <!-- Step 3: Package Selection (Active) -->
          <div class="flex flex-col items-center">
              <div class="w-10 h-10 rounded-full bg-plum-500 flex items-center justify-center text-white text-lg font-bold shadow-md">
                  3
              </div>
              <p class="mt-2 text-sm text-plum-700 font-bold">Package Selection</p>
          </div>

          <!-- Divider -->
          <div class="w-8 h-0.5 bg-gray-300"></div>

          <!-- Step 4: Confirmation (Upcoming) -->
          <div class="flex flex-col items-center">
              <div class="w-10 h-10 rounded-full border-2 border-gray-300 flex items-center justify-center text-gray-500 text-lg font-bold shadow-md">
                  4
              </div>
              <p class="mt-2 text-sm text-gray-500 font-medium">Confirmation</p>
          </div>
      </div>

    <!-- Main Content Container -->
    <div class="flex-1 flex items-center justify-center p-4">
        <main class="w-full max-w-3xl p-8 bg-white rounded-xl shadow-lg border border-lavender-100">
            <h2 class="text-3xl font-heading font-bold text-center text-plum-700 mb-6">Select Your Package</h2>
            <p class="text-center mb-8 text-gray-600">
                Showing packages for <strong class="text-plum-700"><?= htmlspecialchars($event_type) ?></strong> event that is appropriate for the number of clients.<br>
                Auto-selected price range: <span class="text-plum-700 font-semibold"><?= $price_range ?></span>
            </p>

            <form action="final_submit.php" method="POST" class="space-y-6">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="bg-lavender-50 rounded-xl p-6 shadow-sm hover:shadow-md transition-all border border-lavender-100">
                        <label class="flex items-start space-x-4 cursor-pointer">
                            <input type="radio" name="package_id" value="<?= $row['package_id'] ?>" required class="mt-1.5">
                            <div>
                                <h3 class="text-xl font-heading font-semibold text-plum-700"><?= htmlspecialchars($row['name']) ?></h3>
                                <p class="text-gray-700 mt-1"><?= htmlspecialchars($row['description']) ?></p>
                                <p class="text-sm mt-2 text-gray-500">Price: ₱<?= number_format($row['price'], 2) ?> | Range: <?= $row['price_range'] ?></p>
                            </div>
                        </label>
                    </div>
                <?php endwhile; ?>

                <?php if ($result->num_rows === 0): ?>
                    <p class="text-red-600 font-semibold text-center mt-6">No available packages for this event and client size.</p>
                <?php endif; ?>

                <div class="text-center mt-8">
                    <button type="submit" class="w-full bg-plum-500 hover:bg-plum-600 text-white font-semibold py-3 px-6 rounded-lg hover:shadow-md transition-all text-lg">
                        Confirm Package and Finalize Booking
                    </button>
                </div>


            </form>
        </main>
    </div>
</body>
</html>