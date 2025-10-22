<?php
require 'db.php';

// ✅ Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Define variables safely
$greetingName = $_SESSION['user_name'] ?? 'Admin';
if (!isset($currentPage)) {
    $currentPage = isset($_SERVER['PHP_SELF']) ? basename($_SERVER['PHP_SELF']) : '';
}

// ✅ Fetch site name
$query = "SELECT site_name FROM site_settings LIMIT 1";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $siteName = $row['site_name'];
}
?>

<aside class="w-64 bg-white shadow-lg flex flex-col justify-between py-8 px-4 shrink-0 fixed top-0 left-0 h-screen overflow-y-auto">
    <div class="mb-8 px-3">
        <h2 class="text-2xl font-heading font-bold text-plum-700"><?= htmlspecialchars($siteName) ?></h2>
    </div>

    <!-- User Profile -->
    <div class="flex items-center gap-3 mb-6 px-3">
        <div class="w-12 h-12 bg-lavender-200 rounded-full flex items-center justify-center text-plum-700 text-xl">
            <i class="fas fa-user"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800"><?= htmlspecialchars($greetingName) ?></p>
            <p class="text-sm text-gray-500">Administrator</p>
        </div>
    </div>

    <!-- Nav Links -->
    <div class="flex-1 flex flex-col justify-between">
        <nav class="space-y-2 mb-4" id="sidebar-nav">
            <?php
            $links = [
                "admin_dashboard.php" => ["Dashboard", "fa-home"],
                "admin_manage_bookings.php" => ["Manage Bookings", "fa-calendar-check"],
                "admin_manage_teams.php" => ["Manage Teams", "fa-users"],
                "admin_manage_artists.php" => ["Manage Artists", "fa-users"],
                "admin_manage_packages.php" => ["Manage Packages", "fa-box-open"],
                "admin_reports.php" => ["Reports", "fa-chart-line"],
                "admin_site_settings.php" => ["Site Settings", "fa-cog"],
                "admin_reviews.php" => ["User Reviews", "fas fa-commenting"],
            ];

            foreach ($links as $file => [$label, $icon]) {
                $isActive = ($currentPage === $file);
                $activeClasses = $isActive
                    ? 'bg-lavender-100 text-plum-700 shadow-sm'
                    : 'text-gray-700 hover:bg-lavender-100 hover:text-plum-700';

                echo "
                <a href='$file' class='sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition $activeClasses'>
                    <i class='fas $icon'></i>
                    <span>$label</span>
                </a>";
            }
            ?>
        </nav>

        <!-- Logout -->
        <div class="pt-4">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 hover:bg-red-100 hover:text-red-600 rounded-lg transition text-gray-700">
                <i class="fas fa-sign-out-alt"></i>
                <span>Log Out</span>
            </a>
        </div>
    </div>
</aside>
