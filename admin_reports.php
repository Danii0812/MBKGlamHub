<?php
session_start();

// Block caching on authenticated pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Oct 1997 05:00:00 GMT');

// If not logged in or not admin, go to login
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

include 'db.php'; // now safe to include


// Total Revenue (Current Month)
$query = "
  SELECT 
    SUM(p.price) AS total_revenue
  FROM bookings b
  JOIN packages p ON b.package_id = p.package_id
  WHERE b.is_confirmed = 3
";
$result = $conn->query($query);
$row = $result->fetch_assoc();
$totalRevenue = $row['total_revenue'] ?? 0;

// Last Month Revenue
$queryLast = "
    SELECT SUM(p.price) AS total_revenue
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    WHERE b.is_confirmed = 3
    AND MONTH(b.booking_date) = MONTH(CURDATE() - INTERVAL 1 MONTH)
    AND YEAR(b.booking_date) = YEAR(CURDATE() - INTERVAL 1 MONTH)
";
$resultLast = $conn->query($queryLast);
$lastMonthRevenue = $resultLast->fetch_assoc()['total_revenue'] ?? 0;


if ($lastMonthRevenue > 0) {
    $revenueChange = (($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
} else {
    $revenueChange = 100; 
}
$revenueChangeText = number_format($revenueChange, 1) . '% ' . ($revenueChange >= 0 ? 'increase' : 'decrease');

// Completed Bookings
$queryCompleted = "
    SELECT COUNT(*) AS completed_bookings
    FROM bookings
    WHERE is_confirmed = 3
";
$resultCompleted = $conn->query($queryCompleted);
$rowCompleted = $resultCompleted->fetch_assoc();
$completedBookings = $rowCompleted['completed_bookings'] ?? 0;

// Total Clients
$queryClients = "
    SELECT COUNT(*) AS total_clients
    FROM users
    WHERE role = 'user'
";
$resultClients = $conn->query($queryClients);
$rowClients = $resultClients->fetch_assoc();
$totalClients = $rowClients['total_clients'] ?? 0;

// Monthly Bookings (Last 7 Months including 0 bookings)
$months = [];
$bookingsData = [];

// Generate array for last 7 months including current
for ($i = 6; $i >= 0; $i--) {
    $monthName = date('M', strtotime("-$i months"));
    $monthKey = date('Y-m', strtotime("-$i months"));
    $months[$monthKey] = $monthName;
    $bookingsData[$monthKey] = 0; // default to 0
}

// Fetch actual data
$queryMonthly = "
    SELECT 
        DATE_FORMAT(booking_date, '%Y-%m') AS month_key,
        COUNT(*) AS total
    FROM bookings
    WHERE is_confirmed = 3
      AND booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(booking_date), MONTH(booking_date)
";
$resultMonthly = $conn->query($queryMonthly);

// Merge real counts into default months
while ($row = $resultMonthly->fetch_assoc()) {
    $monthKey = $row['month_key'];
    if (isset($bookingsData[$monthKey])) {
        $bookingsData[$monthKey] = (int)$row['total'];
    }
}

// Convert to separate label and data arrays
$labels = array_values($months);
$data = array_values($bookingsData);

// Convert to JSON for Chart.js
$monthsJSON = json_encode($labels);
$bookingsJSON = json_encode($data);


// Top 3 Services (Most Booked Packages)
$queryTopServices = "
    SELECT 
        p.name AS package_name,
        COUNT(b.booking_id) AS total_bookings
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    WHERE b.is_confirmed = 3
    GROUP BY p.package_id, p.name
    ORDER BY total_bookings DESC
    LIMIT 3
";
$resultTopServices = $conn->query($queryTopServices);

$topServices = [];
while ($row = $resultTopServices->fetch_assoc()) {
    $topServices[] = $row;
}

// User Reviews
$queryReviews = "
    SELECT 
        r.review_id,
        r.rating,
        r.comment,
        r.created_at,
        CONCAT(u.first_name, ' ', u.last_name) AS user_name,
        p.name AS package_name
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN bookings b ON r.booking_id = b.booking_id
    JOIN packages p ON b.package_id = p.package_id
    WHERE r.is_verified = 1
    ORDER BY r.created_at DESC
    LIMIT 5
";
$resultReviews = $conn->query($queryReviews);
$latestReviews = [];
while ($row = $resultReviews->fetch_assoc()) {
    $latestReviews[] = $row;
}

// Demographics (Bookings per Team)
$queryTeams = "
    SELECT 
        t.name AS team_name,
        COUNT(DISTINCT bc.booking_id) AS total_bookings
    FROM booking_clients bc
    JOIN teams t ON bc.team_id = t.team_id
    JOIN bookings b ON bc.booking_id = b.booking_id
    WHERE b.is_confirmed = 3
    GROUP BY t.team_id, t.name
    ORDER BY total_bookings DESC
";

$resultTeams = $conn->query($queryTeams);

$teamNames = [];
$teamBookings = [];

while ($row = $resultTeams->fetch_assoc()) {
    $teamNames[] = $row['team_name'];
    $teamBookings[] = (int)$row['total_bookings'];
}

$teamNamesJSON = json_encode($teamNames);
$teamBookingsJSON = json_encode($teamBookings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Reports Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="mbk_logo.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        // Colors for gradients in cards, inspired by the "Purple" dashboard
                        'card-pink': '#FF6B6B',
                        'card-orange': '#FFA07A',
                        'card-blue': '#6A82FB',
                        'card-light-blue': '#FCFCFC', // Lighter blue for gradients
                        'card-green': '#2ECC71',
                        'card-light-green': '#A8E6CF',
                        'card-purple': '#9B59B6',
                        'card-light-purple': '#D2B4DE',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-lavender-50 font-body overflow-x-hidden">
    <div class="flex min-h-screen w-full">
    
    <!-- Sidebar (Sticky, Full Height, No Scroll) -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content Area -->
        <div class="flex-1 flex flex-col bg-white rounded-tl-3xl shadow-inner overflow-y-auto">
        <!-- Top Header / Navbar -->
        <?php include 'admin_header.php'; ?>

        <!-- Reports Dashboard Content -->
           <main class="flex-grow p-10 bg-white ml-64 mt-16">

            <h1 class="text-3xl font-heading font-bold text-plum-700 mb-8 flex items-center gap-3">
                <i class="fas fa-chart-line text-2xl"></i>
                Reports Overview
            </h1>

            <!-- Summary Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <!-- Total Revenue Card -->
                <div class="p-6 rounded-xl shadow-md text-white bg-gradient-to-br from-card-pink to-card-orange relative overflow-hidden">
                    <div class="absolute inset-0 opacity-20">
                        <i class="fas fa-dollar-sign text-9xl absolute -bottom-8 -right-8"></i>
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-xl font-semibold mb-2">Total Revenue</h2>
                        <p class="text-4xl font-bold mt-2">₱<?= number_format($totalRevenue, 2) ?></p>
                        <p class="text-sm opacity-80 mt-1"><?= $revenueChangeText ?> from last month</p>
                    </div>
                </div>
                <!-- Total Bookings Card -->
                <div class="p-6 rounded-xl shadow-md text-white bg-gradient-to-br from-card-blue to-card-light-blue relative overflow-hidden">
                    <div class="absolute inset-0 opacity-20">
                        <i class="fas fa-calendar-check text-9xl absolute -bottom-8 -right-8"></i>
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-xl font-semibold mb-2">Completed Bookings</h2>
                        <p class="text-4xl font-bold mt-2"><?php echo $completedBookings; ?></p>
                    </div>
                </div>
                <!-- New Clients Card -->
                <div class="p-6 rounded-xl shadow-md text-white bg-gradient-to-br from-card-green to-card-light-green relative overflow-hidden">
                    <div class="absolute inset-0 opacity-20">
                        <i class="fas fa-user-plus text-9xl absolute -bottom-8 -right-8"></i>
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-xl font-semibold mb-2">Total Clients</h2>
                        <p class="text-4xl font-bold mt-2"><?php echo $totalClients; ?></p>
                    </div>
                </div>
            </div>

            <!-- Charts and Tables Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Monthly Bookings Chart -->
                    <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6">
                        <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Monthly Bookings</h2>
                        <div class="h-64 bg-white border border-gray-100 rounded-lg p-4 shadow-sm">
                        <canvas id="monthlyBookingsChart" class="w-full h-full"></canvas>
                        </div>
                    </div>




                <!-- Top Services Table -->
                <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6">
                    <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Top Services</h2>
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-lavender-50 text-plum-700 uppercase font-medium">
                            <tr>
                                <th class="px-6 py-3">Service Name</th>
                                <th class="px-6 py-3 text-right">Bookings</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-lavender-200">
                            <?php if (!empty($topServices)): ?>
                                <?php foreach ($topServices as $service): ?>
                                    <tr class="hover:bg-lavender-50">
                                        <td class="px-6 py-4"><?= htmlspecialchars($service['package_name']) ?></td>
                                        <td class="px-6 py-4 text-right"><?= $service['total_bookings'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-gray-500">No completed bookings yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>
                </div>

                <!-- Client Demographics Chart -->
                <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6 lg:col-span-1">
                <h2 class="text-xl font-heading font-bold text-plum-700 mb-4 text-center">Client Demographics</h2>
                <div class="bg-white border border-gray-100 rounded-lg flex justify-center items-center p-6 shadow-sm">
                    <div class="w-80 h-80"> 
                    <canvas id="demographicsChart" class="w-full h-full"></canvas>
                    </div>
                </div>
                </div>

            <!-- Latest Verified User Reviews -->
            <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6 lg:col-span-1">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-heading font-bold text-plum-700">User Reviews</h2>
                    <a href="admin_reviews.php" class="text-lavender-600 text-sm font-medium hover:underline">View all</a>
                </div>

                <?php if (!empty($latestReviews)): ?>
                    <div class="space-y-4 max-h-80 overflow-y-auto pr-2">
                        <?php foreach ($latestReviews as $review): ?>
                            <div class="border border-lavender-100 rounded-lg p-4 bg-lavender-50 hover:bg-lavender-100 transition">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="font-semibold text-plum-700"><?= htmlspecialchars($review['user_name']) ?></p>
                                    <div class="flex items-center text-yellow-400">
                                        <?php 
                                            $rating = (int)$review['rating'];
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo $i <= $rating 
                                                    ? '<i class="fa-solid fa-star"></i>' 
                                                    : '<i class="fa-regular fa-star text-gray-300"></i>';
                                            }
                                        ?>
                                    </div>
                                </div>
                                <p class="text-gray-700 text-sm italic mb-1">“<?= htmlspecialchars($review['comment']) ?>”</p>
                                <p class="text-xs text-gray-500">
                                    <i class="fa-regular fa-calendar"></i>
                                    <?= date('M d, Y', strtotime($review['created_at'])) ?> · <?= htmlspecialchars($review['package_name']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-6">No verified reviews yet</p>
                <?php endif; ?>
            </div>


                </div>
            </div>
        </main>
    </div>
</body>
</html>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sidebar highlighting
    const currentPath = window.location.pathname;
    const currentPage = currentPath.substring(currentPath.lastIndexOf('/') + 1).replace('.php', '').toLowerCase();

    document.querySelectorAll('.sidebar-link').forEach(link => {
        const page = link.getAttribute('href').replace('.php', '').toLowerCase();
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

    // Monthly Bookings Chart
    const bookingsChartEl = document.getElementById('monthlyBookingsChart');
    if (bookingsChartEl) {
        const bookingsCtx = bookingsChartEl.getContext('2d');
        new Chart(bookingsCtx, {
            type: 'bar',
            data: {
                labels: <?= $monthsJSON ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?= $bookingsJSON ?>,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10
                        },
                        grid: {
                            color: '#f3f0ff'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // Demographics Chart
    const ctx = document.getElementById('demographicsChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= $teamNamesJSON ?>,
            datasets: [{
                data: <?= $teamBookingsJSON ?>,
                backgroundColor: [
                    '#A78BFA', '#C4B5FD', '#DDD6FE', '#E9D5FF', '#F5D0FE'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                title: {
                    display: true,
                    text: 'Bookings per Team (Confirmed Only)'
                }
            }
        }
    });

});
</script>