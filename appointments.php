<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']); // ✅ Add this line

if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// DB connection
$conn = new mysqli("localhost", "root", "", "mbk_db");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// Fetch user's name
$user_stmt = $conn->prepare("SELECT first_name FROM users WHERE user_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$first_name = $user_result->fetch_assoc()['first_name'] ?? 'User';
$user_stmt->close();

$greetingName = $_SESSION['user_name'] ?? $first_name;

$reviewedBookingIds = [];
$reviewResult = $conn->query("SELECT booking_id FROM reviews WHERE user_id = $user_id");
if ($reviewResult) {
    while ($reviewRow = $reviewResult->fetch_assoc()) {
        $reviewedBookingIds[] = (int)$reviewRow['booking_id'];
    }
}

// Get filters
$search = $conn->real_escape_string($_GET['search'] ?? '');
$paymentFilter = $_GET['payment_filter'] ?? '';
$statusFilter = $_GET['status_filter'] ?? '';

$where = ["b.user_id = $user_id"];

if ($search !== '') {
    $like = "'%$search%'";
    $where[] = "(
        b.booking_address LIKE $like OR
        b.payment_status LIKE $like OR
        t.makeup_artist_name LIKE $like OR
        t.hairstylist_name LIKE $like OR
        (b.is_confirmed = 1 AND 'confirmed' LIKE $like) OR
        (b.is_confirmed = 0 AND 'pending' LIKE $like) OR
        DATE_FORMAT(b.booking_date, '%Y-%m-%d') LIKE $like OR
        DATE_FORMAT(b.booking_date, '%M %e, %Y') LIKE $like
    )";
}


if (in_array($paymentFilter, ['pending', 'paid'])) {
    $where[] = "b.payment_status = '$paymentFilter'";
}

if ($statusFilter === 'pending') {
    $where[] = "b.is_confirmed = 0";
} elseif ($statusFilter === 'confirmed') {
    $where[] = "b.is_confirmed = 1 
                AND (b.booking_date > CURDATE() 
                     OR (b.booking_date = CURDATE() AND b.booking_time >= CURTIME()))";
} elseif ($statusFilter === 'cancelled') {
    $where[] = "b.is_confirmed = 2";
} elseif ($statusFilter === 'completed') {
    $where[] = "b.is_confirmed = 3";
}



$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Final query with JOINs and filters
$sort = ($_GET['sort'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$sql = "
    SELECT 
        b.booking_id, b.booking_date, b.booking_time, b.booking_address, 
        b.payment_status, b.is_confirmed,
        b.payment_proof_path,
        COUNT(bc.client_id) AS client_count,
        t.name AS team_name, 
        m.first_name AS makeup_first_name, 
        m.last_name AS makeup_last_name, 
        m.bio AS makeup_bio,
        h.first_name AS hair_first_name, 
        h.last_name AS hair_last_name, 
        h.bio AS hair_bio,
        p.name AS service_type
    FROM bookings b
    JOIN booking_clients bc ON bc.booking_id = b.booking_id
    JOIN teams t ON bc.team_id = t.team_id
    LEFT JOIN users m ON t.makeup_artist_id = m.user_id
    LEFT JOIN users h ON t.hairstylist_id = h.user_id
    JOIN packages p ON b.package_id = p.package_id
    $whereSql
    GROUP BY b.booking_id
    ORDER BY b.booking_date $sort, b.booking_time $sort
";

$result = $conn->query($sql);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Appointments </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="icon" type="image/png" href="mbk_logo.png" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    
    <style>
        /* Define custom colors as CSS variables for consistency */
        :root {
            --lavender-50: #fafaff;
            --lavender-100: #f5f5fa;
            --lavender-200: #ececf7;
            --lavender-300: #e6e6fa;
            --lavender-400: #d8d1e8;
            --lavender-500: #c2b6d9;
            --lavender-600: #a79dbf;
            --lavender-700: #8e83a3;
            --lavender-800: #756a86;
            --lavender-900: #5d516c;

            --plum-50: #f9f2f7;
            --plum-100: #f1e3ef;
            --plum-200: #e0c5dc;
            --plum-300: #c89ac1;
            --plum-400: #a06c9e;
            --plum-500: #804f7e; /* Main plum color */
            --plum-600: #673f68;
            --plum-700: #4b2840;
            --plum-800: #3c1f33;
            --plum-900: #2c1726;
        }

        /* Apply custom colors using CSS variables */
        .bg-lavender-50 { background-color: var(--lavender-50); }
        .bg-lavender-100 { background-color: var(--lavender-100); }
        .bg-lavender-200 { background-color: var(--lavender-200); }
        .bg-lavender-300 { background-color: var(--lavender-300); }
        .bg-lavender-400 { background-color: var(--lavender-400); }
        .bg-lavender-500 { background-color: var(--lavender-500); }
        .bg-lavender-600 { background-color: var(--lavender-600); }
        .bg-lavender-700 { background-color: var(--lavender-700); }
        .bg-lavender-800 { background-color: var(--lavender-800); }
        .bg-lavender-900 { background-color: var(--lavender-900); }

        .text-lavender-50 { color: var(--lavender-50); }
        .text-lavender-100 { color: var(--lavender-100); }
        .text-lavender-200 { color: var(--lavender-200); }
        .text-lavender-300 { color: var(--lavender-300); }
        .text-lavender-400 { color: var(--lavender-400); }
        .text-lavender-500 { color: var(--lavender-500); }
        .text-lavender-600 { color: var(--lavender-600); }
        .text-lavender-700 { color: var(--lavender-700); }
        .text-lavender-800 { color: var(--lavender-800); }
        .text-lavender-900 { color: var(--lavender-900); }

        .border-lavender-200 { border-color: var(--lavender-200); }
        .border-lavender-300 { border-color: var(--lavender-300); }

        .bg-plum-50 { background-color: var(--plum-50); }
        .bg-plum-100 { background-color: var(--plum-100); }
        .bg-plum-200 { background-color: var(--plum-200); }
        .bg-plum-300 { background-color: var(--plum-300); }
        .bg-plum-400 { background-color: var(--plum-400); }
        .bg-plum-500 { background-color: var(--plum-500); }
        .bg-plum-600 { background-color: var(--plum-600); }
        .bg-plum-700 { background-color: var(--plum-700); }
        .bg-plum-800 { background-color: var(--plum-800); }
        .bg-plum-900 { background-color: var(--plum-900); }

        .text-plum-50 { color: var(--plum-50); }
        .text-plum-100 { color: var(--plum-100); }
        .text-plum-200 { color: var(--plum-200); }
        .text-plum-300 { color: var(--plum-300); }
        .text-plum-400 { color: var(--plum-400); }
        .text-plum-500 { color: var(--plum-500); }
        .text-plum-600 { color: var(--plum-600); }
        .text-plum-700 { color: var(--plum-700); }
        .text-plum-800 { color: var(--plum-800); }
        .text-plum-900 { color: var(--plum-900); }


        

        /* Custom Gradients */
  .gradient-text{
    background: linear-gradient(to right, #a06c9e, #4b2840);
    -webkit-background-clip:text; background-clip:text;
    -webkit-text-fill-color:transparent; color:transparent;
  }
  .gradient-bg{
    background: linear-gradient(to right, #a06c9e, #4b2840);
    transition: filter .2s ease;
  }
  .gradient-bg:hover{ filter: brightness(0.95); }
  html{ scroll-behavior: smooth; }
        .backdrop-blur {
            backdrop-filter: blur(12px);
        }

        /* Custom SweetAlert styling (for potential future use, as JS functions are removed) */
        .swal-popup-custom {
            border-radius: 1.5rem !important;
            border: 2px solid var(--lavender-200) !important;
        }
        .swal-title-custom {
            color: var(--plum-500) !important;
            font-family: 'Poppins', sans-serif;
        }
.swal-button-custom {
    background-color: #2563eb; /* blue-600 */
    color: white;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    transition: background-color 0.2s;
    width: 100%;
    cursor: pointer;
}
        .swal-button-custom:hover {
            background: linear-gradient(to right, var(--plum-600), var(--plum-700)) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(128, 79, 126, 0.3) !important;
        }
        .swal-cancel-button-custom {
            border-radius: 0.5rem !important;
            font-weight: 600 !important;
            padding: 0.75rem 1.5rem !important;
            border: 2px solid var(--lavender-300) !important;
            background: transparent !important;
            color: #6b7280 !important;
        }
        .swal-cancel-button-custom:hover {
            background: var(--lavender-100) !important;
            border-color: var(--lavender-500) !important;
        }

        /* Appointment card animation */
        .appointment-card {
            transition: all 0.3s ease;
        }
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(128, 79, 126, 0.15);
        }
    </style>
</head>
<body class="min-h-screen bg-white opacity-0 translate-y-4 transition-all duration-700 ease-out" onload="document.body.classList.remove('opacity-0','translate-y-4')">

<?php if (isset($_SESSION['success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?= $_SESSION['success'] ?>',
            confirmButtonColor: '#3085d6',
        });
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?= $_SESSION['error'] ?>',
            confirmButtonColor: '#e3342f',
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Header -->
<header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-lavender-200">
  <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
    <div class="flex items-center space-x-2">
      <a href="homepage.php"><img src="mbk_logo.png" alt="Make up By Kyleen Logo" class="h-14 w-auto"></a>
    </div>

      <nav class="hidden md:flex items-center space-x-8">
        <a href="services.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Services</a>
        <a href="#about" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">About</a>
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
<!-- Main Content -->
<main class="max-w-5xl mx-auto mt-6 px-4">
    <!-- Hero Section -->
    <div class="text-center mb-12">
        <div class="flex items-center justify-center space-x-2 mb-4">
            <i class="fas fa-calendar-check text-3xl text-plum-600"></i>
            <span class="text-4xl font-bold gradient-text">My Appointments</span>
        </div>
        <span class="inline-flex items-center bg-lavender-100 text-plum-700 px-4 py-2 rounded-full text-sm font-medium mb-6">
            <i class="fas fa-history mr-2"></i>
            View Your Booking History & Upcoming Sessions
        </span>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            Manage your beauty journey with ease. See all your scheduled and past appointments here.
        </p>
    </div>

<form method="GET" class="flex flex-wrap gap-4 items-center mb-6">
    <!-- Search box -->
    <div class="relative">
        <input 
            type="text" 
            name="search" 
            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
            placeholder="Search appointments..." 
            class="pl-10 pr-4 py-2 border border-gray-300 rounded-md"
        />
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
    </div>

    <!-- Payment filter -->
    <select name="payment_filter" class="px-3 py-2 border border-gray-300 rounded-md">
        <option value="">All Payments</option>
        <option value="pending" <?= ($_GET['payment_filter'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="paid" <?= ($_GET['payment_filter'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
    </select>

    <!-- Status filter -->
    <select name="status_filter" class="px-3 py-2 border border-gray-300 rounded-md">
        <option value="">All Statuses</option>
        <option value="pending" <?= ($_GET['status_filter'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="confirmed" <?= ($_GET['status_filter'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="completed" <?= ($_GET['status_filter'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
        <option value="cancelled" <?= ($_GET['status_filter'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>

    <select name="sort" class="px-3 py-2 border border-gray-300 rounded-md">
    <option value="desc" <?= ($_GET['sort'] ?? '') === 'desc' ? 'selected' : '' ?>>Newest First</option>
    <option value="asc" <?= ($_GET['sort'] ?? '') === 'asc' ? 'selected' : '' ?>>Oldest First</option>
</select>

    <button type="submit" class="bg-plum-600 text-white px-4 py-2 rounded hover:bg-plum-700 transition">
        Apply
    </button>

</form>


    <!-- Appointments Container -->
    <?php if ($result->num_rows > 0): ?>
        <div class="space-y-6">
            <?php while($row = $result->fetch_assoc()): 

            
                // Determine status text and styling based on PHP data
                $statusClass = '';
                $statusText = '';
                $statusIcon = '';
                
                $bookingDateTime = new DateTime($row['booking_date'] . ' ' . $row['booking_time']);
                $now = new DateTime();

                switch ((int)$row['is_confirmed']) {
                    case 0: // Pending
                        $statusClass = 'bg-yellow-100 text-yellow-800';
                        $statusText = 'Pending';
                        $statusIcon = 'fas fa-hourglass-half';
                        break;
                    case 1: // Confirmed (future)
                        $statusClass = 'bg-green-100 text-green-800';
                        $statusText = 'Confirmed';
                        $statusIcon = 'fas fa-check-circle';
                        break;
                    case 2: // Cancelled
                        $statusClass = 'bg-red-100 text-red-800';
                        $statusText = 'Cancelled';
                        $statusIcon = 'fas fa-ban';
                        break;
                    case 3: // Completed
                        $statusClass = 'bg-blue-100 text-blue-800';
                        $statusText = 'Completed';
                        $statusIcon = 'fas fa-calendar-check';
                        break;
                    default:
                        $statusClass = 'bg-gray-100 text-gray-800';
                        $statusText = 'Unknown';
                        $statusIcon = 'fas fa-question-circle';
                        break;
                }

            ?>
                <div class="appointment-card bg-white rounded-3xl shadow-lg border border-lavender-200 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-2xl font-bold gradient-text">Booking #<?= htmlspecialchars($row['booking_id']) ?></h3>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusClass ?>">
                            <i class="<?= $statusIcon ?> mr-2"></i>
                            <?= $statusText ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700 text-sm mb-6">
                        <div>
                        <?php
                        $bookingDate = new DateTime($row['booking_date']);
                        $bookingTime = new DateTime($row['booking_time']);
                        ?>
                        <p class="mb-1">
                            <i class="fas fa-calendar-alt mr-2 text-plum-600"></i>
                            <strong>Date:</strong> <?= $bookingDate->format('F j, Y') ?>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-clock mr-2 text-plum-600"></i>
                            <strong>Time:</strong> <?= $bookingTime->format('g:i A') ?>
                        </p>

                            <p>
                                <i class="fas fa-users mr-2 text-plum-600"></i>
                                <strong>Clients:</strong> <?= htmlspecialchars($row['client_count']) ?>
                            </p>
                        </div>
                        <div>
                            <p class="mb-1">
                                <i class="fas fa-map-marker-alt mr-2 text-plum-600"></i>
                                <strong>Location:</strong> <?= htmlspecialchars($row['booking_address']) ?>
                            </p>
                            <p>
                                <i class="fas fa-credit-card mr-2 text-plum-600"></i>
                                <strong>Payment:</strong> <?= ucfirst(htmlspecialchars($row['payment_status'])) ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 border-t pt-4">
                        <h3 class="text-lg font-semibold text-lavender-600 mb-2">Glam Team: <?= htmlspecialchars($row['team_name']) ?></h3>
                        <?= htmlspecialchars($row['makeup_first_name'] . ' ' . $row['makeup_last_name']) ?>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($row['makeup_bio']) ?></p>

                        <?= htmlspecialchars($row['hair_first_name'] . ' ' . $row['hair_last_name']) ?>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($row['hair_bio']) ?></p>
                    </div>

<div class="mt-6 flex justify-end space-x-4">

<?php
$status = strtolower(trim($statusText));
$hasProof = !empty($row['payment_proof_path']); // check if payment proof exists
?>

<?php if ($status === 'pending' || $status === 'confirmed'): ?>
  <form method="POST" action="cancel_booking.php" class="cancel-form">
    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($row['booking_id']) ?>">
    <input type="hidden" name="status" value="<?= $status ?>">
    <button type="button"
      onclick="confirmCancel(this, <?= $hasProof ? 'true' : 'false' ?>)"
      class="cancel-btn bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md font-semibold shadow-sm transition-all">
      <i class="fas fa-times-circle mr-2"></i> Cancel
    </button>
  </form>
<?php endif; ?>


<?php
  // Build proof info
  $hasProof  = !empty($row['payment_proof_path']);
  // (A) safest: go through a protected endpoint
  $proofLink = 'view_proof.php?booking_id=' . (int)$row['booking_id'];
?>
<?php if ($hasProof): ?>
  <!-- Already paid / has proof: show a direct "View Payment Proof" button -->
  <a href="<?= htmlspecialchars($proofLink) ?>"
     target="_blank"
     class="bg-plum-600 hover:bg-plum-700 text-white px-4 py-2 rounded-md font-semibold shadow-sm transition-all inline-flex items-center">
    <i class="fas fa-image mr-2"></i> View Payment Proof
  </a>
<?php elseif ($statusText === 'Pending'): ?>
  <!-- No proof yet: show Pay Now -->
  <button
    onclick="openPaymentModal(<?= (int)$row['booking_id'] ?>, false)"
    class="gradient-bg text-white px-4 py-2 rounded-md font-semibold shadow-sm hover:shadow-md transition-all">
    <i class="fas fa-wallet mr-2"></i> Pay Now
  </button>
<?php endif; ?>




    <?php if ($status === 'completed'): 
        $alreadyReviewed = in_array((int)$row['booking_id'], $reviewedBookingIds);
    ?>
        <input type="hidden" name="booking_id" value="<?= htmlspecialchars($row['booking_id']) ?>">
        <button 
            onclick="<?= $alreadyReviewed ? 'return false;' : "openReviewModal(
                '".htmlspecialchars($greetingName)."',
                '".htmlspecialchars($row['service_type'])."',
                '".htmlspecialchars($row['makeup_first_name'] . ' ' . $row['makeup_last_name'])."',
                '".htmlspecialchars($row['hair_first_name'] . ' ' . $row['hair_last_name'])."',
                ".(int)$row['booking_id']."
            )" ?>"
            class="<?= $alreadyReviewed ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' ?> text-white px-4 py-2 rounded-md font-semibold shadow-sm transition-all"
            <?= $alreadyReviewed ? 'disabled' : '' ?>
        >
            <i class="fas fa-star mr-2"></i> <?= $alreadyReviewed ? 'Reviewed' : 'Submit a Review' ?>
        </button>
    <?php endif; ?>

</div>

                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-3xl shadow-lg border border-lavender-200 p-8 text-center text-gray-600">
            <i class="fas fa-box-open text-6xl text-lavender-400 mb-4"></i>
            <p class="text-xl font-semibold mb-2">No appointments found.</p>
        </div>
    <?php endif; ?>
</main>

<script>
function confirmCancel(button, hasProof) {
  const form = button.closest('form');

  // ——— CASE 1: Has payment proof ———
  if (hasProof) {
    Swal.fire({
      icon: 'info',
      title: 'Non-Refundable Payment',
      html: `
        <p class="text-gray-700 leading-relaxed text-sm">
          You've already submitted a payment proof for this booking.<br><br>
          Please note that <strong>down payments are non-refundable</strong>.
          <br>If you wish to cancel, you will not receive a refund.
        </p>
      `,
      showConfirmButton: true,
      showCancelButton: true,
      confirmButtonText: 'Yes, Cancel Anyway',
      cancelButtonText: 'No, Keep Booking',
      allowOutsideClick: false,
      allowEscapeKey: true,
      reverseButtons: true,
      focusConfirm: false,
      customClass: {
        popup: 'swal-popup-custom',
        title: '!text-xl !font-semibold !text-plum-700',
        confirmButton:
          '!mt-3 !bg-gradient-to-r !from-[#a06c9e] !to-[#4b2840] !text-white !px-6 !py-2.5 !rounded-full !font-semibold hover:!opacity-90 focus:!ring-2 focus:!ring-plum-400 transition-all',
        cancelButton:
          '!mt-3 !border !border-lavender-300 !text-gray-600 !px-6 !py-2.5 !rounded-full hover:!bg-lavender-100 hover:!border-lavender-500 transition-all font-medium'
      },
      buttonsStyling: false,
      willClose: () => {
        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
      }
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      } else {
        // Force full cleanup on cancel/dismiss
        Swal.close();
        setTimeout(() => {
          document.querySelectorAll('.swal2-container').forEach(el => el.remove());
          document.body.classList.remove('swal2-shown', 'swal2-height-auto');
        }, 50);
      }
    });

    return;
  }

  // ——— CASE 2: No payment proof ———
  Swal.fire({
    icon: 'warning',
    title: 'Cancel Booking?',
    html: `
      <p class="text-gray-700 text-sm leading-relaxed">
        Are you sure you want to cancel this booking?<br>
        This action <strong>cannot be undone</strong>.
      </p>
    `,
    showCancelButton: true,
    confirmButtonText: 'Yes, Cancel Booking',
    cancelButtonText: 'No, Keep It',
    allowOutsideClick: false,
    allowEscapeKey: true,
    reverseButtons: true,
    focusConfirm: true,
    customClass: {
      popup: 'swal-popup-custom',
      title: '!text-xl !font-semibold !text-red-600',
      confirmButton:
        '!bg-red-600 !text-white !px-6 !py-2.5 !rounded-full font-semibold hover:!bg-red-700 focus:!ring-2 focus:!ring-red-300 transition-all',
      cancelButton:
        '!border !border-lavender-300 !text-gray-700 !px-6 !py-2.5 !rounded-full hover:!bg-lavender-100 hover:!border-lavender-500 transition-all font-medium'
    },
    buttonsStyling: false,
    willClose: () => {
      document.body.classList.remove('swal2-shown', 'swal2-height-auto');
    }
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    } else {
      // Force full cleanup on cancel/dismiss
      Swal.close();
      setTimeout(() => {
        document.querySelectorAll('.swal2-container').forEach(el => el.remove());
        document.body.classList.remove('swal2-shown', 'swal2-height-auto');
      }, 50);
    }
  });
}
</script>




<script>
function openReviewModal(userName, serviceType, muaName, hairstylistName, bookingId) {
  Swal.fire({
    title: `
      <h2 class="text-2xl lg:text-3xl font-bold gradient-text m-0 leading-tight">
        Submit Your Review
      </h2>
    `,
    html: `
      <form id="reviewForm" class="text-left text-sm space-y-3">
        <input type="hidden" name="booking_id" value="${bookingId}" />

        <div class="bg-lavender-50 rounded-2xl p-4 border border-lavender-200 shadow-sm">
          <p class="m-0"><strong>Name:</strong> ${escapeHtml(userName)}</p>
          <p class="m-0 mt-1"><strong>Service Type:</strong> ${escapeHtml(serviceType)}</p>
        </div>

        <div class="space-y-2">
          <label class="block font-semibold text-gray-800 m-0">
            Rating for ${escapeHtml(muaName)} (Makeup Artist)
          </label>
          <div class="star-rating flex items-center gap-1" data-target="mua_rating">
            ${generateStars('mua_rating')}
          </div>
          <input type="hidden" name="mua_rating" id="mua_rating" required />
          <label class="block font-medium text-gray-700 m-0">Comment</label>
          <textarea name="mua_comment" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2"
            style="--tw-ring-color:#4b2840;" rows="3" maxlength="600"
            placeholder="Share your experience with ${escapeHtml(muaName)}" required></textarea>
          <div class="flex justify-end text-xs text-gray-500">
            <span class="char-left" data-for="mua_comment">600</span> chars left
          </div>
        </div>

        <div class="space-y-2">
          <label class="block font-semibold text-gray-800 m-0">
            Rating for ${escapeHtml(hairstylistName)} (Hairstylist)
          </label>
          <div class="star-rating flex items-center gap-1" data-target="hair_rating">
            ${generateStars('hair_rating')}
          </div>
          <input type="hidden" name="hair_rating" id="hair_rating" required />
          <label class="block font-medium text-gray-700 m-0">Comment</label>
          <textarea name="hair_comment" class="w-full p-3 border rounded-md focus:outline-none focus:ring-2"
            style="--tw-ring-color:#4b2840;" rows="3" maxlength="600"
            placeholder="Share your experience with ${escapeHtml(hairstylistName)}" required></textarea>
          <div class="flex justify-end text-xs text-gray-500">
            <span class="char-left" data-for="hair_comment">600</span> chars left
          </div>
        </div>

        <div class="space-y-2">
          <label class="block font-semibold text-gray-800 m-0">Overall Experience</label>
          <div class="star-rating flex items-center gap-1" data-target="overall_rating">
            ${generateStars('overall_rating')}
          </div>
          <input type="hidden" name="overall_rating" id="overall_rating" required />
        </div>
      </form>
    `,
    showCancelButton: true,
    confirmButtonText: '<i class="fa-solid fa-paper-plane mr-2"></i> Submit Review',
    cancelButtonText: 'Cancel',

    // Allow dismissals
    allowEscapeKey: true,
    allowOutsideClick: true,
    stopKeydownPropagation: true,

    customClass: {
      popup: 'swal-popup-custom !pt-6',
      title: '!m-0 !mb-2',
      confirmButton:
        '!mt-4 !py-3 !px-6 !rounded-full !font-semibold !text-sm ' +
        '!bg-gradient-to-r !from-[#a06c9e] !to-[#4b2840] !text-white !shadow-md hover:!opacity-90',
      cancelButton:
        '!mt-4 !py-3 !px-6 !rounded-full !font-semibold !text-sm ' +
        '!border !border-lavender-300 !text-gray-600 hover:!bg-lavender-50',
    },
    buttonsStyling: false,

    didOpen: () => {
      setupStarHandlers();
      setupCounters();

      const formEl = document.getElementById('reviewForm');
      if (formEl) {
        formEl.addEventListener('submit', (e) => {
          e.preventDefault();
          Swal.clickConfirm();
        });
      }

      // ✅ Prevent click-through without blocking SweetAlert2's cancel handler
      const cancelBtn = Swal.getCancelButton();
      if (cancelBtn) {
        // keep ONLY mousedown capture to stop the down event reaching the page
        cancelBtn.addEventListener('mousedown', (e) => e.stopPropagation(), true);
        // ❌ REMOVE the click-capture stopPropagation; let Swal receive the click
        // cancelBtn.addEventListener('click', (e) => e.stopPropagation(), true); // <-- delete this line if present
      }

      const container = Swal.getContainer();
      if (container) {
        // also stop outside mousedown from reaching page (prevents re-open on same click)
        container.addEventListener('mousedown', (e) => e.stopPropagation(), true);
      }
    },

    preConfirm: async () => {
      const form = document.getElementById('reviewForm');
      const fd = new FormData(form);

      const required = ['mua_rating','mua_comment','hair_rating','hair_comment','overall_rating'];
      for (const key of required) {
        if (!fd.get(key) || String(fd.get(key)).trim() === '') {
          Swal.showValidationMessage('Please complete all fields and select ratings.');
          return false;
        }
      }

      try {
        // Use manual loader but DO NOT disable cancel
        Swal.showLoading();
        const cancelBtn = Swal.getCancelButton();
        if (cancelBtn) cancelBtn.disabled = false;

        const res = await fetch('submit_review.php', { method: 'POST', body: fd });
        const text = (await res.text()).trim();

        let ok = false;
        try {
          const j = JSON.parse(text);
          ok = j?.success === true || j?.ok === true || /^ok$/i.test(j?.status || '');
        } catch {
          ok = /^(ok|success|true|1)$/i.test(text);
        }

        if (!res.ok || !ok) {
          Swal.showValidationMessage('Unable to submit right now. Please try again.');
          return false;
        }
        return true;
      } catch {
        Swal.showValidationMessage('Unable to submit right now. Please try again.');
        return false;
      }
    }
  }).then((r) => {
    if (r.isConfirmed) {
      Swal.fire({
        icon: 'success',
        title: 'Review submitted',
        text: 'Thank you for your feedback!',
        confirmButtonText: 'OK',
        customClass: { popup: 'swal-popup-custom', title: 'swal-title-custom' }
      }).then(() => window.location.reload());
    } else if (r.isDismissed) {
      // optional hard cleanup; SweetAlert2 normally handles this
      document.body.classList.remove('swal2-shown','swal2-height-auto');
      document.querySelectorAll('.swal2-container').forEach(el => el.remove());
    }
  });
}
  

// helpers
function generateStars(groupName) {
  let out = '';
  for (let i = 1; i <= 5; i++) {
    out += `
      <i class="fa fa-star text-lg text-gray-300 hover:text-yellow-400 cursor-pointer focus:outline-none"
         tabindex="0" role="radio" aria-checked="false" aria-label="${i} star"
         data-rating="${i}" data-name="${groupName}"></i>`;
  }
  return out;
}

function setupStarHandlers() {
  document.querySelectorAll('.star-rating').forEach(group => {
    const target = group.getAttribute('data-target');
    const input  = document.getElementById(target);
    const stars  = group.querySelectorAll('i[data-name="'+target+'"]');
    const paint = (n) => stars.forEach((st, idx) => {
      st.classList.toggle('text-yellow-400', idx < n);
      st.classList.toggle('text-gray-300', idx >= n);
      st.setAttribute('aria-checked', idx < n ? 'true' : 'false');
    });
    stars.forEach(star => {
      star.addEventListener('click', () => { const n = +star.dataset.rating; input.value = n; paint(n); });
      star.addEventListener('keydown', (e) => {
        let n = parseInt(input.value || '0', 10);
        if (e.key === 'ArrowRight') n = Math.min(5, n + 1);
        if (e.key === 'ArrowLeft')  n = Math.max(1, n - 1);
        if (e.key === 'Enter' || e.key === ' ') n = +star.dataset.rating;
        input.value = n; paint(n);
      });
    });
  });
}


function setupCounters() {
  document.querySelectorAll('textarea[name="mua_comment"], textarea[name="hair_comment"]').forEach(ta => {
    const max = parseInt(ta.getAttribute('maxlength') || '600', 10);
    const counter = ta.closest('div').querySelector('.char-left[data-for="'+ta.name+'"]');
    const set = () => { if (counter) counter.textContent = Math.max(0, max - ta.value.length); };
    ta.addEventListener('input', set); set();
  });
}

function escapeHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
</script>



</body>
</html>


<script>
function openPaymentModal(bookingId, hasProof, proofUrl = null) {



  Swal.fire({
    title: `
      <h2 class="text-2xl lg:text-3xl font-bold gradient-text mb-2 mt-0 leading-tight">
        Complete Your Payment
      </h2>
    `,
    html: `
      <div class="text-left text-gray-700 space-y-3 text-sm leading-relaxed">
        <!-- Booking Info -->
        <div class="bg-lavender-50 rounded-2xl p-4 border border-lavender-200 shadow-sm">
          <p><strong>Booking ID:</strong> ${bookingId}</p>
          <p class="mt-1">Please choose a payment method and accept the terms to continue.</p>
          <p class="mt-1"><strong>Amount:</strong> <span class="text-plum-600 font-semibold">₱1,500</span></p>
        </div>

        <!-- Payment Method -->
        <div>
          <label class="block font-semibold mb-2 text-gray-800">Payment Method</label>
          <div class="space-y-2">
            <label class="flex items-center gap-3 cursor-pointer hover:text-plum-600 transition">
              <input type="radio" name="paymentMethod" id="pmPayPal" value="paypal" class="accent-plum-600 w-4 h-4">
              <span><i class="fa-brands fa-paypal text-blue-600 mr-1"></i> PayPal</span>
            </label>
            <label class="flex items-center gap-3 cursor-pointer hover:text-plum-600 transition">
              <input type="radio" name="paymentMethod" id="pmGcash" value="gcash" class="accent-plum-600 w-4 h-4">
              <span><i class="fa-solid fa-qrcode text-plum-500 mr-1"></i> GCash</span>
            </label>
          </div>
        </div>

        <!-- GCash QR + Proof Upload -->
<div id="gcashArea" class="hidden space-y-3">
  <div class="rounded-xl border-dashed border-2 border-lavender-300 p-4 text-center bg-lavender-50">
    <div class="font-semibold text-gray-800">GCash QR Code</div>
    <p class="text-xs text-gray-500 mt-1">Scan this QR code to complete your payment.</p>

    <!-- ✅ Replace the old h-36 div with this -->
    <div class="mt-2 flex items-center justify-center">
      <img
        src="QRPay.jpg"
        alt="GCash QR"
        class="gcash-qr max-w-full h-auto object-contain rounded-lg border border-lavender-200"
        loading="lazy"
      />
    </div>
  </div>

          <div>
            <label class="block font-semibold mb-1 text-gray-800">Upload Proof of Payment (image)</label>
            <input type="file" id="gcashProof" accept="image/*"
                   class="block w-full border border-lavender-300 rounded-md p-2 bg-white">
            <p class="text-xs text-gray-500 mt-1">Accepted: JPG, PNG, GIF, WEBP. Max 5MB.</p>

            <!-- Preview -->
            <div id="proofPreviewWrap" class="hidden mt-2">
              <div class="text-xs text-gray-500 mb-1">Preview:</div>
              <img id="proofPreview" class="max-h-40 rounded border border-lavender-200" alt="Proof preview">
            </div>
          </div>
        </div>

        <!-- Non-refundable Checkbox -->
        <label class="flex items-start gap-3 mt-2 cursor-pointer">
          <input type="checkbox" id="nonRefundableAgree" class="mt-1 accent-plum-600 w-4 h-4">
          <span class="text-xs leading-5">
            I agree that the <strong>down payment is non-refundable</strong> once paid.
          </span>
        </label>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: '<i class="fa-solid fa-arrow-right mr-2"></i> Proceed',
    cancelButtonText: 'Cancel',
    allowEscapeKey: true,
    allowOutsideClick: () => !Swal.isLoading(),
    customClass: {
      popup: '!p-8 !rounded-3xl !border !border-lavender-200 !shadow-xl !max-w-md !pt-6',
      title: '!mt-0 !mb-2 !leading-tight',
      confirmButton:
        '!mt-5 !py-3 !px-8 !rounded-full !font-semibold !text-sm ' +
        '!bg-gradient-to-r !from-[#a06c9e] !to-[#4b2840] !text-white !shadow-md hover:!opacity-90',
      cancelButton:
        '!mt-5 !py-3 !px-8 !rounded-full !font-semibold !text-sm ' +
        '!border !border-lavender-300 !text-gray-600 hover:!bg-lavender-50',
    },
    buttonsStyling: false,
    reverseButtons: true,

    didOpen: () => {
      const agree = document.getElementById('nonRefundableAgree');
      const gcashArea = document.getElementById('gcashArea');
      const proof = document.getElementById('gcashProof');
      const previewWrap = document.getElementById('proofPreviewWrap');
      const previewImg = document.getElementById('proofPreview');
      const confirmBtn = Swal.getConfirmButton();
      const popup = Swal.getPopup();

      // Ensure built-in Cancel closes
      const cancelBtn = Swal.getCancelButton();
      if (cancelBtn) {
        cancelBtn.addEventListener('click', () => Swal.close(), { once: true });
      }

      // File preview + client-side validation
      proof?.addEventListener('change', () => {
        if (!proof.files || proof.files.length === 0) {
          previewWrap.classList.add('hidden');
          previewImg.removeAttribute('src');
          return;
        }
        const file = proof.files[0];
        const okTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!okTypes.includes(file.type)) {
          proof.value = '';
          previewWrap.classList.add('hidden');
          previewImg.removeAttribute('src');
          Swal.showValidationMessage('Invalid file type. Please upload JPG/PNG/GIF/WEBP.');
          return;
        }
        if (file.size > 5 * 1024 * 1024) { // 5MB
          proof.value = '';
          previewWrap.classList.add('hidden');
          previewImg.removeAttribute('src');
          Swal.showValidationMessage('File too large. Max 5MB.');
          return;
        }
        const reader = new FileReader();
        reader.onload = e => {
          previewImg.src = e.target.result;
          previewWrap.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
      });

      function updateUI() {
        const selected = document.querySelector('input[name="paymentMethod"]:checked');
        const wantsGCash = selected && selected.value === 'gcash';

        // Toggle gcash section + widen dialog a bit
        gcashArea.classList.toggle('hidden', !wantsGCash);
        popup.classList.toggle('wide', wantsGCash);

        // Confirm button label
        if (selected && selected.value === 'paypal') {
          confirmBtn.innerHTML = '<i class="fa-brands fa-paypal mr-2"></i> Proceed to PayPal';
        } else if (wantsGCash) {
          confirmBtn.innerHTML = '<i class="fa-solid fa-upload mr-2"></i> Upload Proof';
        } else {
          confirmBtn.innerHTML = '<i class="fa-solid fa-arrow-right mr-2"></i> Proceed';
        }

        // Enable only when terms accepted AND method chosen
        confirmBtn.disabled = !(agree.checked && selected);
      }

      document.querySelectorAll('input[name="paymentMethod"]').forEach(r => {
        r.addEventListener('change', updateUI);
      });
      agree.addEventListener('change', updateUI);
      updateUI();

      // stash for preConfirm
      Swal._gcashProofInput = proof;
    },

    preConfirm: async () => {
      const selected = document.querySelector('input[name="paymentMethod"]:checked');
      const agree = document.getElementById('nonRefundableAgree');

      if (!agree || !agree.checked) {
        Swal.showValidationMessage('Please accept the non-refundable terms to continue.');
        return false;
      }
      if (!selected) {
        Swal.showValidationMessage('Please select a payment method.');
        return false;
      }

      // PayPal flow: just open PayPal and resolve
      if (selected.value === 'paypal') {
        window.open('https://www.paypal.com/ncp/payment/Y49FTVAEHSRPY', '_blank');
        return true;
      }

      // GCash flow: require an image and upload
      const fileInput = Swal._gcashProofInput;
      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        Swal.showValidationMessage('Please select an image of your payment proof.');
        return false;
      }

      const fd = new FormData();
      fd.append('booking_id', bookingId);
      fd.append('gcash_proof', fileInput.files[0]);

      try {
        Swal.showLoading();
        // Keep cancel available during upload
        const cancelBtn = Swal.getCancelButton();
        if (cancelBtn) cancelBtn.disabled = false;

        const res = await fetch('upload_payment_proof.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.error || 'Upload failed');
        return true;
      } catch (e) {
        Swal.showValidationMessage(e.message || 'Upload failed. Please try again.');
        return false;
      }
    }
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        icon: 'success',
        title: 'Thank you!',
        text: 'Your proof has been uploaded. We will verify it shortly.',
        confirmButtonText: 'OK',
        customClass: { popup: 'swal-popup-custom', title: 'swal-title-custom' }
      }).then(() => window.location.reload());
    }
  });
}
</script>



<style>
/* --- Theme-aligned modal styles --- */
.swal-popup-custom {
  border-radius: 1rem !important;
  background: #ffffff !important; /* pure white */
  border: 1px solid var(--lavender-200) !important;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08) !important;
    padding: 0.5rem 1.25rem 1.25rem 1.25rem !important;
  width: 30rem !important; /* default width */
  max-width: 90vw !important;
  animation: fadeSlideUp 0.35s ease-out;
  font-size: 1rem !important;
  line-height: 1.6 !important;
  transition: width 0.3s ease !important; /* smooth width change */
}

/* --- Wide mode when GCash is selected --- */
.swal-popup-custom.wide {
  width: 40rem !important; /* expanded width */
}

/* Remove any unwanted gaps */
.swal2-header {
  margin: 0 !important;
  padding: 0 !important;
}
.swal2-title {
  margin: 0 !important;
  padding: 0 !important;
  line-height: 1.1 !important;
}

.swal-title-custom {
  font-weight: 800 !important;
  letter-spacing: 0.3px !important;
  font-size: 1.9rem !important;
  margin: 0 !important;
  padding: 0 !important;
  line-height: 1.2 !important;
  color: #4b2840 !important; /* solid plum tone */
  text-align: center !important;
}

/* Content text */
.swal2-html-container {
  font-size: 1rem !important;
  color: #374151 !important;
  margin: 0.5rem 0 0 !important;
}
.swal2-html-container label {
  font-size: 0.95rem !important;
  color: #2f2f2f !important;
}
.swal2-html-container strong {
  font-weight: 700 !important;
  color: var(--plum-600) !important;
}
.swal2-html-container i {
  font-size: 1.1rem !important;
}

/* Buttons */
.swal-button-custom,
.swal-cancel-button-custom {
  font-size: 0.95rem !important;
  padding: 0.85rem 2rem !important;
  min-width: 120px !important;
  border-radius: 0.75rem !important;
  transition: all 0.15s ease !important;
}
.swal-button-custom {
  font-weight: 700 !important;
  background: linear-gradient(to right, var(--plum-500), var(--plum-600)) !important;
  color: #fff !important;
  border: none !important;
}
.swal-button-custom:not(:disabled):hover {
  transform: translateY(-1px) !important;
  filter: brightness(1.05) !important;
}
.swal-cancel-button-custom {
  border: 2px solid var(--lavender-300) !important;
  background: transparent !important;
  color: #6b7280 !important;
}
.swal-cancel-button-custom:hover {
  background: var(--lavender-100) !important;
  border-color: var(--plum-400) !important;
  color: var(--plum-700) !important;
}

/* Inputs */
.swal2-input {
  border: 1px solid var(--lavender-300) !important;
  border-radius: 0.5rem !important;
}

/* QR area */
#gcashQrArea .border-dashed {
  border-color: var(--plum-500) !important;
}
#gcashQrArea #gcash-qr-slot {
  font-size: 1rem !important;
  color: #6b6b6b !important;
}

/* Button alignment */
.swal2-actions {
   margin-top: 1rem !important;   /* was 1.75rem */
  gap: 0.75rem !important;       /* was 1.25rem */
  justify-content: center !important;
}

/* Smooth slide-up animation */
@keyframes fadeSlideUp {
  from {
    opacity: 0;
    transform: translateY(25px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}


</style>
