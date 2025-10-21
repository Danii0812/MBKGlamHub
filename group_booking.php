<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$greetingName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MBK GlamHub</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            lavender: {
              50: '#fafaff',
              100: '#f5f5fa',
              200: '#ececf7',
              300: '#e6e6fa',
              400: '#d8d1e8',
              500: '#c2b6d9',
              600: '#a79dbf',
              700: '#8e83a3',
              800: '#756a86',
              900: '#5d516c'
            },
            plum: {
              50: '#f9f2f7',
              100: '#f1e3ef',
              200: '#e0c5dc',
              300: '#c89ac1',
              400: '#a06c9e',
              500: '#804f7e',
              600: '#673f68',
              700: '#4b2840',
              800: '#3c1f33',
              900: '#2c1726'
            }
          }
        }
      }
    }
  </script>
  <style>
    .gradient-text {
      background: linear-gradient(to right, #a06c9e, #4b2840);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .gradient-bg {
      background: linear-gradient(to right, #a06c9e, #4b2840);
    }
    .gradient-bg:hover {
      background: linear-gradient(to right, #804f7e, #673f68);
    }
    html {
      scroll-behavior: smooth;
    }
  </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-lavender-50 via-lavender-100 to-lavender-200">

<!-- Header -->
<header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-lavender-200">
  <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
    <div class="flex items-center space-x-2">
      <a href="homepage.php"><img src="logo.png" alt="Make up By Kyleen Logo" class="h-10 w-auto"></a>
    </div>

      <nav class="hidden md:flex items-center space-x-8">
        <a href="#services" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Services</a>
        <a href="#about" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">About</a>
        <a href="artist_portfolio.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Portfolio</a>
        <a href="reviews.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Reviews</a>
<!-- Dropdown Menu Container -->
<div class="relative group inline-block text-left">
  <span class="gradient-bg text-white px-6 py-2 rounded-md font-medium transition-all inline-block cursor-pointer">
    Hello, <?php echo htmlspecialchars($greetingName); ?>
    <i class="fas fa-chevron-down text-white text-sm"></i>
  </span>

  <!-- Dropdown Items -->
  <div class="absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-md shadow-lg opacity-0 group-hover:opacity-100 invisible group-hover:visible transition-all z-50">
    <?php if ($isLoggedIn): ?>
      <a href="appointments.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">My Appointments</a>
      <a href="profile_settings.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile Settings</a>
      <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Sign Out</a>
    <?php else: ?>
      <a href="login.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Log In</a>
    <?php endif; ?>
  </div>
</div>



      </nav>
    </div>
  </div>
</header>
    <div class="flex-1 flex flex-col items-center justify-center p-4">
        <!-- Step Indicator -->
        <div class="mb-8 flex items-center justify-center space-x-8 text-gray-500 font-semibold">
            <!-- Step 1: Booking Details (Active) -->
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 rounded-full bg-plum-500 flex items-center justify-center text-white font-bold text-lg shadow-md">1</div>
                <span class="text-plum-700 font-bold">Booking Details</span>
            </div>

<!-- Line between steps -->
<div class="w-10 h-1 bg-gray-300 mt-4"></div>
            <!-- Step 2: Glam Teams (Inactive) -->
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 rounded-full border-2 border-gray-300 flex items-center justify-center text-gray-500 text-lg font-bold mb-2">2</div>
                <span>Glam Teams</span>
            </div>
  
<!-- Line between steps -->
<div class="w-10 h-1 bg-gray-300 mt-4"></div>
            <!-- Step 3: Select Package (Inactive) -->
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 rounded-full border-2 border-gray-300 flex items-center justify-center text-gray-500 text-lg font-bold mb-2">3</div>
                <span>Select Package</span>
            </div>
           
<!-- Line between steps -->
<div class="w-10 h-1 bg-gray-300 mt-4"></div>
            <!-- Step 4: Confirmation (Inactive) -->
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 rounded-full border-2 border-gray-300 flex items-center justify-center text-gray-500 text-lg font-bold mb-2">4</div>
                <span>Confirmation</span>
            </div>
        </div>
        <main class="w-full max-w-3xl p-8 bg-white rounded-xl shadow-lg border border-lavender-100">
    <h2 class="text-3xl font-heading font-bold text-center text-plum-700 mb-8">Booking Details</h2>

    <form action="submit_group_booking.php" method="POST" class="space-y-6">
      <div>
        <label for="booking_date" class="block text-gray-700 font-semibold mb-2">
          <i class="fas fa-calendar-alt text-plum-500 mr-2"></i>Booking Date <span class="text-red-500">*</span>
        </label>
        <input
            type="date"
            name="booking_date"
            id="booking_date"
            required
            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-plum-300 focus:border-plum-500 transition-all"/>
            </div>
      <div>
        <label for="booking_time" class="block text-gray-700 font-semibold mb-2">
          <i class="fas fa-clock text-plum-500 mr-2"></i>Booking Time <span class="text-red-500">*</span>
        </label>
        <input type="time" name="booking_time" id="booking_time" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-plum-300 focus:border-plum-500" />
      </div>
      <div>
        <label for="booking_address" class="block text-gray-700 font-semibold mb-2">
          <i class="fas fa-map-marker-alt text-plum-500 mr-2"></i>Booking Address <span class="text-red-500">*</span>
        </label>
        <input type="text" name="booking_address" id="booking_address" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-plum-300 focus:border-plum-500" />
      </div>
      <div>
        <label for="client_count" class="block text-gray-700 font-semibold mb-2">
          <i class="fas fa-users text-plum-500 mr-2"></i>Number of Clients <span class="text-red-500">*</span>
        </label>
        <input type="number" name="client_count" id="client_count" value="<?= isset($_SESSION['client_count']) ? $_SESSION['client_count'] : 1 ?>" required oninput="updateForms()" min="1" max="15" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-plum-300 focus:border-plum-500" />
      </div>
      <div>
        <label for="shared_event_type" class="block text-gray-700 font-semibold mb-2">
          <i class="fas fa-calendar text-plum-500 mr-2"></i>Event Type <span class="text-red-500">*</span>
        </label>
        <select id="shared_event_type" name="shared_event_type" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-plum-300 focus:border-plum-500">
          <option value="Wedding">Wedding</option>
          <option value="Debut">Debut</option>
          <option value="Photoshoot">Photoshoot</option>
          <option value="Graduation">Graduation</option>
          <option value="Birthday">Birthday</option>
          <option value="Others">Others</option>
        </select>
      </div>
      <div>
        <label for="price_range" class="block text-gray-700 font-semibold mb-2">Price Range (Auto-selected):</label>
        <input type="text" id="price_range" name="price_range" readonly class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-50 cursor-not-allowed text-gray-600" />
      </div>

      <div id="client_forms" class="space-y-8 hidden"></div>

      <button type="submit" class="w-full bg-plum-500 hover:bg-plum-600 text-white py-3 rounded-2xl font-semibold text-lg transition-all shadow-md hover:shadow-lg mt-8 flex items-center justify-center space-x-2">
        <span>Proceed</span><i class="fas fa-arrow-right"></i>
      </button>
    </form>
  </main>
</div>

<script>
function updateForms() {
  const count = parseInt(document.getElementById('client_count').value) || 1;
  const formsContainer = document.getElementById('client_forms');
  const priceInput = document.getElementById('price_range');
  const sharedEventType = document.getElementById('shared_event_type').value;

  const actualCount = Math.max(1, Math.min(15, count));
  document.getElementById('client_count').value = actualCount;

  formsContainer.innerHTML = '';

  if (actualCount <= 5) {
    priceInput.value = 'Low (₱10,000–₱20,000)';
  } else if (actualCount <= 10) {
    priceInput.value = 'Medium (₱20,001–₱25,000)';
  } else {
    priceInput.value = 'High (₱25,001 and above)';
  }

  formsContainer.classList.remove('hidden');

  for (let i = 1; i <= actualCount; i++) {
    const formSection = document.createElement('fieldset');
    formSection.className = 'border border-lavender-200 rounded-xl p-6 bg-lavender-50 shadow-sm space-y-4';
    formSection.innerHTML = `
      <legend class="text-lg font-heading font-bold text-plum-700 px-2 mb-4 -ml-2">Client #${i} Preferences</legend>
      <div><label class="block text-gray-700 font-semibold mb-2">Client Name <span class="text-red-500">*</span>:</label>
      <input type="text" name="client_name[]" required class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-plum-300 focus:border-plum-500" /></div>
      <div><label class="block text-gray-700 font-semibold mb-2">Hair Style <span class="text-red-500">*</span>:</label>
      <select name="hair_style[]" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-plum-300 focus:border-plum-500">
        <option>Curls</option><option>Straight</option><option>Bun</option><option>Braided</option><option>Ponytail</option><option>Others</option>
      </select></div>
      <div><label class="block text-gray-700 font-semibold mb-2">Makeup Style <span class="text-red-500">*</span>:</label>
      <select name="makeup_style[]" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-plum-300 focus:border-plum-500">
        <option>Natural</option><option>Glam</option><option>Bold</option><option>Matte</option><option>Dewy</option><option>Themed</option>
      </select></div>
      <div><label class="block text-gray-700 font-semibold mb-2">Skin Tone <span class="text-red-500">*</span>:</label>
      <select name="skin_tone[]" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-plum-300 focus:border-plum-500">
        <option>Fair</option><option>Medium</option><option>Olive</option><option>Dark</option>
      </select></div>
      <div><label class="block text-gray-700 font-semibold mb-2">Face Shape <span class="text-red-500">*</span>:</label>
      <select name="face_shape[]" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-plum-300 focus:border-plum-500">
        <option>Round</option><option>Oval</option><option>Square</option><option>Heart</option><option>Diamond</option><option>Others</option>
      </select></div>
      <div><label class="block text-gray-700 font-semibold mb-2">Gender Preference <span class="text-red-500">*</span>:</label>
      <select name="gender_preference[]" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-plum-300 focus:border-plum-500">
        <option>No preference</option><option>Male</option><option>Female</option>
      </select></div>
      <div><label class="block text-gray-700 font-semibold mb-2">Hair Length <span class="text-red-500">*</span>:</label>
      <select name="hair_length[]" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-plum-300 focus:border-plum-500">
        <option>Short</option><option>Medium</option><option>Long</option>
      </select></div>
      <input type="hidden" name="event_type[]" value="${sharedEventType}" />
      <hr class="border-t border-lavender-200 my-4" />
    `;
    formsContainer.appendChild(formSection);
  }
}

document.addEventListener('DOMContentLoaded', updateForms);
document.getElementById('shared_event_type').addEventListener('change', updateForms);
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('booking_date').setAttribute('min', today);
    });
</script>

</body>
</html>