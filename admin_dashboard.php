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

try {

    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $totalBookings = $stmt->fetchColumn();

    // Active Teams
    $stmt = $pdo->query("SELECT COUNT(*) FROM teams");
    $activeTeams = $stmt->fetchColumn();

    //Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $newUsers = $stmt->fetchColumn();

    //recent bookings
$stmt = $pdo->query("
    SELECT 
        b.booking_id, 
        CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) AS user_name, 
        b.booking_date, 
        b.booking_time
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.user_id
    ORDER BY b.booking_id DESC
    LIMIT 5
");
$recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS with custom font and color config -->
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
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-lavender-50 font-body h-screen overflow-hidden flex">
    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col bg-white rounded-tl-3xl shadow-inner overflow-y-auto">
        <!-- Top Header / Navbar -->
        <?php include 'admin_header.php'; ?>

        <!-- Dashboard Content -->
        <main class="flex-1 p-8 ml-64">

            <h1 class="text-3xl font-heading font-bold text-plum-700 mb-8 flex items-center gap-3">
                <i class="fas fa-chart-line text-2xl"></i>
                Admin Dashboard
            </h1>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-card-blue to-card-light-blue text-white p-6 rounded-xl shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium opacity-80">Total Bookings</p>
                        <p class="text-3xl font-bold mt-1"><?= $totalBookings ?></p>
                    </div>
                    <i class="fas fa-calendar-alt text-4xl opacity-50"></i>
                </div>
                <div class="bg-gradient-to-br from-card-pink to-card-orange text-white p-6 rounded-xl shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium opacity-80">Active Teams</p>
                        <p class="text-3xl font-bold mt-1"><?= $activeTeams ?></p>
                    </div>
                    <i class="fas fa-users text-4xl opacity-50"></i>
                </div>
                <div class="bg-gradient-to-br from-card-green to-card-light-green text-white p-6 rounded-xl shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium opacity-80">Pending Reviews</p>
                        <p class="text-3xl font-bold mt-1">5</p> 
                    </div>
                    <i class="fas fa-star text-4xl opacity-50"></i>
                </div>
                <div class="bg-gradient-to-br from-plum-500 to-lavender-500 text-white p-6 rounded-xl shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium opacity-80">Total Users</p>
                        <p class="text-3xl font-bold mt-1"><?= $newUsers ?></p>
                    </div>
                    <i class="fas fa-user-plus text-4xl opacity-50"></i>
                </div>
            </div>

            <!-- Recent Activity / Quick Actions -->
            <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6">
                <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Recent Bookings</h2>
                <ul class="space-y-3 text-gray-700">
                    <?php if (!empty($recentBookings)): ?>
                        <?php foreach ($recentBookings as $booking): ?>
                            <li class="flex items-center justify-between border-b border-gray-100 pb-2">
                                <span>
                                    Booking #<?= $booking['booking_id'] ?> - <?= htmlspecialchars($booking['user_name']) ?>
                                </span>
                                <span class="text-sm text-gray-500">
                                    <?= date("M d, Y", strtotime($booking['booking_date'])) ?> 
                                    <?= $booking['booking_time'] ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="text-gray-500">No recent bookings</li>
                    <?php endif; ?>
                </ul>
                <a href="admin_manage_bookings.php" class="mt-4 inline-block text-plum-600 hover:text-plum-700 font-medium text-sm">
                    View All Bookings <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </a>
            </div>
            <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6">
                <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="admin_manage_teams.php" 
                    class="flex items-center justify-center p-4 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm text-plum-700 font-semibold text-center">
                        <i class="fas fa-user-plus mr-2"></i> Add New Team
                    </a>

                    <a href="manage_packages.php" 
                    class="flex items-center justify-center p-4 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm text-plum-700 font-semibold text-center">
                        <i class="fas fa-box-open mr-2"></i> Manage Packages
                    </a>

                    <a href="admin_reports.php" 
                    class="flex items-center justify-center p-4 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm text-plum-700 font-semibold text-center">
                        <i class="fas fa-chart-bar mr-2"></i> View Reports
                    </a>

                    <a href="reviews.php" 
                    class="flex items-center justify-center p-4 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm text-plum-700 font-semibold text-center">
                        <i class="fas fa-star mr-2"></i> Moderate Reviews
                    </a>
                </div>
            </div>


            </div>
        </main>
    </div>

    <script>
        // Sidebar active link logic
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = window.location.pathname.split('/').pop().split('.')[0];
            document.querySelectorAll('.sidebar-link').forEach(link => {
                const page = link.getAttribute('data-page');
                if (page === currentPage) {
                    link.classList.add('bg-lavender-100', 'text-plum-700', 'shadow-sm');
                    link.querySelector('i').classList.add('text-plum-700');
                    link.classList.remove('text-gray-700');
                } else {
                    link.classList.add('text-gray-700', 'hover:bg-lavender-100', 'hover:text-plum-700');
                    link.querySelector('i').classList.remove('text-plum-700');
                }
            });
        });


        // Update chat unread count
        async function updateChatUnreadCount() {
            try {
                const response = await fetch('api/chat_api.php?action=conversations');
                if (response.ok) {
                    const conversations = await response.json();
                    const totalUnread = conversations.reduce((sum, conv) => sum + (conv.unread_count || 0), 0);
                    
                    const badge = document.getElementById('chat-unread-badge');
                    if (totalUnread > 0) {
                        badge.textContent = totalUnread > 99 ? '99+' : totalUnread;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            } catch (error) {
                console.error('Error updating chat unread count:', error);
            }
        }

        // Update unread count on page load and every 30 seconds
        document.addEventListener('DOMContentLoaded', () => {
            updateChatUnreadCount();
            setInterval(updateChatUnreadCount, 30000);
        });

    </script>
</body>
</html>