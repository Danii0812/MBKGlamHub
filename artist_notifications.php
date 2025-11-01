<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$notif_sql = "
SELECT
  b.booking_id,
  b.booking_date,
  b.booking_time,
  b.booking_address,
  COALESCE(p.name, '') AS package_name,
  COALESCE(p.event_type, '') AS package_event_type,
  GROUP_CONCAT(DISTINCT bc.client_name SEPARATOR ', ') AS clients
FROM bookings b
JOIN booking_clients bc ON bc.booking_id = b.booking_id
JOIN teams t ON bc.team_id = t.team_id
LEFT JOIN packages p ON b.package_id = p.package_id
WHERE (t.makeup_artist_id = ? OR t.hairstylist_id = ?)
GROUP BY b.booking_id, b.booking_date, b.booking_time, b.booking_address, p.name, p.event_type
ORDER BY b.booking_date DESC, b.booking_time DESC
";

$stmt = $conn->prepare($notif_sql);
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

$latest_notifications = [];
while ($row = $res->fetch_assoc()) {
    $clients = $row['clients'] ?: 'Client';
    $eventType = $row['package_event_type'] ?: '';
    $packageName = $row['package_name'] ?: 'Package';
    $timeDisplay = !empty($row['booking_time']) ? date("g:i A", strtotime($row['booking_time'])) : '';
    $dateDisplay = !empty($row['booking_date']) ? date("F j, Y", strtotime($row['booking_date'])) : '';

    $message = "New booking: {$clients}";
    if ($eventType) $message .= " — {$eventType}";
    if ($packageName) $message .= " ({$packageName})";

    $latest_notifications[] = [
        'message' => $message,
        'date' => $dateDisplay,
        'time' => $timeDisplay,
        'booking_id' => $row['booking_id']
    ];
}
$stmt->close();

if (empty($latest_notifications)) {
    $latest_notifications[] = [
        'message' => 'No new bookings at the moment.',
        'date' => date("F j, Y"),
        'time' => '',
        'booking_id' => null
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Notifications</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="mbk_logo.png" />
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
                        'card-pink': '#FF6B6B',
                        'card-orange': '#FFA07A',
                        'card-blue': '#6A82FB',
                        'card-light-blue': '#FCFCFC',
                        'card-green': '#2ECC71',
                        'card-light-green': '#A8E6CF',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="flex min-h-screen bg-lavender-50 font-body">
    <!-- Mobile Sidebar Toggle -->
    <button id="mobile-sidebar-toggle" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-plum-700 text-white rounded-md shadow-lg">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <?php include 'artist_sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col bg-white rounded-tl-3xl shadow-inner ml-64">


        <!-- Main -->
        <main class="flex-1 p-6 bg-white overflow-y-auto">
            <h1 class="text-3xl font-heading font-bold text-plum-700 mb-8 flex items-center gap-3">
                <i class="fas fa-bell text-2xl"></i> Notifications
            </h1>

            <div class="bg-white shadow-md rounded-xl overflow-hidden border border-gray-200 p-6">
                <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Your Notifications</h2>
                <div id="notifications-list" class="space-y-4">
                    <?php foreach ($latest_notifications as $notif): ?>
                        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm flex items-start gap-3">
                            <i class="fas fa-calendar-check text-plum-500 mt-1"></i>
                            <div>
                                <p class="text-gray-800"><?= htmlspecialchars($notif['message']) ?></p>
                                <?php if (!empty($notif['date'])): ?>
                                    <span class="text-sm text-gray-500"><?= htmlspecialchars($notif['date']) ?><?= $notif['time'] ? " — " . htmlspecialchars($notif['time']) : '' ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</div>

    <script>
        // Sidebar highlight
        document.addEventListener("DOMContentLoaded", () => {
            const currentPage = window.location.pathname.split("/").pop();
            document.querySelectorAll(".sidebar-link").forEach(link => {
                const pageName = link.getAttribute("href").split("/").pop();
                if (pageName === currentPage) {
                    link.classList.add("bg-lavender-100", "text-plum-700", "font-semibold");
                } else {
                    link.classList.remove("bg-lavender-100", "text-plum-700", "font-semibold");
                }
            });

            const mobileToggle = document.getElementById("mobile-sidebar-toggle");
            const sidebar = document.getElementById("sidebar");
            if (mobileToggle && sidebar) {
                mobileToggle.addEventListener("click", () => {
                    sidebar.classList.toggle("-translate-x-full");
                });
            }
        });
    </script>
</body>
</html>
