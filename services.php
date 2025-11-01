<?php
// Start session BEFORE any output
session_start();

require __DIR__ . '/db.php'; // creates $pdo (PDO connection)

// Read login state from session
$user_id      = $_SESSION['user_id']   ?? null;
$isLoggedIn   = !empty($user_id);
$greetingName = 'Guest';

// If logged in, prefer the cached session name, otherwise fetch from DB
if ($isLoggedIn) {
  if (!empty($_SESSION['user_name'])) {
    $greetingName = $_SESSION['user_name'];
  } else {
    // Pull name from DB (adjust columns to your schema)
    $stmt = $pdo->prepare("
      SELECT TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) AS full_name
      FROM users
      WHERE user_id = :id
      LIMIT 1
    ");
    $stmt->execute([':id' => $user_id]);
    $fullName = $stmt->fetchColumn();
    if ($fullName) {
      $greetingName = $fullName;
      $_SESSION['user_name'] = $fullName; // cache for next request
    }
  }
}

// FETCH PACKAGES
try {
  $stmt = $pdo->query("
    SELECT package_id, name, description, event_type, price_range, price
    FROM packages
    ORDER BY package_id ASC
  ");
  $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  http_response_code(500);
  die('Database error: ' . htmlspecialchars($e->getMessage()));
}

// Optional: prefill search from ?q=
$prefillQ = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
  $stmt = $pdo->query("
    SELECT package_id, name, description, event_type, price_range, price
    FROM packages
    ORDER BY package_id ASC
  ");
  $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  http_response_code(500);
  die('Database error: ' . htmlspecialchars($e->getMessage()));
}

// Optional: prefill search from ?q=
$prefillQ = isset($_GET['q']) ? trim($_GET['q']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Services | MBK GlamHub</title>
  <link rel="icon" type="image/png" href="mbk_logo.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Theme pulled from homepage.php -->
  <style>
    :root{
      /* homepage gradient colors */
      --plum-light: #a06c9e;        /* gradient start */
      --plum-deep:  #4b2840;        /* gradient end */
      --plum-hov-a: #804f7e;        /* hover start */
      --plum-hov-b: #673f68;        /* hover end */

      /* supporting tints seen on homepage */
      --tint-band:   #f9f2f7;       /* section band bg */
      --tint-chip:   #f1e3ef;       /* chip bg */
      --tint-border: #e0c5dc;       /* borders */
      --tint-border-2:#d8d1e8;      /* optional deeper border */

      --text: #1f2937;
      --muted:#6b7280;
      --white:#ffffff;
    }

    /* keep your existing class names mapping to homepage tints */
    .bg-lavender-50{ background-color: var(--tint-band) !important; }
    .bg-lavender-100{ background-color: var(--tint-chip) !important; }
    .border-lavender-200{ border-color: var(--tint-border) !important; }
    .border-lavender-300{ border-color: var(--tint-border-2) !important; }

    .text-plum-600{ color: var(--plum-deep) !important; } /* active nav */
    .text-plum-700{ color: var(--plum-deep) !important; } /* headings */

    /* gradients exactly like homepage */
    .gradient-bg{
      background: linear-gradient(to right, var(--plum-light), var(--plum-deep));
    }
    .gradient-bg:hover{
      background: linear-gradient(to right, var(--plum-hov-a), var(--plum-hov-b));
    }
    .gradient-text{
      background: linear-gradient(to right, var(--plum-light), var(--plum-deep));
      -webkit-background-clip: text; background-clip: text; color: transparent;
    }

    /* cards / chips */
    .card{
      border-radius: 1rem;
      border: 1px solid var(--tint-border);
      background: var(--white);
      box-shadow: 0 6px 18px rgba(17,24,39,0.06);
      transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .card:hover{
      transform: translateY(-3px);
      box-shadow: 0 10px 26px rgba(17,24,39,0.12);
      border-color: var(--tint-border-2);
    }
    .chip{
      display: inline-flex; align-items: center; border-radius: 9999px;
      padding: .25rem .75rem; font-size: .875rem; line-height: 1.4;
    }

    /* nav dropdown */
    .nav-group:hover .nav-dropdown{ opacity:1; visibility:visible; transform: translateY(0); }
    .nav-dropdown{
      opacity:0; visibility:hidden; transform: translateY(4px);
      transition: all .18s ease;
    }
    .gradient-text {
  background: linear-gradient(to right, #a06c9e, #4b2840);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.gradient-bg {
  background: linear-gradient(to right, #a06c9e, #4b2840);
}
  /* Force a clean white background site-wide */
  html, body { background:#fff !important; }
  .bg-lavender-50,
  .bg-lavender-100,
  .from-lavender-50,
  .via-lavender-100,
  .to-lavender-200 { background-color:#fff !important; background-image:none !important; }

  /* Make sure cards remain white */
  .card { background:#fff; }
  </style>
</head>
<body class="bg-white text-gray-900 opacity-0 translate-y-4 transition-all duration-700 ease-out" onload="document.body.classList.remove('opacity-0','translate-y-4')">


<!-- Header -->
  <header class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-lavender-200">
    <div class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <a href="homepage.php">
            <img src="mbk_logo.png" alt="Make up By Kyleen Logo" class="h-14 w-auto">
          </a>
        </div>

        <nav class="hidden md:flex items-center space-x-8">
          <a href="services.php" class="text-plum-700 font-semibold">Services</a>
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

<!-- Search band -->
<section class="py-12 bg-white border-b border-lavender-200">
  <div class="max-w-7xl mx-auto px-4 text-center">
    <h2 class="text-4xl lg:text-5xl font-bold mb-6 gradient-text">
      Our Services
    </h2>

    <div class="mx-auto h-1.5 w-24 rounded-full gradient-bg"></div>

    <p class="mt-6 text-gray-600 max-w-2xl mx-auto text-base md:text-lg leading-relaxed">
      Find the perfect look for your event. Search by name, event type, or price range.
    </p>

    <div class="max-w-xl mx-auto mt-8">
      <input
        type="text"
        id="serviceSearch"
        placeholder="Search for a service..."
        value="<?php echo htmlspecialchars($prefillQ); ?>"
        class="w-full px-5 py-3 border border-lavender-200 rounded-full focus:outline-none focus:ring-2 shadow-sm"
        style="--tw-ring-color: #4b2840;"
      >
    </div>
  </div>
</section>




<!-- Services Grid -->
<section class="py-16">
  <div class="max-w-7xl mx-auto px-4">
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8" id="services-container">
      <?php if (!empty($packages)): ?>
        <?php foreach ($packages as $pkg): ?>
          <div class="service-card card overflow-hidden">
            <!-- ribbon: EXACT homepage gradient -->
            <div class="h-1.5 w-full gradient-bg"></div>

            <div class="p-7">
              <div class="mb-5">
                <h3 class="service-name text-2xl font-bold text-gray-900 mb-2">
                  <?php echo htmlspecialchars($pkg['name'] ?? ''); ?>
                </h3>

                <p class="service-description text-gray-600 leading-relaxed">
                  <?php echo htmlspecialchars($pkg['description'] ?? ''); ?>
                </p>
              </div>

              <div class="flex flex-wrap items-center gap-2.5 mb-6">
                <span class="chip" style="background-color: var(--tint-chip); color: var(--plum-deep);">
                  <?php echo htmlspecialchars($pkg['event_type'] ?? ''); ?>
                </span>
                <span class="chip bg-white text-gray-700 border border-lavender-200">
                  <?php echo htmlspecialchars($pkg['price_range'] ?? ''); ?> Range
                </span>
              </div>

              <div class="flex items-end justify-between">
                <div>
                  <p class="text-sm text-gray-500">Starting at</p>
                  <!-- price uses SAME gradient as homepage text -->
                  <p class="text-3xl font-extrabold gradient-text tracking-tight">
                    ₱<?php
                      $price = $pkg['price'];
                      echo is_null($price) ? '0.00' : number_format((float)$price, 2);
                    ?>
                  </p>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="col-span-full text-center text-gray-500">No services available right now.</p>
      <?php endif; ?>
    </div>

    <p id="no-results" class="text-center text-gray-500 mt-8 hidden">No services match your search.</p>
  </div>
</section>

<!-- JS: client-side live search -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('serviceSearch');
  const cards = Array.from(document.querySelectorAll('.service-card'));
  const noResults = document.getElementById('no-results');

  function applySearch() {
    const q = searchInput.value.toLowerCase().trim();
    let visible = 0;

    cards.forEach(card => {
      const name = card.querySelector('.service-name')?.textContent.toLowerCase() || '';
      const desc = card.querySelector('.service-description')?.textContent.toLowerCase() || '';
      const chips = Array.from(card.querySelectorAll('.chip')).map(s => s.textContent.toLowerCase()).join(' ');
      const haystack = [name, desc, chips].join(' ');

      const match = !q || haystack.includes(q);
      card.style.display = match ? '' : 'none';
      if (match) visible++;
    });

    noResults.classList.toggle('hidden', visible !== 0);
  }

  // Debounce
  let t;
  searchInput.addEventListener('input', () => {
    clearTimeout(t);
    t = setTimeout(applySearch, 140);
  });

  // Initial run (honors ?q=)
  applySearch();
});
</script>



</body>
</html>
