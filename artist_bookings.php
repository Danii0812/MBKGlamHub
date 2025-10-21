<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$artist_id = (int) $_SESSION['user_id'];

// Query: booking_clients -> teams -> bookings -> packages
$sql = "
SELECT
  bc.booking_id,
  bc.client_name,
  bc.hair_style,
  bc.makeup_style,
  COALESCE(bc.price_range, '') AS client_price_range,
  b.booking_date,
  b.booking_time,
  b.booking_address,
  b.is_confirmed,
  b.payment_status,
  b.package_id,
  p.name AS package_name,
  p.event_type AS package_event_type,
  p.price_range AS package_price_range,
  t.team_id,
  t.name AS team_name
FROM booking_clients bc
JOIN teams t ON bc.team_id = t.team_id
JOIN bookings b ON bc.booking_id = b.booking_id
LEFT JOIN packages p ON b.package_id = p.package_id
WHERE t.makeup_artist_id = ? OR t.hairstylist_id = ?
ORDER BY b.booking_date DESC, b.booking_time DESC, bc.client_name ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $artist_id, $artist_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Artist Bookings</title>
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
    <div class="flex-1 flex flex-col bg-white rounded-tl-3xl shadow-inner">
        <!-- Top Header / Navbar -->
        <header class="bg-white p-6 flex items-center justify-between sticky top-0 z-10 border-b border-gray-100 ml-64">
            <div class="relative flex-1 max-w-md mr-4">
                <input type="text" placeholder="Search bookings..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-lavender-300">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
            <div class="flex items-center space-x-4">

            </div>
        </header>

        <!-- Bookings Content -->
        <main class="flex-1 p-10 bg-white ml-64">

            <h1 class="text-3xl font-heading font-bold text-plum-700 mb-8 flex items-center gap-3">
                <i class="fas fa-calendar-check text-2xl"></i>
                Assigned Bookings
            </h1>

            <div class="bg-white shadow-md rounded-xl overflow-hidden border border-gray-200 p-6">
                <div class="grid w-full grid-cols-2 mb-4 bg-gray-100 rounded-lg p-1">
                    <button data-tab="upcoming" class="tab-trigger px-4 py-2 rounded-md text-sm font-medium text-gray-700 data-[state=active]:bg-white data-[state=active]:shadow-sm data-[state=active]:text-plum-700 transition">Ongoing Bookings</button>
                    <button data-tab="past" class="tab-trigger px-4 py-2 rounded-md text-sm font-medium text-gray-700 data-[state=active]:bg-white data-[state=active]:shadow-sm data-[state=active]:text-plum-700 transition">Completed Bookings</button>
                </div>
                <div id="upcoming-bookings-content" class="tab-content mt-4" data-tab-content="upcoming">
                    <!-- Upcoming bookings will be rendered here by JavaScript -->
                </div>
                <div id="past-bookings-content" class="tab-content mt-4 hidden" data-tab-content="past">
                    <!-- Past bookings will be rendered here by JavaScript -->
                </div>
            </div>
        </main>
    </div>

<script>
const bookingsData = <?= json_encode($bookings, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
// Sidebar toggle for mobile
const mobileToggle = document.getElementById("mobile-sidebar-toggle");
const sidebar = document.getElementById("sidebar");
mobileToggle.addEventListener("click", () => {
  sidebar.classList.toggle("-translate-x-full");
});

// Tabs functionality
const tabButtons = document.querySelectorAll(".tab-trigger");
const tabContents = document.querySelectorAll(".tab-content");

tabButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    tabButtons.forEach((b) => b.setAttribute("data-state", ""));
    tabContents.forEach((c) => c.classList.add("hidden"));

    btn.setAttribute("data-state", "active");
    const content = document.querySelector(`[data-tab-content="${btn.dataset.tab}"]`);
    content.classList.remove("hidden");
  });
});
// ---- categorize bookingsData into upcoming and past ----
const upcomingBookings = [];
const pastBookings = [];

bookingsData.forEach(b => {
  // build a reliable Date object
  let bookingDateObj = null;
  if (b.booking_date) {
    // prefer ISO: YYYY-MM-DDTHH:MM:SS
    let dtStr = b.booking_date;
    if (b.booking_time) {
      // if time is already in HH:MM:SS or HH:MM, this will work
      dtStr = `${b.booking_date}T${b.booking_time}`;
    }
    bookingDateObj = new Date(dtStr);
    if (isNaN(bookingDateObj)) {
      // fallback to date-only parsing if time parsing failed
      bookingDateObj = new Date(b.booking_date);
    }
  } else {
    bookingDateObj = new Date(); // fallback
  }

  const today = new Date();
  // zero-out time for "today" comparison if you want date-only comparison:
  // today.setHours(0,0,0,0);

  const serviceName = b.package_name && b.package_name.length
    ? b.package_name
    : [
        b.makeup_style || '',
        b.hair_style ? (' & ' + b.hair_style) : ''
      ].join('').trim() || 'Service';

  const eventType = b.package_event_type && b.package_event_type.length
    ? b.package_event_type
    : (b.event_type || '');

  const priceRange = b.package_price_range && b.package_price_range.length
    ? b.package_price_range
    : (b.client_price_range || '');

  const booking = {
    booking_id: b.booking_id,
    client: b.client_name,
    dateObj: bookingDateObj,
    date: bookingDateObj.toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' }),
    time: b.booking_time || 'TBA',
    address: b.booking_address || 'TBA',
    service: serviceName,
    event_type: eventType,
    price_range: priceRange,
    team: b.team_name || '',
    status: parseInt(b.is_confirmed, 10),
    payment_status: b.payment_status || ''
  };

  // classify based on booking status
  if (booking.status === 0 || booking.status === 1) {
    upcomingBookings.push(booking);
  } else if (booking.status === 3) {
    pastBookings.push(booking);
  }
});

// ---- render function (keeps your UI) ----
function renderBookings(bookings, containerId) {
  const container = document.getElementById(containerId);
  container.innerHTML = "";

  if (!bookings || bookings.length === 0) {
    container.innerHTML = `<p class="text-center text-gray-500 py-4">No bookings found.</p>`;
    return;
  }

  bookings.forEach((booking) => {
    const statusColor =
      booking.status === 0 ? "text-yellow-600" :
      booking.status === 1 ? "text-blue-600" :
      booking.status === 3 ? "text-green-600" :
      "text-gray-500";

    const statusLabel =
      booking.status === 0 ? "Pending" :
      booking.status === 1 ? "Ongoing" :
      booking.status === 3 ? "Completed" :
      "Unknown";

    const card = document.createElement("div");
    card.className = "bg-white border border-gray-200 p-4 rounded-xl shadow-sm mb-4"; // updated classes
    card.innerHTML = `
      <div class="flex justify-between items-center mb-2">
        <div>
          <h3 class="text-lg font-semibold text-plum-700">${escapeHtml(booking.client)}</h3>
          <p class="text-xs text-gray-500">${escapeHtml(booking.team)}</p>
        </div>
        <div class="text-right">
          <span class="text-sm ${statusColor} font-semibold block">${statusLabel}</span>
          <span class="text-xs text-gray-500">${escapeHtml(booking.event_type)}</span>
        </div>
      </div>

      <p class="text-sm text-gray-600 mb-1"><strong>Date:</strong> ${booking.date} ${booking.time ? 'at ' + escapeHtml(booking.time) : ''}</p>
      <p class="text-sm text-gray-600 mb-1"><strong>Address:</strong> ${escapeHtml(booking.address)}</p>
      <p class="text-sm text-gray-600 mb-1"><strong>Service:</strong> ${escapeHtml(booking.service)}</p>
      <p class="text-sm text-gray-600"><strong>Price Range:</strong> ${escapeHtml(booking.price_range)}</p>
    `;

    // optional: clicking a card opens detail modal
    card.addEventListener('click', () => openBookingDetailModal(booking.booking_id));
    container.appendChild(card);

  });
}

// small helper to avoid injecting raw HTML
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

// optional stub for modal - you can implement later
function openBookingDetailModal(bookingId) {
  // fetch detail via Ajax or use bookingsData to find booking & open modal
  // Example: const b = bookingsData.find(x => x.booking_id == bookingId);
  console.log('Open details for booking', bookingId);
}

// finally render into the page
renderBookings(upcomingBookings, "upcoming-bookings-content");
renderBookings(pastBookings, "past-bookings-content");
// Set default active tab on page load
document.addEventListener("DOMContentLoaded", () => {
    const ongoingBtn = document.querySelector('[data-tab="upcoming"]');
    const completedBtn = document.querySelector('[data-tab="past"]');

    // Make "Ongoing Bookings" active
    ongoingBtn.dataset.state = "active";
    completedBtn.dataset.state = "";

    // Ensure upcoming content is visible and past content hidden
    const upcomingContent = document.querySelector('[data-tab-content="upcoming"]');
    const pastContent = document.querySelector('[data-tab-content="past"]');
    upcomingContent.classList.remove("hidden");
    pastContent.classList.add("hidden");
});

</script>
 
</body>
</html>
