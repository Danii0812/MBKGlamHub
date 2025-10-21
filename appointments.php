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
    <title>My Appointments - Make Up By Kyleen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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

        /* Custom Fonts */
        .font-poppins { font-family: 'Poppins', sans-serif; }
        

        /* Custom Gradients */
        .gradient-text {
            background: linear-gradient(to right, var(--plum-500), var(--plum-600));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .gradient-bg {
            background: linear-gradient(to right, var(--plum-500), var(--plum-600));
        }
        .gradient-bg:hover {
            background: linear-gradient(to right, var(--plum-600), var(--plum-700));
        }
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
<body class="min-h-screen bg-gradient-to-br from-lavender-50 via-white to-plum-50 font-poppins">
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
                <!-- Logo / Site Name -->
                <div class="flex items-center space-x-2">
                    <a href ="homepage.php"><img src="logo.png" alt="Make up By Kyleen Logo" class="h-10 w-auto"></a>
                </div>
                <!-- Right Side: Nav + User -->
                <div class="flex items-center space-x-6">
                    <nav class="hidden md:flex items-center space-x-8">
                        <a href="homepage.php" class="text-gray-700 hover:text-plum-600 font-medium">Services</a>
                        <a href="#about" class="text-gray-700 hover:text-plum-600 font-medium">About</a>
                        <a href="artist_portfolio.php" class="text-gray-700 hover:text-plum-600 font-medium">Portfolio</a>
                        <a href="reviews.php" class="text-gray-700 hover:text-plum-600 font-medium">Reviews</a>
                    </nav>
                    <!-- User Dropdown -->
                    <div class="relative group inline-block text-left">
                        <span class="bg-plum-500 text-white px-6 py-2 rounded-md font-medium transition-all inline-block cursor-pointer">
                            Hello, <?= htmlspecialchars($greetingName) ?>
                            <i class="fas fa-chevron-down text-white text-sm ml-1"></i>
                        </span>
                        <div class="absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-md shadow-lg opacity-0 group-hover:opacity-100 invisible group-hover:visible transition-all z-50">
                            <?php if ($isLoggedIn): ?>
                                <a href="appointments.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">My Appointments</a>
                                <a href="profile_settings.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile settings</a>
                                <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Sign Out</a>
                            <?php else: ?>
                                <a href="login.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Log In</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
if ($status === 'pending' || $status === 'confirmed'): ?>
    <form method="POST" action="cancel_booking.php" class="cancel-form">
        <input type="hidden" name="booking_id" value="<?= htmlspecialchars($row['booking_id']) ?>">
        <input type="hidden" name="status" value="<?= $status ?>">
        <button type="button"
            class="cancel-btn bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md font-semibold shadow-sm transition-all">
            <i class="fas fa-times-circle mr-2"></i> Cancel
        </button>
    </form>
<?php endif; ?>


    <?php if ($statusText === 'Pending'): ?>
        <button onclick="openPaymentModal(<?= htmlspecialchars($row['booking_id']) ?>)" 
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
function openPaymentModal(bookingId) {
    Swal.fire({
        title: '<span class="swal-title-custom">Complete Your Payment</span>',
        html: `
            <div class="text-left text-gray-700 space-y-3 text-sm">
                <p><strong>Booking ID:</strong> ${bookingId}</p>
                <p>Please proceed with payment to confirm your appointment.</p>
                <p><strong>Amount:</strong> ₱1,500</p> <!-- You can dynamically insert actual amount -->
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Pay Now',
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'swal-popup-custom',
            title: 'swal-title-custom',
            confirmButton: 'swal-button-custom',
            cancelButton: 'swal-cancel-button-custom'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirect to PayPal payment link in new tab
            window.open('https://www.paypal.com/ncp/payment/Y49FTVAEHSRPY', '_blank');
        }
    });
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cancel-btn').forEach(button => {
        button.addEventListener('click', function () {
            const form = this.closest('form');
            const status = form.querySelector('input[name="status"]').value;

            let title = 'Cancel booking?';
            let text = 'This will remove your booking details.';
            let icon = 'warning';

            if (status === 'confirmed') {
                text = 'This booking is confirmed. The fee is non-refundable. Do you still want to cancel?';
            }

            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#e3342f',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>


<script>
function openReviewModal(userName, serviceType, muaName, hairstylistName, bookingId) {
    Swal.fire({
        title: '<span class="swal-title-custom">Submit Your Review</span>',
        html: `
            <form id="reviewForm" class="text-left text-sm space-y-4">
                <input type="hidden" name="booking_id" value="${bookingId}" />

                <div><strong>Name:</strong> ${userName}</div>
                <div><strong>Service Type:</strong> ${serviceType}</div>

                <div>
                    <label><strong>Rating for ${muaName} (Makeup Artist):</strong></label>
                    <div class="star-rating" data-target="mua_rating">${generateStars('mua_rating')}</div>
                    <input type="hidden" name="mua_rating" id="mua_rating" required />
                </div>

                <div>
                    <label><strong>Comment for ${muaName}:</strong></label>
                    <textarea name="mua_comment" class="w-full p-2 border rounded" required></textarea>
                </div>

                <div>
                    <label><strong>Rating for ${hairstylistName} (Hairstylist):</strong></label>
                    <div class="star-rating" data-target="hair_rating">${generateStars('hair_rating')}</div>
                    <input type="hidden" name="hair_rating" id="hair_rating" required />
                </div>

                <div>
                    <label><strong>Comment for ${hairstylistName}:</strong></label>
                    <textarea name="hair_comment" class="w-full p-2 border rounded" required></textarea>
                </div>

                <div>
                    <label><strong>Overall Experience:</strong></label>
                    <div class="star-rating" data-target="overall_rating">${generateStars('overall_rating')}</div>
                    <input type="hidden" name="overall_rating" id="overall_rating" required />
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Submit Review',
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'swal-popup-custom',
            title: 'swal-title-custom',
            confirmButton: 'swal-button-custom',
            cancelButton: 'swal-cancel-button-custom'
        },
        buttonsStyling: false,
        didOpen: () => {
            setupStarHandlers();
        },
        preConfirm: () => {
            const form = document.getElementById('reviewForm');
            const formData = new FormData(form);

            // Basic validation
            if (
                !formData.get('mua_rating') ||
                !formData.get('mua_comment') ||
                !formData.get('hair_rating') ||
                !formData.get('hair_comment') ||
                !formData.get('overall_rating')
            ) {
                Swal.showValidationMessage('Please fill out all fields and select ratings.');
                return false;
            }

            // Submit via fetch
            return fetch('submit_review.php', {
                method: 'POST',
                body: formData
            }).then(res => {
                if (!res.ok) throw new Error('Network error');
                return res.text();
            });
        }
    }).then(result => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Thank you!',
                text: 'Your review has been submitted.',
                customClass: { popup: 'swal-popup-custom', title: 'swal-title-custom' },
            }).then(() => window.location.reload());
        }
    });
}

// Generate star HTML
function generateStars(inputName) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        stars += `<i class="fa fa-star text-gray-400 hover:text-yellow-400 cursor-pointer" data-rating="${i}" data-name="${inputName}"></i>`;
    }
    return stars;
}

// Attach click handlers to stars
function setupStarHandlers() {
    document.querySelectorAll('.star-rating i').forEach(star => {
        star.addEventListener('click', function () {
            const rating = this.getAttribute('data-rating');
            const inputName = this.getAttribute('data-name');
            document.getElementById(inputName).value = rating;

            // Reset stars
            const group = document.querySelectorAll(`i[data-name="${inputName}"]`);
            group.forEach(st => {
                st.classList.remove('text-yellow-400');
                st.classList.add('text-gray-400');
            });

            // Highlight selected
            for (let i = 0; i < rating; i++) {
                group[i].classList.remove('text-gray-400');
                group[i].classList.add('text-yellow-400');
            }
        });
    });
}
</script>

</body>
</html>
