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

// Fetch packages
$stmt = $pdo->prepare("SELECT * FROM packages ORDER BY package_id ASC");
$stmt->execute();
$packages = $stmt->fetchAll();

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
      <a href="homepage.php"><img src="mbk_logo.png" alt="Make up By Kyleen Logo" class="h-14 w-auto"></a>
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
     <a href="#" 
   id = "logoutBtn" 
   class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
   Sign Out
</a>

    <?php else: ?>
      <a href="login.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Log In</a>
    <?php endif; ?>
  </div>
</div>

      </nav>
    </div>
  </div>
</header>

<!-- Hero Section -->
<section class="relative py-20 lg:py-32 overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-r from-lavender-300/10 to-plum-200/10"></div>
  <div class="container mx-auto px-4 relative">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
      <div class="space-y-8">
        <div class="space-y-4">

          <h1 class="text-5xl lg:text-6xl font-bold leading-tight">
            <span class="gradient-text">Elevate</span>
            <span class="text-gray-900"> Your Beauty</span>
          </h1>
          <p class="text-xl text-gray-600 leading-relaxed">
            Experience luxury makeup artistry and hair styling that transforms your natural beauty into pure elegance.
          </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-4">
          <button 
            onclick="<?php echo $isLoggedIn ? 'window.location.href=\'group_booking.php\'' : 'window.location.href=\'login.php\''; ?>"
            class="gradient-bg text-white px-8 py-4 rounded-md font-medium transition-all"
          >
          <i class="fas fa-calendar-alt mr-2"></i>
            Book Appointment
          </button>

        </div>
      </div>
            <div class="relative">
              <div class="relative w-full rounded-3xl overflow-hidden shadow-2xl h-96 lg:h-[600px]">
                <ul id="cards" class="absolute inset-0 flex transition-transform duration-700 ease-in-out">
                  <li class="flex-shrink-0 w-full h-full">
                    <img src="photo1.jpg" alt="Photo 1" class="w-full h-full object-cover rounded-3xl" />
                  </li>
                  <li class="flex-shrink-0 w-full h-full">
                    <img src="photo2.jpg" alt="Photo 2" class="w-full h-full object-cover rounded-3xl" />
                  </li>
                  <li class="flex-shrink-0 w-full h-full">
                    <img src="photo3.jpg" alt="Photo 3" class="w-full h-full object-cover rounded-3xl" />
                  </li>
                </ul>
              </div>
            </div>
    </div>
  </div>
</section>

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
      <div class="group bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 border border-lavender-200 hover:border-lavender-300">
        <div class="mb-6">
          <div class="w-16 h-16 bg-gradient-to-r from-lavender-100 to-plum-100 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
              <i class="fas fa-gem text-2xl text-plum-600"></i>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($package['name']); ?></h3>
          <p class="text-gray-600 mb-4"><?= htmlspecialchars($package['description']); ?></p>
          <div class="text-2xl font-bold gradient-text mb-4">
              ₱<?= number_format($package['price'], 2); ?>
          </div>
        </div>
        <ul class="space-y-2 mb-6">
          <li class="flex items-center text-gray-600">
            <i class="fas fa-check-circle text-plum-500 mr-2"></i>
            Event Type: <?= htmlspecialchars($package['event_type']); ?>
          </li>
          <li class="flex items-center text-gray-600">
            <i class="fas fa-check-circle text-plum-500 mr-2"></i>
            Price Range: <?= htmlspecialchars($package['price_range']); ?>
          </li>
        </ul>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if($totalPackages > $initialLimit): ?>
    <div class="text-center mt-12">
        <button id="view-more-packages" class="inline-block px-8 py-3 gradient-bg text-white rounded-full font-semibold shadow-lg hover:shadow-xl transition-all">
            View More Services
        </button>
    </div>
    <?php endif; ?>

  </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const packages = <?php echo json_encode($packages); ?>;
    const initialLimit = <?= $initialLimit ?>;
    let displayed = initialLimit;

    const container = document.getElementById("services-container");
    const btn = document.getElementById("view-more-packages");

    if(btn){
        btn.addEventListener("click", () => {
            const nextBatch = packages.slice(displayed, displayed + initialLimit);
            nextBatch.forEach(pkg => {
                const card = document.createElement("div");
                card.className = "group bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 border border-lavender-200 hover:border-lavender-300";
                card.innerHTML = `
                    <div class="mb-6">
                        <div class="w-16 h-16 bg-gradient-to-r from-lavender-100 to-plum-100 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-gem text-2xl text-plum-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">${pkg.name}</h3>
                        <p class="text-gray-600 mb-4">${pkg.description}</p>
                        <div class="text-2xl font-bold gradient-text mb-4">
                            ₱${Number(pkg.price).toLocaleString(undefined, {minimumFractionDigits:2})}
                        </div>
                    </div>
                    <ul class="space-y-2 mb-6">
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check-circle text-plum-500 mr-2"></i>
                            Event Type: ${pkg.event_type}
                        </li>
                        <li class="flex items-center text-gray-600">
                            <i class="fas fa-check-circle text-plum-500 mr-2"></i>
                            Price Range: ${pkg.price_range}
                        </li>
                    </ul>
                `;
                container.appendChild(card);
            });
            displayed += nextBatch.length;
            if(displayed >= packages.length){
                btn.style.display = "none";
            }
        });
    }
});
</script>




<!-- Testimonials Section -->
<section id="testimonials" class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <span class="inline-flex items-center bg-lavender-100 text-plum-700 px-4 py-2 rounded-full text-sm font-medium mb-4">
                Testimonials
            </span>
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

<!-- Footer -->
<footer class="bg-gray-900 text-white py-16">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="space-y-4">
        <div class="flex items-center space-x-2">
          <img src="mbk_white.png" alt="Make up By Kyleen Logo" class="h-10 w-auto">
        </div>

                <p class="text-gray-400">
                    Elevating beauty through professional makeup artistry and hair styling services.
                </p>
                <div class="flex space-x-4">
                    <a href="https://www.instagram.com/makeupby_kyleen/" class="text-gray-400 hover:text-lavender-400 transition-colors">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                    <a href="https://www.facebook.com/bianca.mendoza2" class="text-gray-400 hover:text-lavender-400 transition-colors">
                        <i class="fab fa-facebook text-xl"></i>
                    </a>
                    <a href="https://mail.google.com/mail/u/0/#inbox?compose=CllgCJfmrJhzqpXPCkWVMPcGRqJXnXJzgxqrpcHbXwdSJpRglHbHvnmpVqspJhQnRtMmsDztXlq" class="text-gray-400 hover:text-lavender-400 transition-colors">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Services</h3>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="#" class="hover:text-lavender-400 transition-colors">Bridal Makeup</a></li>
                    <li><a href="#" class="hover:text-lavender-400 transition-colors">Event Makeup</a></li>
                    <li><a href="#" class="hover:text-lavender-400 transition-colors">Hair Styling</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                <ul class="space-y-2 text-gray-400">
                    <li><a href="#about" class="hover:text-lavender-400 transition-colors">About</a></li>
                    <li><a href="artist_portfolio.php" class="hover:text-lavender-400 transition-colors">Portfolio</a></li>
                    <li><a href="#testimonials" class="hover:text-lavender-400 transition-colors">Reviews</a></li>
                    <li><a href="#" class="hover:text-lavender-400 transition-colors">Contact</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Contact Info</h3>
                <div class="space-y-3 text-gray-400">
                    <div class="flex items-center">
                        <i class="fas fa-phone text-lavender-400 mr-3"></i>
                        <span>09777612938</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-map-marker-alt text-lavender-400 mr-3"></i>
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

<!-- Live Chat Widget - Only visible when logged in -->
<?php if ($isLoggedIn): ?>
<div id="chatWidget" class="fixed bottom-6 right-6 z-50">
    <!-- Chat Button -->
    <button id="chatButton" class="bg-gradient-to-r from-plum-500 to-plum-600 text-white w-16 h-16 rounded-full shadow-lg cursor-pointer hover:shadow-xl transition-all duration-300 flex items-center justify-center relative">
        <i class="fas fa-comments text-xl"></i>
        <div id="unreadBadge" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center hidden">0</div>
    </button>

    <!-- Chat Window -->
    <div id="chatWindow" class="absolute bottom-20 right-0 bg-white rounded-lg shadow-2xl w-80 h-96 border border-lavender-200 flex flex-col hidden">
        <!-- Chat Header -->
        <div class="bg-gradient-to-r from-plum-500 to-plum-600 text-white p-4 rounded-t-lg flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-user text-sm"></i>
                </div>
                <div>
                    <div class="font-semibold">Make Up By Kyleen</div>
                    <div class="text-xs opacity-90">Support Team</div>
                </div>
            </div>
            <button id="closeChatBtn" class="text-white hover:text-gray-200 transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Chat Messages -->
        <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50">
            <!-- Initial welcome message -->
            <div class="flex items-start">
                <div class="w-8 h-8 bg-plum-100 rounded-full flex items-center justify-center mr-2 flex-shrink-0">
                    <i class="fas fa-user text-plum-600 text-xs"></i>
                </div>
                <div class="bg-white rounded-lg p-3 max-w-xs shadow-sm">
                    <p class="text-sm">Hello! How can I help you today?</p>
                    <div class="text-xs text-gray-500 mt-1">Just now</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="px-4 py-2 border-t border-gray-200">
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
        <div class="p-4 border-t border-gray-200">
            <div class="flex items-center space-x-2">
                <input type="text" id="chatInput" placeholder="Type your message..." class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-plum-500 focus:ring-1 focus:ring-plum-500">
                <button id="sendChatBtn" class="bg-gradient-to-r from-plum-500 to-plum-600 text-white p-2 rounded-lg hover:shadow-md transition-all disabled:opacity-50">
                    <i class="fas fa-paper-plane text-sm"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Chat Widget JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const chatButton = document.getElementById('chatButton');
    const chatWindow = document.getElementById('chatWindow');
    const closeChatBtn = document.getElementById('closeChatBtn');
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const sendChatBtn = document.getElementById('sendChatBtn');
    const unreadBadge = document.getElementById('unreadBadge');
    const quickActionBtns = document.querySelectorAll('.quick-action-btn');

    let isOpen = false;
    let messages = [];
    const currentUserId = <?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null'; ?>;
    const adminId = 0;

    // Get the current page directory for proper API path
    const currentPath = window.location.pathname;
    const apiPath = 'chat_api.php';

    // Toggle chat window
    function toggleChat() {
        isOpen = !isOpen;
        if (isOpen) {
            chatWindow.classList.remove('hidden');
            chatInput.focus();
            markMessagesAsRead();
        } else {
            chatWindow.classList.add('hidden');
        }
    }

    // Close chat
    function closeChat() {
        isOpen = false;
        chatWindow.classList.add('hidden');
    }

    // Load messages from server
    async function loadMessages() {
        try {
            const response = await fetch(`${apiPath}?action=messages&other_user_id=${adminId}`);
            const data = await response.json();
            
            if (data.success) {
                messages = data.messages;
                renderMessages();
                scrollToBottom();
            } else {
                console.error('Failed to load messages:', data.error);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    // Render messages in chat window
    function renderMessages() {
        // Keep the initial welcome message and add new messages
        const initialMessage = chatMessages.querySelector('.flex');
        chatMessages.innerHTML = '';
        
        // Re-add initial message if no messages exist
        if (messages.length === 0) {
            chatMessages.appendChild(initialMessage);
            return;
        }

        messages.forEach(message => {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'flex items-start mb-3';
            
            const isCurrentUser = message.sender_id == currentUserId;
            
            if (isCurrentUser) {
                messageDiv.classList.add('justify-end');
                messageDiv.innerHTML = `
                    <div class="bg-gradient-to-r from-plum-500 to-plum-600 text-white rounded-lg p-3 max-w-xs shadow-sm">
                        <p class="text-sm">${escapeHtml(message.message)}</p>
                        <div class="text-xs opacity-75 mt-1">${formatTime(message.created_at)}</div>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="w-8 h-8 bg-plum-100 rounded-full flex items-center justify-center mr-2 flex-shrink-0">
                        <i class="fas fa-user text-plum-600 text-xs"></i>
                    </div>
                    <div class="bg-white rounded-lg p-3 max-w-xs shadow-sm">
                        <p class="text-sm">${escapeHtml(message.message)}</p>
                        <div class="text-xs text-gray-500 mt-1">${formatTime(message.created_at)}</div>
                    </div>
                `;
            }
            
            chatMessages.appendChild(messageDiv);
        });
    }

    // Send message
    async function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        chatInput.value = '';
        chatInput.disabled = true;
        sendChatBtn.disabled = true;

        try {
            const response = await fetch(`${apiPath}?action=send_message`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    receiver_id: adminId,
                    message: message
                })
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

    // Mark messages as read
    async function markMessagesAsRead() {
        try {
            await fetch(`${apiPath}?action=mark_read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    other_user_id: adminId
                })
            });
            updateUnreadBadge(0);
        } catch (error) {
            console.error('Error marking messages as read:', error);
        }
    }

    // Check for unread messages
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

    // Update unread badge
    function updateUnreadBadge(count) {
        if (count > 0 && !isOpen) {
            unreadBadge.textContent = count;
            unreadBadge.classList.remove('hidden');
        } else {
            unreadBadge.classList.add('hidden');
        }
    }

    // Scroll to bottom of messages
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Format timestamp
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // Event listeners
    chatButton.addEventListener('click', toggleChat);
    closeChatBtn.addEventListener('click', closeChat);
    sendChatBtn.addEventListener('click', sendMessage);
    
    chatInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const message = this.getAttribute('data-message');
            chatInput.value = message;
            sendMessage();
        });
    });

    // Initialize
    loadMessages();
    checkUnreadMessages();
    
    // Poll for new messages every 5 seconds
    setInterval(() => {
        loadMessages();
        if (!isOpen) {
            checkUnreadMessages();
        }
    }, 5000);
});
</script>
<?php endif; ?>

<script>
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Image carousel
    const cards = document.querySelectorAll("#cards li");
    let current = 0;
    const slider = document.getElementById("cards");

    function updateCarousel() {
        const offset = -current * slider.clientWidth;
        slider.style.transform = `translateX(${offset}px)`;
    }

    setInterval(() => {
        current = (current + 1) % cards.length;
        updateCarousel();
    }, 5000);
</script>
<script>
document.getElementById('logoutBtn').addEventListener('click', function(e) {
    e.preventDefault();

    Swal.fire({
        title: 'Are you sure?',
        text: "You’ll be logged out of your account.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#a06c9e',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, log me out',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirect to logout.php
            window.location.href = 'logout.php';
        }
    });
});
</script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
