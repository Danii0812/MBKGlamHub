<?php
session_start();

$host = 'localhost';
$db   = 'mbk_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// --- Public page: NO auth redirect and NO no-store headers here ---

// Fetch packages
$stmt = $pdo->prepare("SELECT * FROM packages ORDER BY package_id ASC");
$stmt->execute();
$packages = $stmt->fetchAll();

// Fetch positive reviews
$stmt = $pdo->prepare("
    SELECT 
        r.*, 
        CONCAT(u.first_name, ' ', IFNULL(u.middle_name, ''), ' ', u.last_name) AS client_name,
        u.role AS client_type
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.user_id
    WHERE r.is_verified = 1 AND r.sentiment = 'positive'
    ORDER BY r.created_at DESC
    LIMIT 3
");
$stmt->execute();
$positiveReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isLoggedIn = isset($_SESSION['user_id']);
$greetingName = $_SESSION['user_name'] ?? 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MBK GlamHub</title>
    <link rel="icon" type="image/png" href="mbk_logo.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    html, body {
      background-color: #fff;
      margin: 0;
      padding: 0;
    }

    footer {
      margin-bottom: 0 !important;
      padding-bottom: 0 !important;
    }
  </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-lavender-50 via-lavender-100 to-lavender-200 opacity-0 translate-y-4 transition-all duration-700 ease-out" onload="document.body.classList.remove('opacity-0','translate-y-4')">


  <header class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-lavender-200">
    <div class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <a href="homepage.php">
            <img src="mbk_logo.png" alt="Make up By Kyleen Logo" class="h-14 w-auto">
          </a>
        </div>

        <nav class="hidden md:flex items-center space-x-8">
          <a href="services.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Services</a>
          <a href="homepage.php#about" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">About</a>
          <a href="artist_portfolio.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Portfolio</a>
          <a href="reviews.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Reviews</a>
            <!-- Dropdown Menu -->
          <div class="relative group inline-block text-left">
            <span class="gradient-bg text-white px-6 py-2 rounded-md font-medium transition-all inline-block cursor-pointer shadow-soft">
              Hello, <?php echo htmlspecialchars($greetingName); ?>
              <i class="fas fa-chevron-down text-white text-sm"></i>
            </span>
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

<script>
  // Dropdown toggle
  const menuBtn = document.getElementById('userMenuBtn');
  const menu = document.getElementById('userMenu');
  const chevron = menuBtn.querySelector('i');

  menuBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    menu.classList.toggle('hidden');
    chevron.classList.toggle('rotate-180'); // rotate the arrow for UX
  });

  // Click outside to close
  document.addEventListener('click', (e) => {
    if (!menu.contains(e.target) && !menuBtn.contains(e.target)) {
      menu.classList.add('hidden');
      chevron.classList.remove('rotate-180');
    }
  });
</script>


<!-- Hero Section (Collage Layout) -->
<section class="relative bg-white">
  <div class="container mx-auto px-6 lg:px-10 py-16 lg:py-24">
    <div class="grid lg:grid-cols-12 gap-12 items-center">

      <!-- LEFT: Copy + CTAs + Trust -->
      <div class="lg:col-span-6 space-y-8">
        <!-- Subtle badge -->
        <div class="inline-flex items-center gap-2 rounded-full border border-lavender-200 bg-lavender-50 px-4 py-1.5 text-sm text-plum-700">
          <i class="fa-solid fa-sparkles"></i>
          Luxury Makeup & Hair • Since 2010
        </div>

        <h1 class="text-4xl md:text-5xl lg:text-6xl font-heading font-bold leading-tight text-gray-900">
          Timeless Beauty,
          <span class="gradient-text">Expertly Crafted</span>
        </h1>

        <p class="text-lg md:text-xl text-gray-600 leading-relaxed max-w-xl">
          Trusted by brides, debutantes, and event icons for over 15 years. Our master MUAs and hairstylists enhance
          your natural glow with elevated, camera-ready artistry.
        </p>

        <!-- CTAs -->
        <div class="flex flex-col sm:flex-row gap-4 pt-2">
          <button
            onclick="<?php echo $isLoggedIn ? 'window.location.href=\'group_booking.php\'' : 'window.location.href=\'login.php\''; ?>"
            class="gradient-bg text-white px-7 py-4 rounded-xl font-medium shadow-md hover:shadow-lg hover:scale-[1.02] transition-all duration-300 flex items-center justify-center gap-2"
          >
            <i class="fas fa-calendar-alt"></i>
            Book Appointment
          </button>

          <a href="#services"
             class="px-7 py-4 rounded-xl border-2 border-lavender-300 text-plum-700 font-medium hover:bg-lavender-50 hover:border-plum-400 hover:text-plum-600 transition-all duration-300 flex items-center justify-center gap-2">
            <i class="fas fa-magic"></i>
            Explore Services
          </a>
        </div>

        <!-- Trust Row -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 pt-4">
          <!-- Rating -->
          <div class="flex items-center gap-2">
            
          </div>

          <!-- Divider dot -->
          <span class="hidden sm:inline-block w-1.5 h-1.5 rounded-full bg-lavender-300"></span>

        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 pt-6">
          <div class="rounded-2xl border border-lavender-200 bg-white p-4 text-center shadow-sm">
            <div class="text-2xl md:text-3xl font-heading font-bold text-plum-700">15+</div>
            <div class="text-xs md:text-sm text-gray-500">Years of Experience</div>
          </div>
          <div class="rounded-2xl border border-lavender-200 bg-white p-4 text-center shadow-sm">
            <div class="text-2xl md:text-3xl font-heading font-bold text-plum-700">1K+</div>
            <div class="text-xs md:text-sm text-gray-500">Happy Clients</div>
          </div>
        </div>
      </div>

<!-- RIGHT: Collage Visual -->
<div class="lg:col-span-6 relative">
  <!-- Glow blob -->
  <div class="absolute -top-10 -right-10 w-72 h-72 md:w-96 md:h-96 rounded-full blur-3xl bg-lavender-200/40 pointer-events-none"></div>

  <div id="collageCarousel" class="relative h-[480px] md:h-[560px] overflow-visible">
    <!-- Carousel Images -->
    <div class="carousel-item slot-left rounded-3xl overflow-hidden shadow-2xl border border-lavender-100">
      <img src="photo1.jpg" alt="Bridal Glam" class="w-full h-full object-cover">
    </div>
    <div class="carousel-item slot-center rounded-3xl overflow-hidden shadow-2xl border border-lavender-100">
      <img src="photo2.jpg" alt="Editorial Makeup" class="w-full h-full object-cover">
    </div>
    <div class="carousel-item slot-right rounded-3xl overflow-hidden shadow-2xl border border-lavender-100">
      <img src="photo3.jpg" alt="Hair Styling" class="w-full h-full object-cover">
    </div>

    <!-- Manual Controls -->
    <button id="prevBtn" class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 text-plum-700 rounded-full w-9 h-9 flex items-center justify-center shadow-md hover:bg-plum-100 transition pointer-events-auto z-40">
      <i class="fas fa-chevron-left text-sm"></i>
    </button>
    <button id="nextBtn" class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 text-plum-700 rounded-full w-9 h-9 flex items-center justify-center shadow-md hover:bg-plum-100 transition pointer-events-auto z-40">
      <i class="fas fa-chevron-right text-sm"></i>
    </button>

    <!-- Floating badge -->
    <div class="absolute -left-3 -bottom-3 bg-white border border-lavender-200 rounded-2xl shadow-md px-5 py-3 flex items-center gap-3">
      <div class="w-9 h-9 rounded-full bg-lavender-100 flex items-center justify-center text-plum-700">
        <i class="fa-solid fa-heart"></i>
      </div>
      <div>
        <div class="text-sm font-semibold text-plum-700">Camera-Ready Finish</div>
        <div class="text-xs text-gray-500">Photographers love our work</div>
      </div>
    </div>
  </div>
</div>

<style>
  #collageCarousel .carousel-item {
    position: absolute;
    transition:
      left 750ms ease, top 750ms ease,
      width 750ms ease, height 750ms ease,
      transform 750ms ease, opacity 750ms ease, z-index 0s;
    /* GPU-friendly */
    will-change: left, top, width, height, transform, opacity;
  }

  /* Center (featured) image — bigger + fully opaque */
  #collageCarousel .slot-center {
    left: 50%;
    top: 50%;
    width: 62%;
    height: 78%;
    transform: translate(-50%, -50%) scale(1) perspective(800px) translateZ(0);
    z-index: 30;
    opacity: 1;
    filter: none;
  }

  /* Left image — smaller, slightly faded */
  #collageCarousel .slot-left {
    left: 2%;
    top: 10%;
    width: 46%;
    height: 52%;
    transform: scale(0.88) perspective(800px) translateZ(0);
    z-index: 20;
    opacity: 0.85;
    filter: saturate(0.95);
  }

  /* Right image — smaller, slightly faded */
  #collageCarousel .slot-right {
    right: 6%;
    left: auto;
    top: 54%;
    width: 50%;
    height: 46%;
    transform: scale(0.88) perspective(800px) translateZ(0);
    z-index: 20;
    opacity: 0.85;
    filter: saturate(0.95);
  }

  /* Hover niceties on desktop */
  @media (hover: hover) {
    #collageCarousel .slot-center:hover {
      transform: translate(-50%, -50%) scale(1.03);
    }
  }

  @media (min-width: 768px) {
    #collageCarousel .slot-left { left: 4%; top: 8%; }
    #collageCarousel .slot-right { right: 6%; top: 56%; }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const carousel = document.getElementById('collageCarousel');
    if (!carousel) return;

    const items = Array.from(carousel.querySelectorAll('.carousel-item'));
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');

    // Class order is the visual order we rotate through
    let slots = ['slot-left', 'slot-center', 'slot-right'];

    let intervalMs = 3000;
    let timerId = null;

    function applySlots() {
      items.forEach((item, i) => {
        item.classList.remove('slot-left', 'slot-center', 'slot-right');
        item.classList.add(slots[i % slots.length]);
      });
    }

    function next() {
      // Move right -> front
      slots.unshift(slots.pop());
      applySlots();
    }

    function prev() {
      // Move front -> right
      slots.push(slots.shift());
      applySlots();
    }

    function startAuto() {
      stopAuto(); // safety: avoid multiple intervals
      timerId = setInterval(next, intervalMs);
    }

    function stopAuto() {
      if (timerId) {
        clearInterval(timerId);
        timerId = null;
      }
    }

    // Init
    applySlots();
    startAuto();

    // Manual controls (also reset timer)
    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        next();
        startAuto();
      });
    }
    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        prev();
        startAuto();
      });
    }

    // Pause on hover, resume on leave
    carousel.addEventListener('mouseenter', stopAuto);
    carousel.addEventListener('mouseleave', startAuto);
  });
</script>

    </div>
  </div>
</section>

<!-- Keep your existing gradient helpers -->
<style>
  .gradient-text {
    background: linear-gradient(to right, #a06c9e, #4b2840);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .gradient-bg { background: linear-gradient(to right, #a06c9e, #4b2840); }
  .gradient-bg:hover { background: linear-gradient(to right, #804f7e, #673f68); }
</style>



<!-- Services Section -->
<section id="services" class="py-20 bg-white">
  <div class="container mx-auto px-4">
    <!-- Section Header -->
    <div class="text-center mb-16">
      <span class="inline-flex items-center bg-lavender-100 text-plum-700 px-4 py-2 rounded-full text-sm font-medium mb-4">
        Our Services
      </span>
      <h2 class="text-4xl lg:text-5xl font-bold mb-6">
        <span class="gradient-text">Make up By Kyleen</span>
        <span class="text-gray-900"> Services</span>
      </h2>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
        From bridal makeup to editorial styling, we offer premium beauty services tailored to your unique style and occasion.
      </p>
    </div>
<!-- Service Cards Grid -->
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8" id="services-container">
  <?php
  // Initial limit
  $initialLimit = 6;
  $packagesToShow = array_slice($packages, 0, $initialLimit);
  $totalPackages = count($packages);
  foreach ($packagesToShow as $package): ?>
    <div class="group bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 border border-lavender-200 hover:border-lavender-300 hover:-translate-y-1">
      <!-- subtle header ribbon -->
      <div class="h-1.5 w-full bg-gradient-to-r from-plum-500 to-lavender-400"></div>

      <div class="p-7">
        <div class="mb-5">
          <h3 class="text-2xl font-bold text-gray-900 mb-2">
            <?= htmlspecialchars($package['name']); ?>
          </h3>

          <p class="text-gray-600 leading-relaxed">
            <?= htmlspecialchars($package['description']); ?>
          </p>
        </div>

        <!-- chips -->
        <div class="flex flex-wrap items-center gap-2.5 mb-6">
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-lavender-100 text-plum-700">
            <?= htmlspecialchars($package['event_type']); ?>
          </span>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-lavender-50 text-gray-700 border border-lavender-200">
            <?= htmlspecialchars($package['price_range']); ?> Range
          </span>
        </div>

        <!-- price block -->
        <div class="flex items-end justify-between">
          <div>
            <p class="text-sm text-gray-500">Starting at</p>
            <p class="text-3xl font-extrabold gradient-text tracking-tight">
              ₱<?= number_format($package['price'], 2); ?>
            </p>
          </div>

 
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

  <div class="text-center mt-12">
      <button id="view-more-packages"
        class="inline-block px-8 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl transition-all gradient-bg text-white"
        onclick="window.location.href='services.php'">
        View More Services
      </button>

  </div>

<section id="about" class="bg-white py-20">
  <div class="max-w-6xl mx-auto px-6 md:px-10">
    <div class="text-center mb-12">
      <h2 class="text-4xl font-heading font-bold text-plum-700 mb-3">
        About MBK GlamHub
      </h2>
      <div class="w-24 h-1 bg-gradient-to-r from-lavender-400 to-plum-500 mx-auto rounded-full"></div>
    </div>

    <div class="grid md:grid-cols-2 gap-10 items-center">
      <!-- Image -->
      <div class="flex justify-center">
        <img src="kyleen_concepcion.jpg" alt="MBK GlamHub Team" class="rounded-2xl shadow-lg border border-lavender-100 object-cover w-full max-w-md">
      </div>

      <!-- Text -->
      <div class="text-gray-700 leading-relaxed">
        <p class="mb-4 text-lg">
          <span class="font-semibold text-plum-700">MBK GlamHub</span> was founded over 
          <span class="font-semibold text-plum-600">15 years ago</span> by 
          <span class="font-semibold text-plum-700">Kyleen Concepcion</span>, a visionary makeup artist 
          with a passion for enhancing natural beauty and confidence through artistry.
        </p>

        <p class="mb-4 text-lg">
          What began as a solo endeavor has gracefully evolved into a thriving collective of 
          <span class="font-semibold text-plum-600">professional makeup artists and hairstylists</span> — 
          all sharing Kyleen’s dedication to creativity, precision, and personalized client care.
        </p>

        <p class="text-lg">
          Today, MBK GlamHub is recognized for delivering exceptional experiences for every occasion — 
          from weddings and debuts to editorial shoots and special events — helping every client look 
          and feel <span class="font-semibold text-plum-700">beautifully themselves</span>.
        </p>
      </div>
    </div>
  </div>
</section>







<!-- Testimonials Section -->
<section id="testimonials" class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">

            <h2 class="text-4xl lg:text-5xl font-bold mb-6">
                <span class="text-gray-900">Clients</span>
                <span class="gradient-text"> Feedback</span>
            </h2>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($positiveReviews as $review): ?>
            <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow border border-lavender-200">
                <div class="flex items-center mb-4">
                    <?php
                        $rating = (int)$review['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $rating
                                ? '<i class="fas fa-star text-yellow-400"></i>'
                                : '<i class="far fa-star text-yellow-400"></i>';
                        }
                    ?>
                </div>
                <p class="text-gray-600 mb-6 italic">
                    <?= htmlspecialchars($review['comment']); ?>
                </p>
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-gradient-to-r from-lavender-400 to-plum-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                        <?= strtoupper(substr($review['client_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900"><?= htmlspecialchars($review['client_name']); ?></div>
                        <div class="text-sm text-gray-600"><?= date('M d, Y', strtotime($review['created_at'])); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="reviews.php" class="inline-block px-8 py-3 gradient-bg text-white rounded-full font-semibold shadow-lg hover:shadow-xl transition-all">
                View More Reviews
            </a>
        </div>
    </div>
</section>

<!-- Live Chat Widget - Only visible when logged in -->
<?php if ($isLoggedIn): ?>
<div id="chatWidget" class="fixed bottom-6 right-6 z-50">

  <!-- Larger Pill Chat Button -->
  <button id="chatButton" class="group flex items-center gap-3 bg-gradient-to-r from-plum-500 to-plum-600 text-white shadow-xl rounded-full px-6 py-3.5 cursor-pointer hover:shadow-2xl hover:scale-105 transition-all duration-300" aria-expanded="false">
    <i class="fas fa-paper-plane text-base"></i>
    <span class="font-medium text-base">Messages</span>
    <div id="unreadBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center hidden">0</div>
  </button>

  <!-- Chat Window -->
  <div id="chatWindow" class="absolute bottom-20 right-0 bg-white rounded-3xl shadow-2xl w-[400px] h-[500px] border border-lavender-200 flex flex-col overflow-hidden hidden">
    <!-- Chat Header -->
    <div class="bg-white text-plum-700 px-4 py-3 rounded-t-3xl flex items-center justify-between border-b border-plum-200 shadow-sm">
      <div class="flex items-center">
        <!-- Font Awesome user icon (now plum accent) -->
        <div class="w-8 h-8 bg-plum-100 rounded-full flex items-center justify-center mr-2 flex-shrink-0">
          <i class="fas fa-user text-plum-600 text-base"></i>
        </div>
        <div class="leading-tight">
          <div class="font-semibold text-base">Make Up By Kyleen</div>
          <div class="text-[11px] text-plum-500">Support Team</div>
        </div>
      </div>
      <button id="closeChatBtn" class="text-plum-600 hover:text-plum-800 transition-colors" aria-label="Close chat">
        <i class="fas fa-times text-sm"></i>
      </button>
    </div>

    <!-- Chat Messages -->
    <div id="chatMessages" class="flex-1 overflow-y-auto p-5 space-y-4 bg-gray-50">
      <div class="flex items-start">
        <div class="w-8 h-8 bg-plum-100 rounded-full flex items-center justify-center mr-2 flex-shrink-0">
          <i class="fas fa-user text-plum-600 text-xs"></i>
        </div>
        <div class="bg-white rounded-xl p-3 max-w-xs shadow-sm">
          <p class="text-sm">Hello! How can I help you today?</p>
          <div class="text-xs text-gray-500 mt-1">Just now</div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="px-5 py-3 border-t border-gray-200 bg-white">
      <div class="flex flex-wrap gap-2">
        <button class="quick-action-btn bg-lavender-100 text-plum-700 px-3 py-1 rounded-full text-xs hover:bg-lavender-200 transition-colors" data-message="I want to book an appointment">
          Book Appointment
        </button>
        <button class="quick-action-btn bg-lavender-100 text-plum-700 px-3 py-1 rounded-full text-xs hover:bg-lavender-200 transition-colors" data-message="What are your prices?">
          Pricing Info
        </button>
        <button class="quick-action-btn bg-lavender-100 text-plum-700 px-3 py-1 rounded-full text-xs hover:bg-lavender-200 transition-colors" data-message="What services do you offer?">
          Services
        </button>
      </div>
    </div>

    <!-- Chat Input -->
    <div class="p-4 border-t border-gray-200 bg-gray-50">
      <div class="flex items-center space-x-2">
        <input type="text" id="chatInput" placeholder="Type your message..." class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:border-plum-500 focus:ring-1 focus:ring-plum-500">
        <button id="sendChatBtn" class="bg-gradient-to-r from-plum-500 to-plum-600 text-white w-10 h-10 flex items-center justify-center rounded-full hover:shadow-md transition-all disabled:opacity-50" aria-label="Send message">
          <i class="fas fa-paper-plane text-sm"></i>
        </button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
  /* Pill button animations */
  @keyframes pillOut {
    to { transform: scale(0.92); opacity: 0; visibility: hidden; }
  }
  @keyframes pillIn {
    from { transform: scale(0.92); opacity: 0; }
    to   { transform: scale(1);    opacity: 1; }
  }
  .pill-hide { animation: pillOut 220ms ease forwards; }
  .pill-show { animation: pillIn 240ms ease forwards; }

  /* Chat window animations */
  @keyframes chatIn {
    from { transform: translateY(10px); opacity: 0; }
    to   { transform: translateY(0);     opacity: 1; }
  }
  @keyframes chatOut {
    from { transform: translateY(0);     opacity: 1; }
    to   { transform: translateY(10px);  opacity: 0; }
  }
  .chat-enter { animation: chatIn 260ms ease forwards; }
  .chat-exit  { animation: chatOut 220ms ease forwards; }
</style>

<script>
/**
 * Single unified script:
 * - Handles animations (pill + chat window)
 * - Handles messaging logic (load/send/poll)
 * - Avoids duplicate listeners/conflicts
 */
document.addEventListener('DOMContentLoaded', function() {
  // Elements
  const chatButton    = document.getElementById('chatButton');
  const chatWindow    = document.getElementById('chatWindow');
  const closeChatBtn  = document.getElementById('closeChatBtn');
  const chatMessages  = document.getElementById('chatMessages');
  const chatInput     = document.getElementById('chatInput');
  const sendChatBtn   = document.getElementById('sendChatBtn');
  const unreadBadge   = document.getElementById('unreadBadge');
  const quickActionBtns = document.querySelectorAll('.quick-action-btn');

  // State
  let isOpen = false;
  let messages = [];
  const currentUserId = <?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null'; ?>;
  const adminId = 0;

  // API
  const apiPath = 'chat_api.php';

  /* ---------- Animations: open/close ---------- */
  function openChat() {
    isOpen = true;

    // Animate pill out
    chatButton.classList.remove('pill-show');
    chatButton.classList.add('pill-hide');
    chatButton.setAttribute('aria-expanded', 'true');

    // Show + animate chat window in
    chatWindow.classList.remove('hidden', 'chat-exit');
    void chatWindow.offsetWidth; // reflow to restart animation in some browsers
    chatWindow.classList.add('chat-enter');

    // Focus, mark read
    chatInput.focus();
    markMessagesAsRead();
    updateUnreadBadge(0);
  }

  function closeChat() {
    isOpen = false;

    // Animate chat window out, then hide at end
    chatWindow.classList.remove('chat-enter');
    chatWindow.classList.add('chat-exit');
    const onOutEnd = () => {
      chatWindow.classList.add('hidden');
      chatWindow.removeEventListener('animationend', onOutEnd);
    };
    chatWindow.addEventListener('animationend', onOutEnd);

    // Animate pill back in
    chatButton.classList.remove('pill-hide');
    chatButton.classList.add('pill-show');
    chatButton.setAttribute('aria-expanded', 'false');
  }

  function toggleChat() {
    if (chatWindow.classList.contains('hidden')) {
      openChat();
    } else {
      closeChat();
    }
  }

  // Click handlers
  chatButton.addEventListener('click', toggleChat);
  closeChatBtn.addEventListener('click', closeChat);

  // ESC closes chat
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !chatWindow.classList.contains('hidden')) {
      closeChat();
    }
  });

  // Make sure pill is visible on first paint
  chatButton.classList.add('pill-show');

  /* ---------- Messaging: load / send / poll ---------- */
  async function loadMessages() {
    try {
      const response = await fetch(`${apiPath}?action=messages&other_user_id=${adminId}`);
      const data = await response.json();
      if (data.success) {
        messages = data.messages || [];
        renderMessages();
        scrollToBottom();
      } else {
        console.error('Failed to load messages:', data.error);
      }
    } catch (error) {
      console.error('Error loading messages:', error);
    }
  }

  function renderMessages() {
    chatMessages.innerHTML = '';

    if (!messages.length) {
      // Keep initial welcome bubble when no messages yet
      const welcome = document.createElement('div');
      welcome.className = 'flex items-start';
      welcome.innerHTML = `
        <div class="w-8 h-8 bg-plum-100 rounded-full flex items-center justify-center mr-2 flex-shrink-0">
          <i class="fas fa-user text-plum-600 text-xs"></i>
        </div>
        <div class="bg-white rounded-xl p-3 max-w-xs shadow-sm">
          <p class="text-sm">Hello! How can I help you today?</p>
          <div class="text-xs text-gray-500 mt-1">Just now</div>
        </div>`;
      chatMessages.appendChild(welcome);
      return;
    }

    messages.forEach(message => {
      const isCurrentUser = message.sender_id == currentUserId;
      const wrap = document.createElement('div');
      wrap.className = 'flex items-start mb-3 ' + (isCurrentUser ? 'justify-end' : '');

      if (isCurrentUser) {
        wrap.innerHTML = `
          <div class="bg-gradient-to-r from-plum-500 to-plum-600 text-white rounded-lg p-3 max-w-xs shadow-sm">
            <p class="text-sm">${escapeHtml(message.message)}</p>
            <div class="text-xs opacity-75 mt-1">${formatTime(message.created_at)}</div>
          </div>`;
      } else {
        wrap.innerHTML = `
          <div class="w-8 h-8 bg-plum-100 rounded-full flex items-center justify-center mr-2 flex-shrink-0">
            <i class="fas fa-user text-plum-600 text-xs"></i>
          </div>
          <div class="bg-white rounded-lg p-3 max-w-xs shadow-sm">
            <p class="text-sm">${escapeHtml(message.message)}</p>
            <div class="text-xs text-gray-500 mt-1">${formatTime(message.created_at)}</div>
          </div>`;
      }

      chatMessages.appendChild(wrap);
    });
  }

  async function sendMessage() {
    const message = chatInput.value.trim();
    if (!message) return;

    chatInput.value = '';
    chatInput.disabled = true;
    sendChatBtn.disabled = true;

    try {
      const response = await fetch(`${apiPath}?action=send_message`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ receiver_id: adminId, message })
      });
      const data = await response.json();

      if (data.success) {
        messages.push(data.message);
        renderMessages();
        scrollToBottom();
      } else {
        console.error('Failed to send message:', data.error);
        alert('Failed to send message: ' + data.error);
      }
    } catch (error) {
      console.error('Error sending message:', error);
      alert('Failed to send message. Please try again.');
    } finally {
      chatInput.disabled = false;
      sendChatBtn.disabled = false;
      chatInput.focus();
    }
  }

  async function markMessagesAsRead() {
    try {
      await fetch(`${apiPath}?action=mark_read`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ other_user_id: adminId })
      });
      updateUnreadBadge(0);
    } catch (error) {
      console.error('Error marking messages as read:', error);
    }
  }

  async function checkUnreadMessages() {
    try {
      const response = await fetch(`${apiPath}?action=unread_count`);
      const data = await response.json();
      if (data.success) {
        updateUnreadBadge(data.unread_count);
      }
    } catch (error) {
      console.error('Error checking unread messages:', error);
    }
  }

  function updateUnreadBadge(count) {
    if (count > 0 && !isOpen) {
      unreadBadge.textContent = count;
      unreadBadge.classList.remove('hidden');
    } else {
      unreadBadge.classList.add('hidden');
    }
  }

  function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function formatTime(ts) {
    const date = new Date(ts);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  // Composer events
  sendChatBtn.addEventListener('click', sendMessage);
  chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendMessage();
  });
  quickActionBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      const message = this.getAttribute('data-message');
      chatInput.value = message;
      sendMessage();
    });
  });

  // Initial load + polling
  loadMessages();
  checkUnreadMessages();
  setInterval(() => {
    loadMessages();
    if (!isOpen) checkUnreadMessages();
  }, 5000);
});
</script>

<script>
  // Smooth scrolling for anchor links (kept as-is)
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // (Your other page scripts—carousel, logout, etc.—remain unchanged)
  const cards = document.querySelectorAll("#cards li");
  let current = 0;
  const slider = document.getElementById("cards");
  function updateCarousel() {
    const offset = -current * slider.clientWidth;
    slider.style.transform = `translateX(${offset}px)`;
  }
  setInterval(() => {
    if (!slider) return;
    current = (current + 1) % cards.length;
    updateCarousel();
  }, 5000);

  document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    Swal.fire({
      title: 'Are you sure?',
      text: "You’ll be logged out of your account.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#4b2840', // plum
      cancelButtonColor: '#a06c9e', // lavender
      confirmButtonText: 'Yes, sign out',
      cancelButtonText: 'Cancel',
      background: '#fff',
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'logout.php';
      }
    });
  });
</script>


  <!-- Footer -->
<footer class="relative left-1/2 right-1/2 ml-[-50vw] mr-[-50vw] w-screen bg-gray-950 text-white pt-14 pb-12 mt-10">
  <div class="max-w-7xl mx-auto px-6">
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-10">
      <div class="space-y-4">
        <div class="flex items-center space-x-2">
          <img src="mbk_white.png" alt="Make up By Kyleen Logo" class="h-12 w-auto">
        </div>
        <p class="text-gray-400">
          Elevating beauty through professional makeup artistry and hair styling services.
        </p>
        <div class="flex space-x-4">
          <a href="https://www.instagram.com/makeupby_kyleen/" class="text-gray-400 hover:text-[#a06c9e] transition-colors">
            <i class="fab fa-instagram text-xl"></i>
          </a>
          <a href="https://www.facebook.com/bianca.mendoza2" class="text-gray-400 hover:text-[#a06c9e] transition-colors">
            <i class="fab fa-facebook text-xl"></i>
          </a>
          <a href="mailto:" class="text-gray-400 hover:text-[#a06c9e] transition-colors">
            <i class="fas fa-envelope text-xl"></i>
          </a>
        </div>
      </div>

      <div>
        <h3 class="text-lg font-semibold mb-4">Services</h3>
        <ul class="space-y-2 text-gray-400">
          <li><a href="#" class="hover:text-[#a06c9e] transition-colors">Bridal Makeup</a></li>
          <li><a href="#" class="hover:text-[#a06c9e] transition-colors">Event Makeup</a></li>
          <li><a href="#" class="hover:text-[#a06c9e] transition-colors">Graduation</a></li>
        </ul>
      </div>

      <div>
        <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
        <ul class="space-y-2 text-gray-400">
          <li><a href="#about" class="hover:text-[#a06c9e] transition-colors">About</a></li>
          <li><a href="artist_portfolio.php" class="hover:text-[#a06c9e] transition-colors">Portfolio</a></li>
          <li><a href="reviews.php" class="hover:text-[#a06c9e] transition-colors">Reviews</a></li>
          <li><a href="#" class="hover:text-[#a06c9e] transition-colors">Contact</a></li>
        </ul>
      </div>

      <div>
        <h3 class="text-lg font-semibold mb-4">Contact Info</h3>
        <div class="space-y-3 text-gray-400">
          <div class="flex items-center">
            <i class="fas fa-phone text-[#a06c9e] mr-3"></i>
            <span>09777612938</span>
          </div>
          <div class="flex items-center">
            <i class="fas fa-map-marker-alt text-[#a06c9e] mr-3"></i>
            <span>123 Beauty Ave, City, ST 12345</span>
          </div>
        </div>
      </div>
    </div>

    <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
      <p>&copy; 2025 Make up By Kyleen. All rights reserved.</p>
    </div>
  </div>
</footer>


</body>
</html>
