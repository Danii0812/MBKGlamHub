<?php
session_start();
include 'db.php';

$user_id = $_SESSION['user_id'];

if (!$user_id) {
    die("User not logged in or session missing.");
}

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "<p style='color:red;'>⚠️ No user found for user_id: $user_id</p>";
    exit; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Artist Profile</title>
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
        <!-- Top Header / Navbar -->


        <!-- Profile Content -->
        <main class="flex-1 p-10 bg-white overflow-y-auto">
            <h1 class="text-3xl font-heading font-bold text-plum-700 mb-8 flex items-center gap-3">
                <i class="fas fa-user text-2xl"></i>
                Artist Profile
            </h1>

            <div class="bg-white shadow-md rounded-xl overflow-hidden border border-gray-200 p-6">
                <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Your Profile</h2>
                <div class="flex flex-col items-center text-center">
                    <i class="fas fa-user text-6xl text-plum-500 mb-4 border-2 border-plum-500 rounded-full p-4"></i>
                    <h3 class="text-2xl font-semibold text-gray-800">
                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </h3>
                    <p class="text-gray-600 mb-4"><?= htmlspecialchars($user['email']); ?></p>
                    <p class="text-sm text-gray-700 mb-6 max-w-md">
                        <?= htmlspecialchars($user['bio'] ?? 'No bio yet.'); ?>
                    </p>
                    <button id="editProfileBtn" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2 text-plum-700 border-plum-200 hover:bg-lavender-100 bg-transparent">
                        <i class="fas fa-pencil-alt mr-2"></i>
                        Edit Profile
                    </button>
                </div>
            </div>
        </main>
    </div>



    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative">
            <h2 class="text-xl font-semibold text-plum-700 mb-4">Edit Profile</h2>
            <form action="update_artist_profile.php" method="POST" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                <label class="block text-sm font-medium text-gray-700">First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']); ?>" class="w-full mt-1 border border-gray-300 rounded-md p-2" required>
                </div>
                <div>
                <label class="block text-sm font-medium text-gray-700">Middle Name</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($user['middle_name']); ?>" class="w-full mt-1 border border-gray-300 rounded-md p-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']); ?>" class="w-full mt-1 border border-gray-300 rounded-md p-2" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                <label class="block text-sm font-medium text-gray-700">Birth Date</label>
                <input type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date']); ?>" class="w-full mt-1 border border-gray-300 rounded-md p-2">
                </div>
                <div>
                <label class="block text-sm font-medium text-gray-700">Sex</label>
                <select name="sex" class="w-full mt-1 border border-gray-300 rounded-md p-2">
                    <option value="Male" <?= $user['sex'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?= $user['sex'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?= $user['sex'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                <input type="text" name="contact_no" value="<?= htmlspecialchars($user['contact_no']); ?>" class="w-full mt-1 border border-gray-300 rounded-md p-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" class="w-full mt-1 border border-gray-300 rounded-md p-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Bio</label>
                <textarea name="bio" rows="3" class="w-full mt-1 border border-gray-300 rounded-md p-2"><?= htmlspecialchars($user['bio']); ?></textarea>
            </div>

            <div class="flex justify-end space-x-3 mt-4">
                <button type="button" id="cancelEdit" class="px-4 py-2 rounded-md bg-gray-200 text-gray-800 hover:bg-gray-300">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-md bg-plum-600 text-white hover:bg-plum-700">Save Changes</button>
            </div>
            </form>
        </div>
    </div>


<script>
    const modal = document.getElementById("editProfileModal");
    const openBtn = document.getElementById("editProfileBtn");
    const cancelBtn = document.getElementById("cancelEdit");

    openBtn.addEventListener("click", () => {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    });

    cancelBtn.addEventListener("click", () => {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    });

    window.addEventListener("click", (e) => {
    if (e.target === modal) {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }
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
