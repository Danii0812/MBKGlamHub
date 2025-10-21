<?php
session_start();
require 'db.php'; // <-- Make sure this connects to your database

// Dummy session (for testing)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['user_name'] = 'Admin User';
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$greetingName = $_SESSION['user_name'] ?? 'Admin';
$message = '';
$message_type = '';

$query = "SELECT * FROM site_settings LIMIT 1";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $site_settings = $result->fetch_assoc();
} else {
    $default_insert = "
        INSERT INTO site_settings (
            site_name, site_tagline, contact_email, contact_phone, business_address, business_hours,
            facebook_url, instagram_url, twitter_url, booking_enabled, maintenance_mode
        ) VALUES (
            'Make Up By Kyleen', 'Professional Makeup & Beauty Services', 
            'info@makeupbykyleen.com', '+1 (555) 123-4567',
            '123 Beauty Street, Glamour City, GC 12345',
            'Mon-Fri: 9AM-6PM, Sat: 10AM-4PM, Sun: Closed',
            'https://facebook.com/makeupbykyleen',
            'https://instagram.com/makeupbykyleen',
            'https://twitter.com/makeupbykyleen',
            1, 0
        )
    ";
    $conn->query($default_insert);
    $site_settings = $conn->query("SELECT * FROM site_settings LIMIT 1")->fetch_assoc();
}

/* --- Handle Update Form --- */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_site_settings'])) {
    $site_name = $_POST['site_name'];
    $site_tagline = $_POST['site_tagline'];
    $contact_email = $_POST['contact_email'];
    $contact_phone = $_POST['contact_phone'];
    $business_address = $_POST['business_address'];
    $business_hours = $_POST['business_hours'];
    $facebook_url = $_POST['facebook_url'];
    $instagram_url = $_POST['instagram_url'];
    $twitter_url = $_POST['twitter_url'];
    $booking_enabled = isset($_POST['booking_enabled']) ? 1 : 0;
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

    $update_query = "
        UPDATE site_settings SET
            site_name = ?,
            site_tagline = ?,
            contact_email = ?,
            contact_phone = ?,
            business_address = ?,
            business_hours = ?,
            facebook_url = ?,
            instagram_url = ?,
            twitter_url = ?,
            booking_enabled = ?,
            maintenance_mode = ?,
            updated_at = NOW()
        WHERE id = 1
    ";

    $stmt = $conn->prepare($update_query);
    $stmt->bind_param(
        "ssssssssiii",
        $site_name, $site_tagline, $contact_email, $contact_phone, $business_address,
        $business_hours, $facebook_url, $instagram_url, $twitter_url,
        $booking_enabled, $maintenance_mode
    );

    if ($stmt->execute()) {
        $message = "Site settings updated successfully!";
        $message_type = "success";

        // Refresh data after update
        $site_settings = $conn->query("SELECT * FROM site_settings LIMIT 1")->fetch_assoc();
    } else {
        $message = "Error updating site settings: " . $stmt->error;
        $message_type = "error";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Site Settings - Admin Dashboard</title>
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
                            500: '#a06c9e',
                            600: '#804f7e',
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
                            500: '#4b2840',
                            600: '#673f68',
                            700: '#4A2D4B',
                            800: '#2E1B2E',
                            900: '#120912',
                        },
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

        <!-- Site Settings Content -->
        <main class="flex-1 p-8 ml-64">

            <h1 class="text-3xl font-heading font-bold text-plum-700 mb-8 flex items-center gap-3">
                <i class="fas fa-cog text-2xl"></i>
                Site Settings
            </h1>

            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg
                    <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'; ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form action="admin_site_settings.php" method="POST" class="space-y-8">
                <!-- General Settings -->
                <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6">
                    <h2 class="text-xl font-heading font-bold text-plum-700 mb-6 border-b pb-3 border-lavender-100">General Settings</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                            <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($site_settings['site_name']) ?>" required
                                   class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-plum-500 focus:border-plum-500 sm:text-sm bg-white">
                        </div>
                        <div>
                            <label for="site_tagline" class="block text-sm font-medium text-gray-700 mb-1">Site Tagline</label>
                            <input type="text" id="site_tagline" name="site_tagline" value="<?= htmlspecialchars($site_settings['site_tagline']) ?>"
                                   class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-plum-500 focus:border-plum-500 sm:text-sm bg-white">
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6">
                    <h2 class="text-xl font-heading font-bold text-plum-700 mb-6 border-b pb-3 border-lavender-100">Contact Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                            <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($site_settings['contact_email']) ?>" required
                                   class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-plum-500 focus:border-plum-500 sm:text-sm bg-white">
                        </div>
                        <div>
                            <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                            <input type="tel" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($site_settings['contact_phone']) ?>"
                                   class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-plum-500 focus:border-plum-500 sm:text-sm bg-white">
                        </div>
                        <div class="md:col-span-2">
                            <label for="business_address" class="block text-sm font-medium text-gray-700 mb-1">Business Address</label>
                            <textarea id="business_address" name="business_address" rows="3"
                                      class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-plum-500 focus:border-plum-500 sm:text-sm bg-white"><?= htmlspecialchars($site_settings['business_address']) ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label for="business_hours" class="block text-sm font-medium text-gray-700 mb-1">Business Hours</label>
                            <input type="text" id="business_hours" name="business_hours" value="<?= htmlspecialchars($site_settings['business_hours']) ?>"
                                   class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-plum-500 focus:border-plum-500 sm:text-sm bg-white">
                        </div>
                    </div>
                </div>

                <!-- Social Media -->
                <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6">
                    <h2 class="text-xl font-heading font-bold text-plum-700 mb-6 border-b pb-3 border-lavender-100">Social Media Links</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="facebook_url" class="block text-sm font-medium text-gray-700 mb-1">Facebook URL</label>
                            <input id="facebook_url" name="facebook_url" value="<?= htmlspecialchars($site_settings['facebook_url']) ?>"
                                   class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-plum-500 focus:border-plum-500 sm:text-sm bg-white">
                        </div>
                        <div>
                            <label for="instagram_url" class="block text-sm font-medium text-gray-700 mb-1">Instagram URL</label>
                            <input id="instagram_url" name="instagram_url" value="<?= htmlspecialchars($site_settings['instagram_url']) ?>"
                                   class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-plum-500 focus:border-plum-500 sm:text-sm bg-white">
                        </div>
                        <div>
                            <label for="twitter_url" class="block text-sm font-medium text-gray-700 mb-1">Twitter URL</label>
                            <input id="twitter_url" name="twitter_url" value="<?= htmlspecialchars($site_settings['twitter_url']) ?>"
                                   class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-plum-500 focus:border-plum-500 sm:text-sm bg-white">
                        </div>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6">
                    <h2 class="text-xl font-heading font-bold text-plum-700 mb-6 border-b pb-3 border-lavender-100">System Settings</h2>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="booking_enabled" name="booking_enabled" <?= $site_settings['booking_enabled'] ? 'checked' : '' ?>
                                   class="h-4 w-4 text-plum-600 focus:ring-plum-500 border-gray-300 rounded">
                            <label for="booking_enabled" class="ml-2 block text-sm text-gray-900">
                                Enable Online Booking
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="maintenance_mode" name="maintenance_mode" <?= $site_settings['maintenance_mode'] ? 'checked' : '' ?>
                                   class="h-4 w-4 text-plum-600 focus:ring-plum-500 border-gray-300 rounded">
                            <label for="maintenance_mode" class="ml-2 block text-sm text-gray-900">
                                Maintenance Mode
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button type="submit" name="update_site_settings"
                            class="inline-flex justify-center py-3 px-8 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-plum-500 hover:bg-plum-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-plum-500 transition-all">
                        <i class="fas fa-save mr-2"></i>
                        Save Settings
                    </button>
                </div>
            </form>
        </main>
    </div>



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
</body>
</html>
