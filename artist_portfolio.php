
<?php
session_start();


$isLoggedIn = isset($_SESSION['user_id']);
$greetingName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';

?>
<!DOCTYPE html>
<html lang="en" class="min-h-screen">
<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/png" href="mbk_logo.png" />
  <title>MBK GlamHub | Teams Portfolio </title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">

  <!-- Tailwind (CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            heading: ['Poppins','sans-serif'],
            body: ['Inter','sans-serif']
          },
          colors: {
            lavender: {
              50:'#F8F5FA',100:'#F0EBF5',200:'#E0D6EB',300:'#D0C1E1',400:'#C0ACD7',
              500:'#a06c9e',600:'#804f7e',700:'#60325e',800:'#40153e',900:'#20001e',
            },
            plum: {
              50:'#F5F0F5',100:'#EBE0EB',200:'#D7C0D7',300:'#C3A0C3',400:'#AF80AF',
              500:'#4b2840',600:'#673f68',700:'#4A2D4B',800:'#2E1B2E',900:'#120912',
            }
          },
          boxShadow: {
            soft: '0 6px 20px rgba(0,0,0,0.06)',
            inner: 'inset 0 1px 2px rgba(0,0,0,0.04)'
          }
        }
      }
    }
  </script>

  <style>
    .gradient-text{
      background: linear-gradient(to right,#a06c9e,#4b2840);
      -webkit-background-clip:text;
      -webkit-text-fill-color:transparent;
      background-clip:text;
    }
    .gradient-bg{ background:linear-gradient(to right,#a06c9e,#4b2840); }
    .gradient-bg:hover{ background:linear-gradient(to right,#804f7e,#673f68); }
    html{ scroll-behavior:smooth; }
    
  </style>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="flex flex-col min-h-screen bg-white font-body text-gray-800 opacity-0 translate-y-4 transition-all duration-700 ease-out" onload="document.body.classList.remove('opacity-0','translate-y-4')">

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
          <a href="services.php" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">Services</a>
          <a href="homepage.php#about" class="text-gray-700 hover:text-plum-600 transition-colors font-medium">About</a>
          <a href="artist_portfolio.php" class="text-plum-700 font-semibold">Portfolio</a>
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

  <!-- Main -->
  <main class="flex-1">
    <section class="max-w-6xl mx-auto px-4 py-10 md:py-12">

      <!-- Title -->
      <h1 class="text-4xl md:text-5xl font-heading font-bold text-center gradient-text mb-3">Our Talented Teams</h1>
      <div class="flex justify-center mb-10">
        <div class="h-1 w-28 rounded-full bg-lavender-300"></div>
      </div>

      <!-- Team Selector (chips) -->
      <div class="flex flex-wrap justify-center gap-3 md:gap-4 mb-10">
        <?php
          include 'db.php';
          $teams = $conn->query("
            SELECT t.team_id, t.name, t.profile_image,
                   mu.first_name AS makeup_first, mu.last_name AS makeup_last, mu.bio AS makeup_bio, mu.sex AS makeup_gender,
                   hu.first_name AS hair_first, hu.last_name AS hair_last, hu.bio AS hair_bio, hu.sex AS hair_gender
            FROM teams t
            JOIN users mu ON mu.user_id = t.makeup_artist_id
            JOIN users hu ON hu.user_id = t.hairstylist_id
          ");
          while ($team = $teams->fetch_assoc()) {
            echo "<button data-team-id='{$team['team_id']}'
                    class='team-select-btn px-5 py-2.5 rounded-xl border border-lavender-200 bg-white text-plum-700 font-semibold hover:bg-lavender-50 hover:border-lavender-300 focus:outline-none focus:ring-2 focus:ring-plum-300 transition'>
                    {$team['name']}
                  </button>";
          }
        ?>
      </div>

      <!-- Team Profile (kept your IDs) -->
      <div id="team-profile" class="flex flex-col gap-8 mb-10 text-center">
        <div class="flex flex-col items-center">
          <div id="team-image" class="w-40 h-40 rounded-full overflow-hidden shadow-soft border-4 border-plum-200 mb-4 bg-lavender-50">
            <img src="" alt="Team Profile" class="w-full h-full object-cover">
          </div>
          <h2 id="team-name" class="text-3xl font-heading font-bold text-plum-700 mb-3">Team Name</h2>
          <p class="text-gray-700 leading-relaxed max-w-2xl mx-auto">
            Meet the incredible professionals behind this team, dedicated to bringing your vision to life with their combined expertise in makeup and hair artistry.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Makeup Artist -->
          <div class="p-0 text-center">
            <div class="mb-2 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border border-lavender-200 shadow-inner">
              <i class="fas fa-paint-brush text-plum-600 text-sm"></i>
            </div>
            <h3 id="makeup-artist-name" class="text-2xl font-heading font-bold text-plum-700 mb-1">Makeup Artist Name</h3>
            <p id="makeup-artist-bio" class="text-gray-700 leading-relaxed text-sm mb-2">Makeup artist bio here.</p>
            <p id="makeup-artist-gender" class="text-gray-500 text-xs">(Gender)</p>
          </div>

          <!-- Hair Stylist -->
          <div class="p-0 text-center">
            <div class="mb-2 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white border border-lavender-200 shadow-inner">
              <i class="fas fa-scissors text-plum-600 text-sm"></i>
            </div>
            <h3 id="hairstylist-name" class="text-2xl font-heading font-bold text-plum-700 mb-1">Hair Stylist Name</h3>
            <p id="hairstylist-bio" class="text-gray-700 leading-relaxed text-sm mb-2">Hair stylist bio here.</p>
            <p id="hairstylist-gender" class="text-gray-500 text-xs">(Gender)</p>
          </div>
        </div>
      </div>

      <!-- Portfolio Gallery -->
      <h3 class="text-2xl font-heading font-bold text-plum-700 text-center mb-6">Team Portfolio Highlights</h3>
      <div id="portfolio-gallery" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
        <!-- images injected by JS -->
      </div>
    </section>
  </main>

   <!-- Image Modal -->
<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 z-[100] hidden" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="relative w-full h-full flex items-center justify-center p-4 bg-white/95">
    <img id="modalImage" alt="Portfolio full size" class="max-h-[92vh] max-w-[96vw] object-contain rounded-xl shadow-2xl select-none" />
    <div class="absolute top-4 right-4 flex items-center gap-2">
      <button id="fsToggleBtn" ...>...</button>
      <button id="modalCloseBtn" ...>...</button>
    </div>
  </div>
</div>

  <!-- Footer -->
  <footer class="bg-gray-950 text-white pt-14 pb-12 mt-10">
    <div class="max-w-6xl mx-auto px-4">
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-10">
        <div class="space-y-4">
          <div class="flex items-center space-x-2">
            <img src="mbk_white.png" alt="Make up By Kyleen Logo" class="h-10 w-auto">
          </div>
          <p class="text-gray-400">Elevating beauty through professional makeup artistry and hair styling services.</p>
          <div class="flex space-x-4">
            <a href="https://www.instagram.com/makeupby_kyleen/" class="text-gray-400 hover:text-lavender-300 transition-colors">
              <i class="fab fa-instagram text-xl"></i>
            </a>
            <a href="https://www.facebook.com/bianca.mendoza2" class="text-gray-400 hover:text-lavender-300 transition-colors">
              <i class="fab fa-facebook text-xl"></i>
            </a>
            <a href="https://mail.google.com/mail/u/0/#inbox?compose=CllgCJfmrJhzqpXPCkWVMPcGRqJXnXJzgxqrpcHbXwdSJpRglHbHvnmpVqspJhQnRtMmsDztXlq" class="text-gray-400 hover:text-lavender-300 transition-colors">
              <i class="fas fa-envelope"></i>
            </a>
          </div>
        </div>

        <div>
          <h3 class="text-lg font-semibold mb-4">Services</h3>
          <ul class="space-y-2 text-gray-400">
            <li><a href="#" class="hover:text-lavender-300 transition-colors">Bridal Makeup</a></li>
            <li><a href="#" class="hover:text-lavender-300 transition-colors">Event Makeup</a></li>
            <li><a href="#" class="hover:text-lavender-300 transition-colors">Graduation</a></li>
          </ul>
        </div>

        <div>
          <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
          <ul class="space-y-2 text-gray-400">
            <li><a href="#about" class="hover:text-lavender-300 transition-colors">About</a></li>
            <li><a href="#portfolio" class="hover:text-lavender-300 transition-colors">Portfolio</a></li>
            <li><a href="#testimonials" class="hover:text-lavender-300 transition-colors">Reviews</a></li>
            <li><a href="#" class="hover:text-lavender-300 transition-colors">Contact</a></li>
          </ul>
        </div>

        <div>
          <h3 class="text-lg font-semibold mb-4">Contact Info</h3>
          <div class="space-y-3 text-gray-400">
            <div class="flex items-center">
              <i class="fas fa-phone text-lavender-300 mr-3"></i>
              <span>09777612938</span>
            </div>
            <div class="flex items-center">
              <i class="fas fa-map-marker-alt text-lavender-300 mr-3"></i>
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
<script>
async function renderTeam(teamId) {
    try {
        const response = await fetch(`get_team_data.php?team_id=${teamId}`);
        const data = await response.json();

        if (data.error || !data.team) {
            console.error("Error loading team data:", data.error);
            return;
        }

        const t = data.team;

        // TEAM INFO
        const teamNameEl = document.getElementById('team-name');
        const teamImageContainer = document.getElementById('team-image');
        const teamImage = teamImageContainer.querySelector('img');

        teamNameEl.textContent = t.team_name;

        // ✅ Hide profile if missing or empty
        if (t.profile_image && t.profile_image.trim() !== '') {
            teamImage.src = t.profile_image;
            teamImageContainer.classList.remove('hidden');
        } else {
            teamImageContainer.classList.add('hidden');
        }

        // MAKEUP ARTIST INFO
        document.getElementById('makeup-artist-name').textContent = `${t.makeup_first} ${t.makeup_last}`;
        document.getElementById('makeup-artist-bio').textContent = t.makeup_bio || 'No bio available.';
        document.getElementById('makeup-artist-gender').textContent = `(${t.makeup_gender})`;

        // HAIRSTYLIST INFO
        document.getElementById('hairstylist-name').textContent = `${t.hair_first} ${t.hair_last}`;
        document.getElementById('hairstylist-bio').textContent = t.hair_bio || 'No bio available.';
        document.getElementById('hairstylist-gender').textContent = `(${t.hair_gender})`;

        // PORTFOLIO GALLERY
        const gallery = document.getElementById('portfolio-gallery');
        gallery.innerHTML = ''; // Clear previous items

        if (!data.uploads || data.uploads.length === 0) {
            gallery.innerHTML = `<p class="text-center text-gray-500 col-span-full">No portfolio highlights uploaded yet.</p>`;
        } else {
            data.uploads.forEach(imageUrl => {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'relative group overflow-hidden rounded-lg shadow-md border border-lavender-100 cursor-pointer aspect-[3/4]';
                imgContainer.innerHTML = `
                    <img 
                        src="${imageUrl}" 
                        alt="Portfolio image for ${t.team_name}" 
                        onclick="showModal('${imageUrl}')"
                        class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                    >
                    <div class="absolute inset-0 bg-plum-700/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <i class="fas fa-eye text-white text-3xl"></i>
                    </div>`;
                gallery.appendChild(imgContainer);
            });
        }

        // BUTTON HIGHLIGHT
        document.querySelectorAll('.team-select-btn').forEach(btn => {
            if (parseInt(btn.dataset.teamId) === teamId) {
                btn.classList.remove('bg-lavender-200', 'text-plum-700');
                btn.classList.add('bg-plum-500', 'text-white');
            } else {
                btn.classList.remove('bg-plum-500', 'text-white');
                btn.classList.add('bg-lavender-200', 'text-plum-700');
            }
        });

    } catch (err) {
        console.error("Failed to load team:", err);
    }
}

// ✅ Event listeners for buttons
document.querySelectorAll('.team-select-btn').forEach(button => {
    button.addEventListener('click', e => renderTeam(parseInt(button.dataset.teamId)));
});

// ✅ Load first team automatically on page load
document.addEventListener('DOMContentLoaded', () => {
    const firstTeamButton = document.querySelector('.team-select-btn');
    if (firstTeamButton) {
        const firstTeamId = parseInt(firstTeamButton.dataset.teamId);
        renderTeam(firstTeamId);

        // Optional: visually highlight it right away
        firstTeamButton.classList.remove('bg-lavender-200', 'text-plum-700');
        firstTeamButton.classList.add('bg-plum-500', 'text-white');
    }
});
data.uploads.forEach(imageUrl => {
  const imgContainer = document.createElement('div');
  imgContainer.className = 'relative group overflow-hidden rounded-lg shadow-md border border-lavender-100 cursor-pointer aspect-[3/4]';

imgContainer.innerHTML = `
  <img 
      src="${imageUrl}" 
      alt="Portfolio image for ${t.team_name}" 
      onclick="showModal('${imageUrl}')"
      class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
  >`;
;

  gallery.appendChild(imgContainer);
});

</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const dropdownTrigger = document.querySelector(".nav-group > span");
  const dropdownMenu = document.querySelector(".nav-dropdown");

  if (dropdownMenu) dropdownMenu.style.display = "none"; // hidden by default

  dropdownTrigger?.addEventListener("click", (e) => {
    e.stopPropagation();
    dropdownMenu.style.display =
      dropdownMenu.style.display === "block" ? "none" : "block";
  });

  // Close dropdown when clicking outside
  document.addEventListener("click", (e) => {
    if (!dropdownTrigger?.contains(e.target)) {
      dropdownMenu.style.display = "none";
    }
  });
});
</script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const logoutBtn = document.getElementById('logoutBtn');
  if (!logoutBtn) return;

  logoutBtn.addEventListener('click', function (e) {
    e.preventDefault(); // prevent instant redirect

    Swal.fire({
      title: 'Sign out?',
      text: "Are you sure you want to log out of your account?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#4b2840', // plum color from your theme
      cancelButtonColor: '#a06c9e', // lavender tone
      confirmButtonText: 'Yes, sign out',
      cancelButtonText: 'Cancel',
      background: '#fff',
      color: '#333',
    }).then((result) => {
      if (result.isConfirmed) {
        // proceed with logout
        window.location.href = 'logout.php';
      }
    });
  });
});
</script>


</body>
</html>