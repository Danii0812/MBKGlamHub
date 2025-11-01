<?php
session_start();


$isLoggedIn = isset($_SESSION['user_id']);
$greetingName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';


// Get team_id from URL
$team_id = $_GET['team_id'] ?? null;

// Store booking id in session for final submit
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : null;
if ($booking_id) { $_SESSION['booking_id'] = $booking_id; }

// ✅ Extract values from session to use in logic
$selected_date = $_SESSION['selected_date'] ?? null;
$selected_time = $_SESSION['selected_time'] ?? null;

// ❗ Don't check $selected_date before it's defined!
if (!$team_id || !$selected_date || !$selected_time) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Missing Data</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-white text-gray-800 flex items-center justify-center">
        <div class="bg-white p-8 rounded-xl shadow-lg border border-red-200 text-center max-w-md">
            <h2 class="text-2xl font-bold text-red-700 mb-4">Missing Selection Data</h2>
            <p class="text-gray-700 mb-6">Please go back and select a date, time, and team.</p>
            <a href="javascript:history.back();" class="inline-block bg-red-100 text-red-700 font-medium px-6 py-3 rounded-lg hover:bg-red-200 transition">
                Go Back
            </a>
        </div>
    </body>
    </html>';
    exit();
}



$_SESSION['selected_team_id'] = $team_id;

$conn = new mysqli("localhost", "root", "", "mbk_db");
if ($conn->connect_error) die("DB connection failed");

// ✅ Check if the team is confirmed-booked at this date & time
$conflict_check = $conn->prepare("
    SELECT b.booking_id
    FROM bookings b
    JOIN booking_clients bc ON b.booking_id = bc.booking_id
    WHERE bc.team_id = ?
      AND b.booking_date = ?
      AND b.booking_time = ?
      AND b.is_confirmed = 1");
$conflict_check->bind_param("iss", $team_id, $selected_date, $selected_time);
$conflict_check->execute();
$conflict_result = $conflict_check->get_result();

if ($conflict_result->num_rows > 0):
    // ❌ Team is unavailable?
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="mbk_logo.png" />
  <title>MBK GlamHub | Team Selection </title>
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
<body class="min-h-screen bg-white text-gray-800">


<!-- Header -->
<header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-lavender-200">
  <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
    <div class="flex items-center space-x-2">
      <a href="homepage.php"><img src="mbk_logo.png" alt="Make up By Kyleen Logo" class="h-10 w-auto"></a>
    </div>

      <nav class="hidden md:flex items-center space-x-8">
        <a href="services.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Services</a>
        <a href="homepage.php#about" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">About</a>
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

    <div class="flex-1 flex flex-col items-center justify-center p-4">
           <!-- Step Indicator (Gradient Circles + Short Dividers) -->
<div class="mb-10 flex items-center justify-center text-gray-600 font-semibold">
  <!-- Step 1: Completed -->
  <div class="flex flex-col items-center">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-plum-500 to-lavender-400 flex items-center justify-center text-white text-lg font-bold mb-2 shadow-lg ring-4 ring-lavender-200/50">
      <i class="fas fa-check"></i>
    </div>
    <span class="text-plum-700 font-bold">Booking Details</span>
  </div>

  <!-- Divider -->
  <div class="w-10 h-1 bg-gradient-to-r from-lavender-300 to-lavender-100 mx-3 rounded-full"></div>

  <!-- Step 2: Active -->
  <div class="flex flex-col items-center">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-plum-500 to-lavender-400 flex items-center justify-center text-white text-lg font-bold mb-2 shadow-lg ring-4 ring-lavender-200/50">
      2
    </div>
    <span class="text-plum-700 font-bold">Team Selection</span>
  </div>

  <!-- Divider -->
  <div class="w-10 h-1 bg-gradient-to-r from-lavender-300 to-lavender-100 mx-3 rounded-full"></div>

  <!-- Step 3: Inactive -->
  <div class="flex flex-col items-center opacity-90">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-gray-200 to-gray-100 flex items-center justify-center text-gray-600 text-lg font-bold mb-2 shadow-inner border border-lavender-200">
      3
    </div>
    <span class="text-gray-600 font-medium">Select Package</span>
  </div>

  <!-- Divider -->
  <div class="w-10 h-1 bg-gradient-to-r from-lavender-300 to-lavender-100 mx-3 rounded-full"></div>

  <!-- Step 4: Inactive -->
  <div class="flex flex-col items-center opacity-90">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-gray-200 to-gray-100 flex items-center justify-center text-gray-600 text-lg font-bold mb-2 shadow-inner border border-lavender-200">
      4
    </div>
    <span class="text-gray-600 font-medium">Confirmation</span>
  </div>
</div>



        <main class="w-full max-w-xl p-8 bg-white rounded-xl shadow-lg border border-red-200 text-center">
            <h2 class="text-3xl font-heading font-bold text-red-700 mb-4">Team Unavailable</h2>
            <p class="text-gray-700 text-lg mb-4"> This team is already booked and confirmed on 
                <strong><?= htmlspecialchars(date('m/d/y', strtotime($selected_date))) ?></strong> 
                at <strong><?= htmlspecialchars(date('g:i A', strtotime($selected_time))) ?></strong>.
            </p>
            <p class="text-gray-600 mt-2 mb-6">Please choose another team or adjust your booking time.</p>
            <a href="javascript:history.back();" class="inline-block bg-red-100 text-red-700 font-medium px-6 py-3 rounded-lg hover:bg-red-200 transition-all shadow-sm">
                <i class="fas fa-arrow-left mr-2"></i>Go Back
            </a>
        </main>
    </div>
</body>
</html>
<?php
    exit();
endif;

$stmt = $conn->prepare("
    SELECT 
        t.team_id,
        t.name AS team_name,
        t.profile_image,
        -- Makeup artist
        ma.first_name AS ma_first_name,
        ma.middle_name AS ma_middle_name,
        ma.last_name AS ma_last_name,
        ma.bio AS ma_bio,
        ma.sex AS ma_sex,
        -- Hairstylist
        hs.first_name AS hs_first_name,
        hs.middle_name AS hs_middle_name,
        hs.last_name AS hs_last_name,
        hs.bio AS hs_bio,
        hs.sex AS hs_sex
    FROM teams t
    LEFT JOIN users ma ON t.makeup_artist_id = ma.user_id
    LEFT JOIN users hs ON t.hairstylist_id = hs.user_id
    WHERE t.team_id = ?
");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();

$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()):
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <link rel="icon" type="image/png" href="mbk_logo.png" />
  <title>MBK GlamHub | Team Selection </title>
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
<body class="min-h-screen bg-white text-gray-800">


<!-- Header -->
<header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-lavender-200">
  <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
    <div class="flex items-center space-x-2">
      <a href="homepage.php"><img src="mbk_logo.png" alt="Make up By Kyleen Logo" class="h-10 w-auto"></a>
    </div>

      <nav class="hidden md:flex items-center space-x-8">
        <a href="services.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Services</a>
        <a href="homepage.php#about" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">About</a>
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
      <div class="flex-1 flex flex-col items-center justify-center p-4">
<!-- Step Indicator (Gradient Circles + Short Dividers) -->
<div class="mb-10 flex items-center justify-center text-gray-600 font-semibold">
  <!-- Step 1: Completed -->
  <div class="flex flex-col items-center">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-plum-500 to-lavender-400 flex items-center justify-center text-white text-lg font-bold mb-2 shadow-lg ring-4 ring-lavender-200/50">
      <i class="fas fa-check"></i>
    </div>
    <span class="text-plum-700 font-bold">Booking Details</span>
  </div>

  <!-- Divider -->
  <div class="w-10 h-1 bg-gradient-to-r from-lavender-300 to-lavender-100 mx-3 rounded-full"></div>

  <!-- Step 2: Active -->
  <div class="flex flex-col items-center">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-plum-500 to-lavender-400 flex items-center justify-center text-white text-lg font-bold mb-2 shadow-lg ring-4 ring-lavender-200/50">
      2
    </div>
    <span class="text-plum-700 font-bold">Team Selection</span>
  </div>

  <!-- Divider -->
  <div class="w-10 h-1 bg-gradient-to-r from-lavender-300 to-lavender-100 mx-3 rounded-full"></div>

  <!-- Step 3: Inactive -->
  <div class="flex flex-col items-center opacity-90">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-gray-200 to-gray-100 flex items-center justify-center text-gray-600 text-lg font-bold mb-2 shadow-inner border border-lavender-200">
      3
    </div>
    <span class="text-gray-600 font-medium">Select Package</span>
  </div>

  <!-- Divider -->
  <div class="w-10 h-1 bg-gradient-to-r from-lavender-300 to-lavender-100 mx-3 rounded-full"></div>

  <!-- Step 4: Inactive -->
  <div class="flex flex-col items-center opacity-90">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-gray-200 to-gray-100 flex items-center justify-center text-gray-600 text-lg font-bold mb-2 shadow-inner border border-lavender-200">
      4
    </div>
    <span class="text-gray-600 font-medium">Confirmation</span>
  </div>
</div>



        <main class="w-full max-w-xl p-8 bg-white rounded-xl shadow-lg border border-lavender-100">
    <h2 class="text-3xl font-heading font-bold text-center text-plum-700 mb-6">
        Your Recommended Glam Team
    </h2>

    <!-- ✅ Show team profile image only if it exists -->
    <?php if (!empty($row['profile_image'])): ?>
        <div class="flex justify-center mb-4">
            <img 
                src="<?= htmlspecialchars($row['profile_image']) ?>" 
                alt="Team Profile Image" 
                class="w-32 h-32 object-cover rounded-full border-4 border-plum-200 shadow-md"
            >
        </div>
    <?php endif; ?>

    <div class="space-y-6">
        <div class="text-center">
            <h3 class="text-2xl font-heading font-semibold text-plum-700 mb-2">
                <?= htmlspecialchars($row['team_name']) ?>
            </h3>
            <p class="text-gray-600 text-sm">The perfect team for your event!</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Makeup Artist Card -->
            <div class="bg-lavender-50 p-4 rounded-lg shadow-sm border border-lavender-100">
                <p class="text-lg font-semibold text-gray-700 mb-2">
                    <i class="fas fa-paint-brush text-plum-500 mr-2"></i>Makeup Artist
                </p>
                <p class="text-plum-700 font-medium text-xl">
                    <?= htmlspecialchars($row['ma_first_name'] . ' ' . $row['ma_last_name']) ?>
                </p>
                <p class="text-gray-600 text-sm mt-1">
                    <?= htmlspecialchars($row['ma_bio'] ?: 'No bio available.') ?>
                </p>
                <p class="text-gray-500 text-xs mt-1">
                    (<?= htmlspecialchars($row['ma_sex'] ?: 'N/A') ?>)
                </p>
            </div>

            <!-- Hair Stylist Card -->
            <div class="bg-lavender-50 p-4 rounded-lg shadow-sm border border-lavender-100">
                <p class="text-lg font-semibold text-gray-700 mb-2">
                    <i class="fas fa-cut text-plum-500 mr-2"></i>Hair Stylist
                </p>
                <p class="text-plum-700 font-medium text-xl">
                    <?= htmlspecialchars($row['hs_first_name'] . ' ' . $row['hs_last_name']) ?>
                </p>
                <p class="text-gray-600 text-sm mt-1">
                    <?= htmlspecialchars($row['hs_bio'] ?: 'No bio available.') ?>
                </p>
                <p class="text-gray-500 text-xs mt-1">
                    (<?= htmlspecialchars($row['hs_sex'] ?: 'N/A') ?>)
                </p>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <a href="select_package.php"
           class="block w-full text-center bg-plum-500 text-white font-semibold py-3 px-6 rounded-lg hover:bg-plum-600 hover:shadow-md transition-all duration-300 text-lg flex items-center justify-center space-x-2">
            <span>Continue to Package Selection</span>
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</main>


    </div>
</body>
</html>
<?php else: ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="mbk_logo.png" />
  <title>MBK GlamHub | Team Selection </title>
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
<body class="min-h-screen bg-white text-gray-800">


<!-- Header -->
<header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-lavender-200">
  <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
    <div class="flex items-center space-x-2">
      <a href="homepage.php"><img src="mkb_logo.png" alt="Make up By Kyleen Logo" class="h-10 w-auto"></a>
    </div>

      <nav class="hidden md:flex items-center space-x-8">
        <a href="services.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Services</a>
        <a href="homepage.php#about" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">About</a>
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


    <div class="flex-1 flex flex-col items-center justify-center p-4">
<!-- Step Indicator (Gradient Circles + Short Dividers) -->
<div class="mb-10 flex items-center justify-center text-gray-600 font-semibold">
  <!-- Step 1: Completed -->
  <div class="flex flex-col items-center">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-plum-500 to-lavender-400 flex items-center justify-center text-white text-lg font-bold mb-2 shadow-lg ring-4 ring-lavender-200/50">
      <i class="fas fa-check"></i>
    </div>
    <span class="text-plum-700 font-bold">Booking Details</span>
  </div>

  <!-- Divider -->
  <div class="w-10 h-1 bg-gradient-to-r from-lavender-300 to-lavender-100 mx-3 rounded-full"></div>

  <!-- Step 2: Active -->
  <div class="flex flex-col items-center">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-plum-500 to-lavender-400 flex items-center justify-center text-white text-lg font-bold mb-2 shadow-lg ring-4 ring-lavender-200/50">
      2
    </div>
    <span class="text-plum-700 font-bold">Team Selection</span>
  </div>

  <!-- Divider -->
  <div class="w-10 h-1 bg-gradient-to-r from-lavender-300 to-lavender-100 mx-3 rounded-full"></div>

  <!-- Step 3: Inactive -->
  <div class="flex flex-col items-center opacity-90">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-gray-200 to-gray-100 flex items-center justify-center text-gray-600 text-lg font-bold mb-2 shadow-inner border border-lavender-200">
      3
    </div>
    <span class="text-gray-600 font-medium">Select Package</span>
  </div>

  <!-- Divider -->
  <div class="w-10 h-1 bg-gradient-to-r from-lavender-300 to-lavender-100 mx-3 rounded-full"></div>

  <!-- Step 4: Inactive -->
  <div class="flex flex-col items-center opacity-90">
    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-gray-200 to-gray-100 flex items-center justify-center text-gray-600 text-lg font-bold mb-2 shadow-inner border border-lavender-200">
      4
    </div>
    <span class="text-gray-600 font-medium">Confirmation</span>
  </div>
</div>





        <main class="w-full max-w-xl p-8 bg-white rounded-xl shadow-lg border border-red-200 text-center">
            <h2 class="text-3xl font-heading font-bold text-red-700 mb-4">Team Not Found</h2>
            <p class="text-gray-700 text-lg mb-4">The selected team could not be found.</p>
            <a href="javascript:history.back();" class="inline-block bg-red-100 text-red-700 font-medium px-6 py-3 rounded-lg hover:bg-red-200 transition-all shadow-sm">
                <i class="fas fa-arrow-left mr-2"></i>Go Back
            </a>
        </main>
    </div>
    
</body>
<style>
  .gradient-bg { background: linear-gradient(to right, #a06c9e, #4b2840); }
  .gradient-bg:hover { background: linear-gradient(to right, #804f7e, #673f68); }
  html { scroll-behavior: smooth; }
</style>

</html>
<?php endif; ?>