<?php
require_once 'db.php'; 

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
$role = htmlspecialchars($user['role']);

// Detect current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside id="sidebar" class="fixed top-0 left-0 h-screen w-64 bg-white shadow-lg flex flex-col justify-between py-8 px-4 z-50">
    <!-- Top: Logo & Profile -->
    <div>
        <!-- Logo/Admin Panel Title -->
        <div class="mb-8 px-3">
            <h2 class="text-2xl font-heading font-bold text-plum-700">Make Up By Kyleen</h2>
        </div>

        <!-- User Profile -->
        <div class="flex items-center gap-3 mb-8 px-3">
            <div class="w-12 h-12 bg-lavender-200 rounded-full flex items-center justify-center text-plum-700 text-xl">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-800"><?= $fullName ?></p>
                <p class="text-sm text-gray-500"><?= ucfirst($role) ?></p>
            </div>
        </div>
    </div>

    <!-- Middle: Scrollable Navigation -->
    <div class="flex-1 overflow-y-auto">
        <nav class="space-y-2" id="sidebar-nav">
            <a href="artist_dashboard.php"
            class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition 
            <?= $currentPage === 'artist_dashboard.php' ? 'bg-lavender-100 text-plum-700 shadow-sm font-bold' : 'text-gray-700 hover:bg-lavender-100 hover:text-plum-700' ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>

            <a href="artist_bookings.php"
            class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition 
            <?= $currentPage === 'artist_bookings.php' ? 'bg-lavender-100 text-plum-700 shadow-sm font-bold' : 'text-gray-700 hover:bg-lavender-100 hover:text-plum-700' ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Assigned Bookings</span>
            </a>

            <a href="artist_notifications.php"
            class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition 
            <?= $currentPage === 'artist_notifications.php' ? 'bg-lavender-100 text-plum-700 shadow-sm font-bold' : 'text-gray-700 hover:bg-lavender-100 hover:text-plum-700' ?>">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>

            <a href="artist_settings.php"
            class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition 
            <?= $currentPage === 'artist_settings.php' ? 'bg-lavender-100 text-plum-700 shadow-sm font-bold' : 'text-gray-700 hover:bg-lavender-100 hover:text-plum-700' ?>">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
        </nav>

    </div>

    <!-- Bottom: Sticky Logout Button -->
<!-- Logout -->
<div class="pt-4">
    <a href="#" id="logoutBtn" 
       class="flex items-center gap-3 px-4 py-3 hover:bg-red-100 hover:text-red-600 rounded-lg transition text-gray-700">
        <i class="fas fa-sign-out-alt"></i>
        <span>Log Out</span>
    </a>
</div>


</aside>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('logoutBtn').addEventListener('click', function(e) {
    e.preventDefault();

    Swal.fire({
        title: 'Are you sure?',
        text: "You’ll be logged out of your artist account.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#a06c9e',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, log me out',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirect to logout.php
            window.location.href = 'logout.php';
        }
    });
});
</script>