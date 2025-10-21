<?php
session_start();
include 'db.php';

// Get current artist user_id
$artist_id = $_SESSION['user_id'] ?? null;

if (!$artist_id) {
    header("Location: login.php");
    exit;
}


// === FETCH ARTIST PROFILE ===
$user_stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, birth_date, contact_no, bio 
                             FROM users 
                             WHERE user_id = ?");
$user_stmt->bind_param("i", $artist_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();


// === TOTAL BOOKINGS (artist-related only) ===
$total_query = $conn->query("
    SELECT COUNT(DISTINCT bc.booking_id) AS total
    FROM booking_clients bc
    JOIN teams t ON bc.team_id = t.team_id
    WHERE t.makeup_artist_id = $artist_id OR t.hairstylist_id = $artist_id
");
$total = $total_query->fetch_assoc()['total'] ?? 0;

// === ONGOING BOOKINGS ===
$ongoing_query = $conn->query("
    SELECT COUNT(DISTINCT bc.booking_id) AS ongoing
    FROM booking_clients bc
    JOIN teams t ON bc.team_id = t.team_id
    JOIN bookings b ON bc.booking_id = b.booking_id
    WHERE (t.makeup_artist_id = $artist_id OR t.hairstylist_id = $artist_id)
      AND b.is_confirmed IN (0, 1)
");
$ongoing = $ongoing_query->fetch_assoc()['ongoing'] ?? 0;

// === COMPLETED BOOKINGS ===
$completed_query = $conn->query("
    SELECT COUNT(DISTINCT bc.booking_id) AS completed
    FROM booking_clients bc
    JOIN teams t ON bc.team_id = t.team_id
    JOIN bookings b ON bc.booking_id = b.booking_id
    WHERE (t.makeup_artist_id = $artist_id OR t.hairstylist_id = $artist_id)
      AND b.is_confirmed = 3
");
$completed = $completed_query->fetch_assoc()['completed'] ?? 0;

$stats = [
    'total' => $total,
    'ongoing' => $ongoing,
    'completed' => $completed
];

// === FETCH ASSIGNED BOOKINGS ===
$bookings_query = $conn->query("
    SELECT 
        bc.client_name,
        b.booking_id,
        b.booking_date,
        b.booking_time,
        b.booking_address,
        b.is_confirmed,
        p.name AS package_name,
        p.event_type
    FROM booking_clients bc
    JOIN teams t ON bc.team_id = t.team_id
    JOIN bookings b ON bc.booking_id = b.booking_id
    JOIN packages p ON b.package_id = p.package_id
    WHERE (t.makeup_artist_id = $artist_id OR t.hairstylist_id = $artist_id)
    ORDER BY b.booking_date DESC
");

$assigned_bookings = [];
while ($row = $bookings_query->fetch_assoc()) {
    $assigned_bookings[] = $row;
}

// === FETCH ARTIST REVIEWS ===
$reviews_sql = "
SELECT 
    r.review_id,
    r.rating,
    r.comment,
    r.sentiment,
    r.created_at,
    r.is_verified,
    bc.client_name,
    b.booking_date,
    p.name AS package_name
FROM reviews r
JOIN bookings b ON r.booking_id = b.booking_id
JOIN booking_clients bc ON b.booking_id = bc.booking_id
JOIN teams t ON bc.team_id = t.team_id
LEFT JOIN packages p ON b.package_id = p.package_id
WHERE t.makeup_artist_id = ? OR t.hairstylist_id = ?
ORDER BY r.created_at DESC
";

$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param('ii', $artist_id, $artist_id);
$reviews_stmt->execute();
$reviews_res = $reviews_stmt->get_result();

$artist_reviews = [];
while ($row = $reviews_res->fetch_assoc()) {
    $artist_reviews[] = $row;
}
$reviews_stmt->close();

// === FETCH LATEST 3 BOOKINGS FOR NOTIFICATIONS (grouped by booking) ===
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
LIMIT 3
";

$stmt = $conn->prepare($notif_sql);
$stmt->bind_param('ii', $artist_id, $artist_id);
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

// Fallback if none found
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
    <title>Artist Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

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
<body class="flex min-h-screen bg-lavender-50 font-body">
    <!-- Mobile Sidebar Toggle -->
    <button id="mobile-sidebar-toggle" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-plum-700 text-white rounded-md shadow-lg">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <?php include 'artist_sidebar.php'; ?>
    <!-- Main Content Area -->
<div class="ml-64 flex-1 flex flex-col bg-white rounded-tl-3xl shadow-inner">
        <!-- Top Header / Navbar -->
        <header class="bg-white p-6 flex items-center justify-between sticky top-0 z-10 border-b border-gray-100">
            <div class="relative flex-1 max-w-md mr-4">
                <input type="text" placeholder="Search bookings..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-lavender-300">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
            <div class="flex items-center space-x-4">
                
            </div>
        </header>

        <!-- Dashboard Content -->
        <main class="flex-1 p-10 bg-white">
            <h1 class="text-3xl font-heading font-bold text-plum-700 mb-8 flex items-center gap-3">
                <i class="fas fa-home text-2xl"></i>
                Dashboard
            </h1>

            <!-- Booking Stats -->
            <div id="booking-stats" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <!-- Stats will be rendered here by JavaScript -->
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Assigned Bookings -->
                <div id="assigned-bookings-section" class="bg-white shadow-md rounded-xl overflow-hidden border border-gray-200 p-6">
                    <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Assigned Bookings</h2>
                    <div class="grid w-full grid-cols-2 mb-4 bg-gray-100 rounded-lg p-1">
                        <button data-tab="upcoming" class="tab-trigger px-4 py-2 rounded-md text-sm font-medium text-gray-700 data-[state=active]:bg-white data-[state=active]:shadow-sm data-[state=active]:text-plum-700 transition">Ongoing Bookings</button>
                        <button data-tab="past" class="tab-trigger px-4 py-2 rounded-md text-sm font-medium text-gray-700 data-[state=active]:bg-white data-[state=active]:shadow-sm data-[state=active]:text-plum-700 transition">Past Bookings</button>
                    </div>
                    <div id="upcoming-bookings-content" class="tab-content mt-4" data-tab-content="upcoming">
                        <!-- Upcoming bookings will be rendered here by JavaScript -->
                    </div>
                    <div id="past-bookings-content" class="tab-content mt-4 hidden" data-tab-content="past">
                        <!-- Past bookings will be rendered here by JavaScript -->
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Notifications -->
                    <div id="notifications-section" class="bg-white shadow-md rounded-xl overflow-hidden border border-gray-200 p-6">
                        <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Notifications</h2>
                        <div id="notifications-list" class="space-y-4">
                            <!-- Notifications will be rendered here by JavaScript -->
                        </div>
                    </div>

                    <!-- Profile Section -->
                    <div id="profile-section" class="bg-white shadow-md rounded-xl overflow-hidden border border-gray-200 p-6">
                        <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Artist Profile</h2>
                        <div class="flex flex-col items-center text-center">
                         <i class="fas fa-user text-6xl text-plum-500 mb-4 border-2 border-plum-500 rounded-full p-4"></i>

                            <h3 class="text-2xl font-semibold text-gray-800">
                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </h3>
                            <p class="text-gray-600 mb-4"><?= htmlspecialchars($user['email']); ?></p>
                            <p class="text-sm text-gray-700 mb-6 max-w-md">
                                <?= htmlspecialchars($user['bio'] ?? 'No bio yet.'); ?>
                            </p>
                        </div>
                    </div>

                    
    <!-- Artist Reviews Section -->
                    <div id="artist-reviews-section" class="bg-white shadow-md rounded-xl overflow-hidden border border-gray-200 p-6 mt-6">
                        <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Artist Reviews</h2>

                        <?php if (empty($artist_reviews)): ?>
                            <p class="text-gray-500 italic">No reviews yet.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($artist_reviews as $review): ?>
                                    <div class="p-4 border border-gray-200 rounded-lg shadow-sm">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="font-semibold text-plum-700"><?= htmlspecialchars($review['client_name']); ?></span>
                                            <span class="text-sm text-gray-500"><?= date("F j, Y", strtotime($review['created_at'])); ?></span>
                                        </div>
                                        <div class="flex items-center mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fa-star <?= $i <= $review['rating'] ? 'fas text-yellow-400' : 'far text-gray-300'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <p class="text-gray-700 mb-2"><?= htmlspecialchars($review['comment']); ?></p>
                                        <p class="text-sm text-gray-500">
                                            <strong>Package:</strong> <?= htmlspecialchars($review['package_name'] ?? 'N/A'); ?>
                                            <span class="ml-2">| Sentiment: 
                                                <span class="<?= $review['sentiment'] === 'positive' ? 'text-green-600' : ($review['sentiment'] === 'negative' ? 'text-red-600' : 'text-gray-600'); ?>">
                                                    <?= ucfirst($review['sentiment']); ?>
                                                </span>
                                            </span>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </main>
    </div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
    // === Dummy Data ===
    const bookings = <?= json_encode($assigned_bookings); ?>;

    const notifications = <?= json_encode($latest_notifications, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

    const stats = {
        total: <?= $stats['total']; ?>,
        upcoming: <?= $stats['ongoing']; ?>,
        completed: <?= $stats['completed']; ?>
    };
    // === Render Stats ===
    const statsContainer = document.getElementById("booking-stats");
    statsContainer.innerHTML = `
        <div class="bg-gradient-to-r from-card-blue to-card-light-blue text-white rounded-xl p-6 shadow-md">
            <h3 class="text-lg font-semibold mb-1">Total Bookings</h3>
            <p class="text-3xl font-bold">${stats.total}</p>
        </div>
        <div class="bg-gradient-to-r from-card-green to-card-light-green text-white rounded-xl p-6 shadow-md">
            <h3 class="text-lg font-semibold mb-1">Ongoing Bookings</h3>
            <p class="text-3xl font-bold">${stats.upcoming}</p>
        </div>
        <div class="bg-gradient-to-r from-card-orange to-card-pink text-white rounded-xl p-6 shadow-md">
            <h3 class="text-lg font-semibold mb-1">Completed Bookings</h3>
            <p class="text-3xl font-bold">${stats.completed}</p>
        </div>
    `;

    // === Render Bookings ===
    const upcomingContainer = document.getElementById("upcoming-bookings-content");
    const pastContainer = document.getElementById("past-bookings-content");

    bookings.forEach((b) => {
        const card = document.createElement("div");
        card.className = "p-4 border border-gray-200 rounded-lg mb-4 shadow-sm hover:shadow transition";
        card.innerHTML = `
            <h2 class="font-semibold text-lg text-plum-600">${b.client_name}</h2>
            <p><strong>Event:</strong> ${b.event_type}</p>
            <p><strong>Package:</strong> ${b.package_name}</p>
            <p><strong>Date:</strong> ${b.booking_date}</p>
            <p><strong>Time:</strong> ${b.booking_time}</p>
            <p><strong>Address:</strong> ${b.booking_address}</p>
        `;

        if (b.is_confirmed == 0 || b.is_confirmed == 1) {
            upcomingContainer.appendChild(card);
        } else if (b.is_confirmed == 3) {
            pastContainer.appendChild(card);
        }
    });

    // === Render Notifications ===
    const notificationsList = document.getElementById("notifications-list");
    notifications.forEach((n) => {
        const item = document.createElement("div");
        item.className = "p-3 bg-white border-l-4 border-plum-500 rounded shadow-sm";
        item.innerHTML = `
            <p class="text-sm text-gray-800"><strong>${n.message}</strong></p>
            <p class="text-xs text-gray-500">${n.date}</p>
        `;
        notificationsList.appendChild(item);
    });


    // === Tab Switching ===
    const tabTriggers = document.querySelectorAll(".tab-trigger");
    const tabContents = document.querySelectorAll(".tab-content");

    tabTriggers.forEach((btn) => {
        btn.addEventListener("click", () => {
            const tab = btn.getAttribute("data-tab");
            tabTriggers.forEach((b) => {
                if (b === btn) {
                    b.setAttribute("data-state", "active");
                } else {
                    b.setAttribute("data-state", "inactive");
                }
            });
            tabContents.forEach((content) => {
                content.classList.toggle("hidden", content.getAttribute("data-tab-content") !== tab);
            });
        });
    });

    // === Mobile Sidebar Toggle ===
    const mobileToggle = document.getElementById("mobile-sidebar-toggle");
    const sidebar = document.getElementById("sidebar");

    mobileToggle.addEventListener("click", () => {
        sidebar.classList.toggle("-translate-x-full");
    });
});
document.addEventListener("DOMContentLoaded", () => {
  const links = document.querySelectorAll(".sidebar-link");
  const currentPage = window.location.pathname.split("/").pop(); 

  links.forEach(link => {
    const pageName = link.getAttribute("href").split("/").pop();
    if (pageName === currentPage) {
      link.classList.add("bg-lavender-100", "text-plum-700", "font-semibold");
    } else {
      link.classList.remove("bg-lavender-100", "text-plum-700", "font-semibold");
    }
  });
});

</script>
</body>
</html>
