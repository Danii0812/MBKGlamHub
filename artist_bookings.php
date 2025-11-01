<?php
session_start();
include 'db.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Oct 1997 05:00:00 GMT');

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
    <div class="flex-1 flex flex-col bg-white rounded-tl-3xl shadow-inner">
        <!-- Top Header / Navbar -->


        <!-- Bookings Content -->
        <main class="flex-1 p-10 bg-white ml-64">

            <h1 class="text-3xl font-heading font-bold text-plum-700 mb-8 flex items-center gap-3">
                <i class="fas fa-calendar-check text-2xl"></i>
                Assigned Bookings
            </h1>

            <div class="bg-white shadow-md rounded-xl overflow-hidden border border-gray-200 p-6">
              <!-- Filters -->
<div class="mb-4 grid gap-3 md:grid-cols-6 sm:grid-cols-2">
  <!-- Free text search -->
  <div>
    <label class="block text-sm text-gray-600 mb-1">Search</label>
    <input id="flt-text" type="text" placeholder="Client, address, service…"
           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-plum-300">
  </div>

  <!-- Status -->
  <div>
    <label class="block text-sm text-gray-600 mb-1">Status</label>
    <select id="flt-status" class="w-full border border-gray-300 rounded-md px-3 py-2">
      <option value="">All</option>
      <option value="0">Pending</option>
      <option value="1">Ongoing</option>
      <option value="3">Completed</option>
    </select>
  </div>

  <!-- Payment -->
  <div>
    <label class="block text-sm text-gray-600 mb-1">Payment</label>
    <select id="flt-payment" class="w-full border border-gray-300 rounded-md px-3 py-2">
      <option value="">All</option>
      <option value="pending">Pending</option>
      <option value="paid">Paid</option>
    </select>
  </div>

  <!-- Event type (dynamic options populated by JS) -->
  <div>
    <label class="block text-sm text-gray-600 mb-1">Event Type</label>
    <select id="flt-event" class="w-full border border-gray-300 rounded-md px-3 py-2">
      <option value="">All</option>
    </select>
  </div>

  <!-- Date from -->
  <div>
    <label class="block text-sm text-gray-600 mb-1">Date From</label>
    <input id="flt-from" type="date"
           class="w-full border border-gray-300 rounded-md px-3 py-2">
  </div>

  <!-- Date to -->
  <div>
    <label class="block text-sm text-gray-600 mb-1">Date To</label>
    <input id="flt-to" type="date"
           class="w-full border border-gray-300 rounded-md px-3 py-2">
  </div>
</div>

<div class="flex items-center gap-2 mb-4">
  <button id="flt-apply" class="bg-plum-600 hover:bg-plum-700 text-white px-4 py-2 rounded-md">
    Apply Filters
  </button>
  <button id="flt-reset" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md">
    Reset
  </button>
</div>

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
const upcomingBookings = [];
const pastBookings = [];

bookingsData.forEach(b => {
  let bookingDateObj = null;
  if (b.booking_date) {
    let dtStr = b.booking_date;
    if (b.booking_time) dtStr = `${b.booking_date}T${b.booking_time}`;
    bookingDateObj = new Date(dtStr);
    if (isNaN(bookingDateObj)) bookingDateObj = new Date(b.booking_date);
  } else {
    bookingDateObj = new Date();
  }

  const serviceName = b.package_name?.length
    ? b.package_name
    : `${b.makeup_style || ''}${b.hair_style ? (' & ' + b.hair_style) : ''}`.trim() || 'Service';

  const eventType = b.package_event_type?.length ? b.package_event_type : (b.event_type || '');
  const priceRange = b.package_price_range?.length ? b.package_price_range : (b.client_price_range || '');

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
    payment_status: (b.payment_status || '').toLowerCase()
  };

  if (booking.status === 0 || booking.status === 1) {
    upcomingBookings.push(booking);
  } else if (booking.status === 3) {
    pastBookings.push(booking);
  }
});

// ---------- FILTERS ----------
const qs = id => document.getElementById(id);

// Populate event types dynamically
(function populateEventTypes() {
  const sel = qs('flt-event');
  if (!sel) return;
  const all = [...upcomingBookings, ...pastBookings];
  const uniq = Array.from(new Set(all.map(b => (b.event_type || '').trim()).filter(Boolean))).sort();
  uniq.forEach(v => {
    const opt = document.createElement('option');
    opt.value = v;
    opt.textContent = v;
    sel.appendChild(opt);
  });
})();

function normalize(str) { return (str || '').toString().toLowerCase(); }

function inDateRange(dateObj, fromStr, toStr) {
  if (!fromStr && !toStr) return true;
  const d = new Date(dateObj);
  if (fromStr) {
    const f = new Date(fromStr);
    if (d < new Date(f.getFullYear(), f.getMonth(), f.getDate())) return false;
  }
  if (toStr) {
    const t = new Date(toStr);
    // include the end day
    const end = new Date(t.getFullYear(), t.getMonth(), t.getDate(), 23, 59, 59, 999);
    if (d > end) return false;
  }
  return true;
}

function applyFiltersToList(list) {
  const txt = normalize(qs('flt-text')?.value);
  const status = qs('flt-status')?.value;       // "", "0", "1", "3"
  const payment = normalize(qs('flt-payment')?.value); // "", "pending", "paid"
  const eventType = qs('flt-event')?.value;     // "" or exact event name
  const from = qs('flt-from')?.value;           // "YYYY-MM-DD" or ""
  const to = qs('flt-to')?.value;               // "YYYY-MM-DD" or ""

  return list.filter(b => {
    // text search against several fields
    const hay = `${b.client} ${b.address} ${b.service} ${b.event_type} ${b.team} ${b.price_range}`.toLowerCase();
    if (txt && !hay.includes(txt)) return false;

    if (status !== '' && String(b.status) !== status) return false;
    if (payment && normalize(b.payment_status) !== payment) return false;
    if (eventType && b.event_type !== eventType) return false;
    if (!inDateRange(b.dateObj, from, to)) return false;

    return true;
  });
}

function renderAll() {
  renderBookings(applyFiltersToList(upcomingBookings), "upcoming-bookings-content");
  renderBookings(applyFiltersToList(pastBookings), "past-bookings-content");
}

// Wire up buttons
qs('flt-apply')?.addEventListener('click', renderAll);
qs('flt-reset')?.addEventListener('click', () => {
  ['flt-text','flt-status','flt-payment','flt-event','flt-from','flt-to'].forEach(id => {
    const el = qs(id);
    if (!el) return;
    if (el.tagName === 'INPUT') el.value = '';
    if (el.tagName === 'SELECT') el.value = '';
  });
  renderAll();
});

// Live search on typing (optional, remove if you prefer the Apply button only)
qs('flt-text')?.addEventListener('input', renderAll);

// ---- render function (existing) ----
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
    card.className = "bg-white border border-gray-200 p-4 rounded-xl shadow-sm mb-4";
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
    card.addEventListener('click', () => openBookingDetailModal(booking.booking_id));
    container.appendChild(card);
  });
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function openBookingDetailModal(bookingId) {
  console.log('Open details for booking', bookingId);
}

// Initial render
renderAll();

// Keep your existing tab setup (make Ongoing active by default)
document.addEventListener("DOMContentLoaded", () => {
  const ongoingBtn = document.querySelector('[data-tab="upcoming"]');
  const completedBtn = document.querySelector('[data-tab="past"]');

  ongoingBtn.dataset.state = "active";
  completedBtn.dataset.state = "";

  const upcomingContent = document.querySelector('[data-tab-content="upcoming"]');
  const pastContent = document.querySelector('[data-tab-content="past"]');
  upcomingContent.classList.remove("hidden");
  pastContent.classList.add("hidden");
});

</script>
 
</body>
</html>
