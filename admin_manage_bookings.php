<?php
session_start();
require 'db.php';


if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Dummy user ID
    $_SESSION['role'] = 'admin'; // Dummy role
    $_SESSION['user_name'] = 'Admin User'; // Dummy user name
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$greetingName = $_SESSION['user_name'] ?? 'Admin';

$conn = new mysqli("localhost", "root", "", "mbk_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Confirm booking
if (isset($_GET['confirm']) && is_numeric($_GET['confirm'])) {
    $bookingId = intval($_GET['confirm']);
    $conn->query("UPDATE bookings SET is_confirmed = 1, payment_status = 'paid' WHERE booking_id = $bookingId");
    header("Location: admin_manage_bookings.php");
    exit;
}

// Complete booking
if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    $bookingId = intval($_GET['complete']);
    $conn->query("UPDATE bookings SET is_confirmed = 3 WHERE booking_id = $bookingId");
    header("Location: admin_manage_bookings.php");
    exit;
}

// Counts
$pendingPayments = $conn->query("SELECT COUNT(*) AS count FROM bookings WHERE payment_status = 'pending'")->fetch_assoc()['count'];
$pendingBookings = $conn->query("SELECT COUNT(*) AS count FROM bookings WHERE is_confirmed = 0")->fetch_assoc()['count'];
$completedBookings = $conn->query("SELECT COUNT(*) AS count FROM bookings WHERE is_confirmed = 3")->fetch_assoc()['count'];

// Search filter
$search = $conn->real_escape_string($_GET['search'] ?? '');
$paymentFilter = $_GET['payment_filter'] ?? '';
$statusFilter = $_GET['status_filter'] ?? '';

$where = [];
if ($search !== '') {
    $where[] = "(
        CONCAT(users.first_name, ' ', IFNULL(users.middle_name, ''), ' ', users.last_name) LIKE '%$search%' 
        OR bookings.booking_address LIKE '%$search%'
        OR bookings.payment_status LIKE '%$search%'
        OR (bookings.is_confirmed = 1 AND 'confirmed' LIKE '%$search%')
        OR (bookings.is_confirmed = 0 AND 'pending' LIKE '%$search%')
        OR (bookings.is_confirmed = 3 AND 'completed' LIKE '%$search%')
        OR DATE_FORMAT(bookings.booking_date, '%M %e, %Y') LIKE '%$search%'
        OR DATE_FORMAT(bookings.booking_date, '%Y-%m-%d') LIKE '%$search%'
    )";
}
if (in_array($paymentFilter, ['pending', 'paid'])) {
    $where[] = "bookings.payment_status = '$paymentFilter'";
}
if ($statusFilter === 'pending') {
    $where[] = "bookings.is_confirmed = 0";
} elseif ($statusFilter === 'confirmed') {
    $where[] = "bookings.is_confirmed = 1";
} elseif ($statusFilter === 'completed') {
    $where[] = "bookings.is_confirmed = 3";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

$query = "
    SELECT 
        bookings.*, 
        users.first_name, 
        users.middle_name, 
        users.last_name 
    FROM bookings 
    JOIN users ON bookings.user_id = users.user_id 
    $whereSql
    ORDER BY booking_date DESC, booking_time DESC
";
$bookings = $conn->query($query);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Bookings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Fonts, Tailwind, Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        heading: ['Poppins', 'sans-serif'],
                        body: ['Inter', 'sans-serif']
                    },
                    colors: {
                        lavender: {
                            50: '#F8F5FA',
                            100: '#F0EBF5',
                            200: '#E0D6EB',
                            300: '#D0C1E1',
                            400: '#C0ACD7',
                            500: '#a06c9e', // Original lavender
                            600: '#804f7e', // Hover lavender
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
                            500: '#4b2840', // Original plum
                            600: '#673f68', // Hover plum
                            700: '#4A2D4B',
                            800: '#2E1B2E',
                            900: '#120912',
                        },
                        // New colors for gradients in cards, inspired by the "Purple" dashboard
                        'card-pink': '#FF6B6B',
                        'card-orange': '#FFA07A',
                        'card-blue': '#6A82FB',
                        'card-light-blue': '#FCFCFC', // Lighter blue for gradients
                        'card-green': '#2ECC71',
                        'card-light-green': '#A8E6CF',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-lavender-50 font-body overflow-x-hidden">
  <div class="flex min-h-screen w-full">

    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content Area -->
<div class="flex-1 flex flex-col bg-white rounded-tl-3xl shadow-inner overflow-y-auto">
    <?php include 'admin_header.php'; ?>

        <!-- Dashboard Content -->
   <main class="flex-1 p-8 ml-64">

            <h1 class="text-3xl font-heading font-bold text-plum-700 mb-8 flex items-center gap-3">
                <i class="fas fa-home text-2xl"></i>
                Dashboard
            </h1>


            <!-- Booking Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <!-- Pending Payments Card -->
                <div class="p-6 rounded-xl shadow-md text-white bg-gradient-to-br from-card-pink to-card-orange relative overflow-hidden">
                    <div class="absolute inset-0 opacity-20">
                        <i class="fas fa-clock text-9xl absolute -bottom-8 -right-8"></i>
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-xl font-semibold mb-2">Pending Payments</h2>
                        <p class="text-4xl font-bold mt-2"><?= $pendingPayments ?></p>
                        <p class="text-sm opacity-80 mt-1">Needs attention</p>
                    </div>
                </div>
                <!-- Pending Bookings Card -->
                <div class="p-6 rounded-xl shadow-md text-white bg-gradient-to-br from-card-blue to-card-light-blue relative overflow-hidden">
                    <div class="absolute inset-0 opacity-20">
                        <i class="fas fa-calendar-alt text-9xl absolute -bottom-8 -right-8"></i>
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-xl font-semibold mb-2">Pending Bookings</h2>
                        <p class="text-4xl font-bold mt-2"><?= $pendingBookings ?></p>
                        <p class="text-sm opacity-80 mt-1">Awaiting confirmation</p>
                    </div>
                </div>
                <!-- Completed Bookings Card -->
                <div class="p-6 rounded-xl shadow-md text-white bg-gradient-to-br from-card-green to-card-light-green relative overflow-hidden">
                    <div class="absolute inset-0 opacity-20">
                        <i class="fas fa-check-circle text-9xl absolute -bottom-8 -right-8"></i>
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-xl font-semibold mb-2">Completed Bookings</h2>
                        <p class="text-4xl font-bold mt-2"><?= $completedBookings ?></p>
                        <p class="text-sm opacity-80 mt-1">Successfully finished</p>
                    </div>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="bg-white shadow-md rounded-xl overflow-x-auto border border-gray-200 p-6">
                <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">All Bookings</h2>
                <form method="GET" class="flex flex-wrap gap-4 items-center mb-6">
    <div class="relative">
        <input 
            type="text" 
            name="search" 
            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
            placeholder="Search bookings..." 
            class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lavender-300"
        />
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
    </div>

    <!-- Payment Status Filter -->
    <select name="payment_filter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none">
        <option value="">All Payments</option>
        <option value="pending" <?= ($_GET['payment_filter'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="paid" <?= ($_GET['payment_filter'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
    </select>

    <!-- Booking Status Filter -->
    <select name="status_filter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none">
        <option value="">All Statuses</option>
        <option value="pending" <?= ($_GET['status_filter'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="confirmed" <?= ($_GET['status_filter'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
    </select>

    <button type="submit" class="bg-plum-600 text-white px-4 py-2 rounded hover:bg-plum-700 transition">
        Apply
    </button>
</form>
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-lavender-50 text-plum-700 uppercase font-medium">
                        <tr>
                            <th class="px-6 py-3">Client Name</th>
                            <th class="px-6 py-3">Date <br> MM/DD/YY</th>
                            <th class="px-6 py-3">Time</th>
                            <th class="px-6 py-3">Address</th>
                            <th class="px-6 py-3">Payment</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-lavender-200">
                        <?php while ($row = $bookings->fetch_assoc()): ?>
                            <tr class="hover:bg-lavender-50">
                                <td class="px-6 py-4">
    <?= htmlspecialchars($row['first_name'] . 
        (!empty($row['middle_name']) ? ' ' . $row['middle_name'] : '') . 
        ' ' . $row['last_name']) ?>
</td>

                                <td class="px-6 py-4">
                                    <?= date("F j, Y", strtotime($row['booking_date'])) ?> <!-- Example: July 16, 2025 -->
                                </td>
                                <td class="px-6 py-4">
                                    <?= date("g:i A", strtotime($row['booking_time'])) ?> <!-- Example: 9:30 AM -->
                                </td>

                                <td class="px-6 py-4"><?= $row['booking_address'] ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold <?= $row['payment_status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' ?>">
                                        <?= ucfirst($row['payment_status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                        if ($row['is_confirmed'] == 0) {
                                            echo '<span class="text-blue-600 font-semibold">Pending</span>';
                                        } elseif ($row['is_confirmed'] == 1) {
                                            echo '<span class="text-green-600 font-semibold">Confirmed</span>';
                                        } elseif ($row['is_confirmed'] == 2) {
                                            echo '<span class="text-red-600 font-semibold">Cancelled</span>';
                                        } elseif ($row['is_confirmed'] == 3) {
                                            echo '<span class="text-gray-600 font-semibold">Completed</span>';
                                        }
                                    ?>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <?php if ($row['is_confirmed'] == 0): ?>
                                        <a href="?confirm=<?= $row['booking_id'] ?>" class="bg-green-500 hover:bg-green-600 text-white text-sm px-3 py-1 rounded-md transition">
                                            Confirm
                                        </a>
                                    <?php elseif ($row['is_confirmed'] == 1 && $row['payment_status'] === 'paid'): ?>
                                        <a href="?complete=<?= $row['booking_id'] ?>" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-3 py-1 rounded-md transition">
                                            Complete
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">—</span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
<script>
 document.addEventListener('DOMContentLoaded', function () {
    const currentPath = window.location.pathname;
    const currentPage = currentPath.substring(currentPath.lastIndexOf('/') + 1).replace('.php', '').toLowerCase();

    document.querySelectorAll('.sidebar-link').forEach(link => {
        const page = link.getAttribute('data-page').toLowerCase();

        if (page === currentPage) {
            link.classList.add('bg-lavender-100', 'text-plum-700', 'shadow-sm');
            link.classList.remove('text-gray-700');
            const icon = link.querySelector('i');
            if (icon) icon.classList.add('text-plum-700');
        } else {
            link.classList.add('text-gray-700', 'hover:bg-lavender-100', 'hover:text-plum-700');
            const icon = link.querySelector('i');
            if (icon) icon.classList.remove('text-plum-700');
        }
});
});

</script>