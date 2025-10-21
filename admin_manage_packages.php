<?php
session_start();

include 'db.php';
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Dummy user ID
    $_SESSION['role'] = 'admin'; // Dummy role
    $_SESSION['user_name'] = 'Admin User'; // Dummy user name
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$greetingName = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Packages - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS with custom font and color config -->
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
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-lavender-50 font-body h-screen overflow-hidden flex">
    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col bg-white rounded-tl-3xl shadow-inner overflow-y-auto">
        <!-- Top Header / Navbar -->
        <?php include 'admin_header.php'; ?>

        <!-- Manage Packages Content -->
        <main class="flex-1 p-8 ml-64">

            <h1 class="text-3xl font-heading font-bold text-plum-700 mb-8 flex items-center gap-3">
                <i class="fas fa-box-open text-2xl"></i>
                Manage Packages
            </h1>

            <!-- Add New Package Section -->
            <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6 mb-10">
                <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Add New Package</h2>
                <form id="addPackageForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Package Name -->
                    <div>
                        <label for="packageName" class="block text-sm font-medium text-gray-700 mb-1">Package Name</label>
                        <input type="text" id="packageName" name="packageName" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lavender-300">
                    </div>
                    <!-- Price -->
                    <div>
                        <label for="packagePrice" class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                        <input type="number" id="packagePrice" name="packagePrice" step="0.01" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lavender-300">
                    </div>
                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label for="packageDescription" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="packageDescription" name="packageDescription" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lavender-300"></textarea>
                    </div>
                    <div>
                        <label for="eventType" class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                        <select id="eventType" name="eventType" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lavender-300 bg-white">
                            <option value="Birthday">Birthday</option>
                            <option value="Graduation">Graduation</option>
                            <option value="Debut">Debut</option>
                            <option value="Wedding">Wedding</option>
                            <option value="Photoshoot">Photoshoot</option>
                            <option value="Others">Others</option>
                        </select>
                        </div>

                        <!-- Price Range -->
                        <div>
                        <label for="priceRange" class="block text-sm font-medium text-gray-700 mb-1">Price Range</label>
                        <select id="priceRange" name="priceRange" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lavender-300 bg-white">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                        </select>
                        </div>
                    <!-- Features -->
                    <div class="md:col-span-2">
                        <label for="packageFeatures" class="block text-sm font-medium text-gray-700 mb-1">Features (comma-separated)</label>
                        <textarea id="packageFeatures" name="packageFeatures" rows="2" placeholder="e.g., Full Face Makeup, Hair Styling, Lash Application" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lavender-300"></textarea>
                        <p class="text-xs text-gray-500 mt-1">List features separated by commas.</p>
                    </div>
                    <!-- Status -->
                    <div class="md:col-span-2">
                        <label for="packageStatus" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="packageStatus" name="packageStatus" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lavender-300 bg-white">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" class="bg-plum-500 hover:bg-plum-600 text-white font-semibold px-6 py-3 rounded-lg transition-all shadow-md">
                            <i class="fas fa-plus mr-2"></i>Add Package
                        </button>
                    </div>
                    <div id="formMessage" class="hidden mt-4 p-3 rounded-lg text-sm font-medium"></div>
                </form>
            </div>

            <!-- Existing Packages Section -->
            <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6">
                <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Existing Packages</h2>
                <div id="packagesList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Packages will be rendered here by JavaScript -->
                    <p id="noPackagesMessage" class="text-gray-500 text-center md:col-span-full">No packages added yet.</p>
                </div>
            </div>

        </main>

        <div id="editModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
                <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6 relative">
                    <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Edit Package</h2>

                    <form id="editPackageForm" class="space-y-4">
                    <input type="hidden" id="editPackageId">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Package Name</label>
                        <input type="text" id="editName" class="w-full px-4 py-2 border rounded-lg">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="editDescription" rows="3" class="w-full px-4 py-2 border rounded-lg"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                        <label class="block text-sm font-medium text-gray-700">Event Type</label>
                        <select id="editEventType" class="w-full px-4 py-2 border rounded-lg">
                            <option>Birthday</option>
                            <option>Graduation</option>
                            <option>Debut</option>
                            <option>Wedding</option>
                            <option>Photoshoot</option>
                            <option>Others</option>
                        </select>
                        </div>
                        <div>
                        <label class="block text-sm font-medium text-gray-700">Price Range</label>
                        <select id="editPriceRange" class="w-full px-4 py-2 border rounded-lg">
                            <option>Low</option>
                            <option>Medium</option>
                            <option>High</option>
                        </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price (₱)</label>
                        <input type="number" id="editPrice" step="0.01" class="w-full px-4 py-2 border rounded-lg">
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" id="cancelEdit" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-plum-500 hover:bg-plum-600 text-white rounded-lg">Save Changes</button>
                    </div>
                    </form>
                </div>
            </div>
    </div>

    <script>
        // Dummy data for packages (will be populated by the form)
        let packages = [];
        let nextPackageId = 1; // Simple ID counter

        // Fetch packages from database
        async function fetchPackages() {
            try {
                const response = await fetch('get_packages.php');
                const data = await response.json();

                if (data.success) {
                    packages = data.data;
                    renderPackages();
                } else {
                    console.error(data.message);
                }
            } catch (error) {
                console.error("Error fetching packages:", error);
            }
        }

        // Render packages (unchanged except remove toggle logic)
        function renderPackages() {
            const packagesListDiv = document.getElementById('packagesList');
            packagesListDiv.innerHTML = '';

            if (packages.length === 0) {
                packagesListDiv.innerHTML = '<p id="noPackagesMessage" class="text-gray-500 text-center md:col-span-full">No packages found.</p>';
                return;
            }

            packages.forEach(pkg => {
                const packageCard = document.createElement('div');
                packageCard.className = 'bg-white p-6 rounded-xl shadow-sm border border-gray-200 flex flex-col hover:shadow-md transition';
                packageCard.innerHTML = `
                    <h3 class="text-xl font-heading font-bold text-plum-700 mb-3">${pkg.name}</h3>
                    <p class="text-sm text-gray-500 mb-2">${pkg.event_type} • ${pkg.price_range}</p>
                    <p class="text-2xl font-bold text-gray-900 mb-3">₱${pkg.price.toLocaleString()}</p>
                    <p class="text-gray-700 text-sm mb-4 flex-1">${pkg.description}</p>
                    <div class="flex justify-end">
                        <button class="edit-btn bg-gray-100 hover:bg-plum-600 text-gray-700 hover:text-white font-semibold px-4 py-2 rounded-md transition" data-id="${pkg.id}">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </button>

                    </div>
                `;

                packagesListDiv.appendChild(packageCard);
            });


            
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const id = e.target.closest('button').getAttribute('data-id');
                    const pkg = packages.find(p => p.id == id);
                    openEditModal(pkg);
                });
            });
        }

        // On load
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = window.location.pathname.split('/').pop().split('.')[0];
            document.querySelectorAll('.sidebar-link').forEach(link => {
                const page = link.getAttribute('data-page');
                if (page === currentPage) {
                    link.classList.add('bg-lavender-100', 'text-plum-700', 'shadow-sm');
                    link.querySelector('i').classList.add('text-plum-700');
                    link.classList.remove('text-gray-700');
                }
            });

            fetchPackages(); // ✅ Load from DB on page load
        });


        // Handle Add Package Form Submission
        document.getElementById('addPackageForm').addEventListener('submit', async function(event) {
            event.preventDefault();

            const formMessage = document.getElementById('formMessage');
            formMessage.classList.add('hidden');

            const packageName = document.getElementById('packageName').value;
            const packagePrice = parseFloat(document.getElementById('packagePrice').value);
            const packageDescription = document.getElementById('packageDescription').value;
            const eventType = document.getElementById('eventType').value;
            const priceRange = document.getElementById('priceRange').value;
            const packageStatus = document.getElementById('packageStatus').value;

            const payload = {
                name: packageName,
                description: packageDescription,
                event_type: eventType,
                price_range: priceRange,
                price: packagePrice,
                status: packageStatus
            };

            try {
                const response = await fetch('add_package.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    showFormMessage('✅ Package added successfully!', 'success');
                    this.reset();
                    fetchPackages(); // reload from DB
                } else {
                    showFormMessage('⚠️ ' + data.message, 'error');
                }
            } catch (error) {
                console.error("Error adding package:", error);
                showFormMessage('❌ An error occurred while adding the package.', 'error');
            }
        });

        // Function to show temporary form message
        function showFormMessage(message, type) {
            const msg = document.getElementById('formMessage');
            msg.textContent = message;
            msg.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');

            if (type === 'success') {
                msg.classList.add('bg-green-100', 'text-green-800', 'border', 'border-green-300');
            } else {
                msg.classList.add('bg-red-100', 'text-red-800', 'border', 'border-red-300');
            }

            // Fade out after 3 seconds
            setTimeout(() => {
                msg.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                setTimeout(() => {
                    msg.classList.add('hidden');
                    msg.classList.remove('opacity-0');
                }, 500);
            }, 3000);
        }

        // Sidebar active link logic
        document.addEventListener('DOMContentLoaded', () => {
            const currentPage = window.location.pathname.split('/').pop().split('.')[0];
            document.querySelectorAll('.sidebar-link').forEach(link => {
                const page = link.getAttribute('data-page');
                if (page === currentPage) {
                    link.classList.add('bg-lavender-100', 'text-plum-700', 'shadow-sm');
                    link.querySelector('i').classList.add('text-plum-700');
                    link.classList.remove('text-gray-700');
                } else {
                    link.classList.add('text-gray-700', 'hover:bg-lavender-100', 'hover:text-plum-700');
                    link.querySelector('i').classList.remove('text-plum-700');
                }
            });

            renderPackages(); // Initial render of packages (will show "No packages added yet")
        });

        function openEditModal(pkg) {
            document.getElementById('editModal').classList.remove('hidden');

            document.getElementById('editPackageId').value = pkg.id;
            document.getElementById('editName').value = pkg.name;
            document.getElementById('editEventType').value = pkg.event_type;
            document.getElementById('editPriceRange').value = pkg.price_range;
            document.getElementById('editPrice').value = pkg.price;
            document.getElementById('editDescription').value = pkg.description;
        }


        document.getElementById('cancelEdit').addEventListener('click', () => {
            document.getElementById('editModal').classList.add('hidden');
        });

        document.getElementById('editPackageForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const payload = {
                id: document.getElementById('editPackageId').value,
                name: document.getElementById('editName').value,
                description: document.getElementById('editDescription').value,
                event_type: document.getElementById('editEventType').value,
                price_range: document.getElementById('editPriceRange').value,
                price: parseFloat(document.getElementById('editPrice').value)
            };

            try {
                const response = await fetch('update_package.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    showFormMessage('✅ Package updated successfully!', 'success');
                    document.getElementById('editModal').classList.add('hidden');
                    fetchPackages();
                } else {
                    showFormMessage('⚠️ ' + data.message, 'error');
                }
            } catch (err) {
                console.error('Error updating package:', err);
                showFormMessage('❌ Failed to update package.', 'error');
            }
        });

    </script>
</body>
</html>