<?php
session_start();
// Dummy session variables for demonstration if not logged in
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

$message = '';
$message_type = '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Reviews - Naive Bayes</title>
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
                            500: '#a06c9e',
                            600: '#804f7e',
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
                            500: '#4b2840',
                            600: '#673f68',
                            700: '#4A2D4B',
                            800: '#2E1B2E',
                            900: '#120912',
                        },
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

        <!-- Site Settings Content -->
        <main class="flex-1 p-8 ml-64">

            <?php
            require 'db.php';
        
            // Pending reviews
            $pendingStmt = $pdo->query("
                SELECT r.*, u.first_name, u.last_name
                FROM reviews r
                LEFT JOIN users u ON r.user_id = u.user_id
                WHERE r.is_verified = 0
                ORDER BY r.created_at DESC
            ");
            $pendingReviews = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

            // Verified reviews
            $verifiedStmt = $pdo->query("
                SELECT r.*, u.first_name, u.last_name
                FROM reviews r
                LEFT JOIN users u ON r.user_id = u.user_id
                WHERE r.is_verified = 1
                ORDER BY r.created_at DESC
            ");
            $verifiedReviews = $verifiedStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="mb-6 flex space-x-4">
                <button id="tab-pending" class="px-4 py-2 bg-plum-500 text-white rounded">Pending Reviews</button>
                <button id="tab-verified" class="px-4 py-2 bg-gray-200 text-gray-700 rounded">Verified Reviews</button>
            </div>

            <!-- Pending Reviews -->
            <div id="pending-reviews">
                <?php if (count($pendingReviews) > 0): ?>
                    <div class="bg-white rounded-xl shadow p-6">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-lavender-100 text-gray-700">
                                    <th class="p-3 rounded-tl-lg">Review ID</th>
                                    <th class="p-3">User</th>
                                    <th class="p-3">Booking ID</th>
                                    <th class="p-3">Rating</th>
                                    <th class="p-3">Comment</th>
                                    <th class="p-3">Predicted</th>
                                    <th class="p-3">Mark As</th>
                                    <th class="p-3 rounded-tr-lg">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingReviews as $r): ?>
                                    <tr class="border-b hover:bg-lavender-50 transition">
                                        <td class="p-3"><?= htmlspecialchars($r['review_id']) ?></td>
                                        <td class="p-3"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                        <td class="p-3"><?= htmlspecialchars($r['booking_id']) ?></td>
                                        <td class="p-3 text-yellow-500">
                                            <?php for ($i = 0; $i < $r['rating']; $i++): ?>
                                                <i class="fa fa-star"></i>
                                            <?php endfor; ?>
                                        </td>
                                        <td class="p-3 max-w-sm truncate"><?= htmlspecialchars($r['comment']) ?></td>
                                        <td class="p-3 text-center">
                                            <?php if (!empty($r['sentiment'])): ?>
                                                <span class="px-2 py-1 text-xs rounded-full 
                                                    <?= $r['sentiment'] === 'positive' ? 'bg-green-100 text-green-700' : 
                                                    ($r['sentiment'] === 'negative' ? 'bg-red-100 text-red-700' : 
                                                    'bg-yellow-100 text-yellow-700') ?>">
                                                    <?= ucfirst($r['sentiment']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-sm">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3">
                                            <select class="sentiment-select border border-gray-300 rounded px-2 py-1 text-sm"
                                                data-id="<?= $r['review_id'] ?>">
                                                <option value="positive" <?= $r['sentiment'] === 'positive' ? 'selected' : '' ?>>Positive</option>
                                                <option value="neutral" <?= $r['sentiment'] === 'neutral' ? 'selected' : '' ?>>Neutral</option>
                                                <option value="negative" <?= $r['sentiment'] === 'negative' ? 'selected' : '' ?>>Negative</option>
                                            </select>
                                        </td>
                                        <td class="p-3">
                                            <button 
                                                class="verify-btn bg-plum-500 hover:bg-plum-600 text-white px-3 py-1 rounded text-sm transition"
                                                data-id="<?= $r['review_id'] ?>">
                                                Verify
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center bg-white p-8 rounded-xl shadow text-gray-600">
                        <i class="fas fa-inbox text-4xl text-gray-400 mb-3"></i>
                        <p class="text-lg font-semibold">No pending reviews.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Verified Reviews (hidden by default) -->
            <div id="verified-reviews" class="hidden">
                <?php if (count($verifiedReviews) > 0): ?>
                    <div class="bg-white rounded-xl shadow p-6">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-lavender-100 text-gray-700">
                                    <th class="p-3 rounded-tl-lg">Review ID</th>
                                    <th class="p-3">User</th>
                                    <th class="p-3">Booking ID</th>
                                    <th class="p-3">Rating</th>
                                    <th class="p-3">Comment</th>
                                    <th class="p-3">Sentiment</th>
                                    <th class="p-3 rounded-tr-lg">Created At</th>
                                    <th class="p-3 rounded-tr-lg">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($verifiedReviews as $r): ?>
                                    <tr class="border-b hover:bg-lavender-50 transition">
                                        <td class="p-3"><?= htmlspecialchars($r['review_id']) ?></td>
                                        <td class="p-3"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                        <td class="p-3"><?= htmlspecialchars($r['booking_id']) ?></td>
                                        <td class="p-3 text-yellow-500">
                                            <?php for ($i = 0; $i < $r['rating']; $i++): ?>
                                                <i class="fa fa-star"></i>
                                            <?php endfor; ?>
                                        </td>
                                        <td class="p-3 max-w-sm truncate"><?= htmlspecialchars($r['comment']) ?></td>
                                        <td class="p-3 text-center">
                                            <span class="px-2 py-1 text-xs rounded-full 
                                                <?= $r['sentiment'] === 'positive' ? 'bg-green-100 text-green-700' : 
                                                ($r['sentiment'] === 'negative' ? 'bg-red-100 text-red-700' : 
                                                'bg-yellow-100 text-yellow-700') ?>">
                                                <?= ucfirst($r['sentiment']) ?>
                                            </span>
                                        </td>
                                        <td class="p-3">
    <?php
        $formattedDate = date("F d, Y h:i A", strtotime($r['created_at']));
        echo htmlspecialchars($formattedDate);
    ?>
</td>

<td class="p-3">
    <?php if ($r['sentiment'] === 'neutral'): ?>
        <button 
            class="revert-btn bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded text-sm transition"
            data-id="<?= $r['review_id'] ?>">
            Revert
        </button>
    <?php endif; ?>
</td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center bg-white p-8 rounded-xl shadow text-gray-600">
                        <i class="fas fa-inbox text-4xl text-gray-400 mb-3"></i>
                        <p class="text-lg font-semibold">No verified reviews yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        document.querySelectorAll('.verify-btn').forEach(button => {
            button.addEventListener('click', async () => {
                const id = button.getAttribute('data-id');
                const select = document.querySelector(`.sentiment-select[data-id="${id}"]`);
                const sentiment = select.value || select.getAttribute('data-predicted');

                if (!sentiment) {
                    Swal.fire('Wait!', 'Please select a sentiment before verifying.', 'warning');
                    return;
                }

                const res = await fetch('verify_review.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `review_id=${id}&sentiment=${sentiment}`
                });
                const data = await res.json();

                if (data.success) {
                    Swal.fire('Verified!', 'The review has been added to training data.', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Something went wrong.', 'error');
                }
            });
        });

        document.getElementById('tab-pending').addEventListener('click', () => {
        document.getElementById('pending-reviews').classList.remove('hidden');
        document.getElementById('verified-reviews').classList.add('hidden');
        document.getElementById('tab-pending').classList.add('bg-plum-500', 'text-white');
        document.getElementById('tab-pending').classList.remove('bg-gray-200', 'text-gray-700');
        document.getElementById('tab-verified').classList.add('bg-gray-200', 'text-gray-700');
        document.getElementById('tab-verified').classList.remove('bg-plum-500', 'text-white');
    });

    document.getElementById('tab-verified').addEventListener('click', () => {
        document.getElementById('verified-reviews').classList.remove('hidden');
        document.getElementById('pending-reviews').classList.add('hidden');
        document.getElementById('tab-verified').classList.add('bg-plum-500', 'text-white');
        document.getElementById('tab-verified').classList.remove('bg-gray-200', 'text-gray-700');
        document.getElementById('tab-pending').classList.add('bg-gray-200', 'text-gray-700');
        document.getElementById('tab-pending').classList.remove('bg-plum-500', 'text-white');
    });

    document.querySelectorAll('.revert-btn').forEach(button => {
        button.addEventListener('click', async () => {
        const id = button.getAttribute('data-id');
        const res = await fetch('revert_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `review_id=${id}`
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('Reverted!', 'The review has been moved back to pending.', 'success')
                .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'Something went wrong.', 'error');
        }
    });
});
        </script>

        </main>
    </div>

</body>
</html>
