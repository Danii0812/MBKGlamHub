<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include 'db.php';

$errors = [];

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addTeam'])) {
    $teamName = trim($_POST['teamName'] ?? '');
    $makeupArtist = intval($_POST['makeupArtist'] ?? 0);
    $hairstylist = intval($_POST['hairstylist'] ?? 0);
    $status = $_POST['teamStatus'] ?? 'Active';
    $errors = [];

    if ($teamName === '') $errors[] = "Team name required.";
    if ($makeupArtist <= 0) $errors[] = "Select a makeup artist.";
    if ($hairstylist <= 0) $errors[] = "Select a hairstylist.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO teams (name, makeup_artist_id, hairstylist_id, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt === false) die("Prepare failed: " . htmlspecialchars($conn->error));

        $stmt->bind_param("sii", $teamName, $makeupArtist, $hairstylist);
        $execResult = $stmt->execute();

        if ($execResult) {
            $newTeamId = $conn->insert_id;
            $stmt->close();
            // --- PROFILE IMAGE UPLOAD ---
            if (!empty($_FILES['teamProfile']['name'])) {
                $profileDir = __DIR__ . '/uploads/team_profiles/';
                if (!is_dir($profileDir)) mkdir($profileDir, 0777, true);

                $tmpName = $_FILES['teamProfile']['tmp_name'];
                $fileType = mime_content_type($tmpName);

                if (strpos($fileType, 'image/') === 0) {
                    $fileName = uniqid('profile_', true) . '_' . basename($_FILES['teamProfile']['name']);
                    $targetPath = $profileDir . $fileName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $relativePath = 'uploads/team_profiles/' . $fileName;

                        $stmtProfile = $conn->prepare("UPDATE teams SET profile_image = ? WHERE team_id = ?");
                        $stmtProfile->bind_param("si", $relativePath, $newTeamId);
                        $stmtProfile->execute();
                        $stmtProfile->close();
                    }
                }
            }

            // --- IMAGE UPLOADS ---
            if (!empty($_FILES['teamImages']['name'][0])) {
                $uploadDir = __DIR__ . '/uploads/teams/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $fileCount = min(count($_FILES['teamImages']['name']), 6);

                for ($i = 0; $i < $fileCount; $i++) {
                    $tmp = $_FILES['teamImages']['tmp_name'][$i];
                    $fileType = mime_content_type($tmp);

                    if (strpos($fileType, 'image/') !== 0) continue;

                    $fileName = uniqid('team_', true) . '_' . basename($_FILES['teamImages']['name'][$i]);
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmp, $targetPath)) {
                        $relativePath = 'uploads/teams/' . $fileName;
                        $stmtImg = $conn->prepare("INSERT INTO team_uploads (team_id, image_path) VALUES (?, ?)");
                        $stmtImg->bind_param("is", $newTeamId, $relativePath);
                        $stmtImg->execute();
                        $stmtImg->close();
                    }
                }
            }

            header("Location: " . $_SERVER['PHP_SELF'] . "?added=1");
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
            $stmt->close();
        }
    }

    $_SESSION['team_form_errors'] = $errors;
}


// Fetch artists
$artists = [];
$artistSql = "SELECT user_id, CONCAT(first_name, ' ', last_name) AS full_name FROM users WHERE role = 'artist' ORDER BY first_name";
if ($res = $conn->query($artistSql)) {
    while ($row = $res->fetch_assoc()) {
        $artists[] = $row;
    }
    $res->free();
} else {
    $_SESSION['team_form_errors'][] = "Failed to load artists: " . $conn->error;
}

$success = isset($_GET['added']);
$errors = $_SESSION['team_form_errors'] ?? [];
unset($_SESSION['team_form_errors']);

$greetingName = $_SESSION['user_name'] ?? 'Admin';

// Fetch teams with artist names
$teams = [];
$teamSql = "
    SELECT 
        t.team_id, 
        t.name AS team_name,
        ma.user_id AS makeup_artist_id,
        CONCAT(ma.first_name, ' ', ma.last_name) AS makeup_artist_name,
        ha.user_id AS hairstylist_id,
        CONCAT(ha.first_name, ' ', ha.last_name) AS hairstylist_name
    FROM teams t
    LEFT JOIN users ma ON t.makeup_artist_id = ma.user_id
    LEFT JOIN users ha ON t.hairstylist_id = ha.user_id
    ORDER BY t.created_at DESC
";
if ($res = $conn->query($teamSql)) {
    while ($row = $res->fetch_assoc()) {
        $teams[] = $row;
    }
    $res->free();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Teams - Admin</title>
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

        <!-- Manage Teams Content -->
        <main class="flex-1 p-8 ml-64">

            <h1 class="text-3xl font-heading font-bold text-plum-700 mb-8 flex items-center gap-3">
                <i class="fas fa-users text-2xl"></i>
                Manage Teams
            </h1>

            <!-- Add New Team Section -->
            <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6 mb-10">
                <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Add New Team</h2>
                <form id="addTeamForm" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <input type="hidden" name="addTeam" value="1">

                    <div class="md:col-span-2">
                        <label for="teamName" class="block text-sm font-medium text-gray-700 mb-1">Team Name</label>
                        <input type="text" id="teamName" name="teamName" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>

                    <div class="md:col-span-2">
                        <label for="teamProfile" class="block text-sm font-medium text-gray-700 mb-1">
                            Team Profile Image
                        </label>
                        <input type="file" id="teamProfile" name="teamProfile" accept="image/*"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Upload a profile photo for this team.</p>
                    </div>

                    <div>
                        <label for="makeupArtist" class="block text-sm font-medium text-gray-700 mb-1">Makeup Artist</label>
                        <select id="makeupArtist" name="makeupArtist" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Select Makeup Artist</option>
                            <?php foreach ($artists as $a): ?>
                                <option value="<?= htmlspecialchars($a['user_id']) ?>"><?= htmlspecialchars($a['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="hairstylist" class="block text-sm font-medium text-gray-700 mb-1">Hairstylist</label>
                        <select id="hairstylist" name="hairstylist" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">Select Hairstylist</option>
                            <?php foreach ($artists as $a): ?>
                                <option value="<?= htmlspecialchars($a['user_id']) ?>"><?= htmlspecialchars($a['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="teamImages" class="block text-sm font-medium text-gray-700 mb-1">
                            Upload Team Images (Max 6)
                        </label>
                        <input type="file" id="teamImages" name="teamImages[]" accept="image/*" multiple
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">You can upload up to 6 images.</p>
                    </div>

                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" id="addTeamBtn" class="bg-plum-500 hover:bg-plum-600 text-white font-semibold px-6 py-3 rounded-lg shadow-md">
                            <i class="fas fa-plus mr-2"></i><span id="addTeamBtnText">Add Team</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Existing Teams Section -->
            <div class="bg-white shadow-md rounded-xl border border-gray-200 p-6">
                <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Existing Teams</h2>
                <div id="teamsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Teams will be rendered here by JavaScript -->
                    <?php if (!empty($teams)): ?>
                        <?php foreach ($teams as $team): ?>
                            <div class="border border-gray-200 rounded-lg p-4 shadow-sm bg-white">
                            <?php
                                $profileQuery = $conn->prepare("SELECT profile_image FROM teams WHERE team_id = ?");
                                $profileQuery->bind_param("i", $team['team_id']);
                                $profileQuery->execute();
                                $profileResult = $profileQuery->get_result()->fetch_assoc();
                                $profilePath = $profileResult['profile_image'] ?? null;
                                $profileQuery->close();
                            ?>
                            <?php if ($profilePath): ?>
                                <img src="<?= htmlspecialchars($profilePath) ?>" 
                                    alt="Team Profile" 
                                    class="w-24 h-24 object-cover rounded-full mx-auto mb-3 border-2 border-plum-300">
                            <?php else: ?>
                                <div class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center mx-auto mb-3 text-gray-500">
                                    <i class="fa fa-user fa-2x"></i>
                                </div>
                            <?php endif; ?>

                            <h3 class="text-lg font-semibold text-plum-700 text-center mb-2"><?= htmlspecialchars($team['team_name']) ?></h3>
                            <h3 class="text-lg font-semibold text-plum-700 mb-2"><?= htmlspecialchars($team['team_name']) ?></h3>
                            <div class="flex flex-wrap gap-2 mb-3">
                                <?php
                                $imgQuery = $conn->prepare("SELECT image_path FROM team_uploads WHERE team_id = ?");
                                $imgQuery->bind_param("i", $team['team_id']);
                                $imgQuery->execute();
                                $imgResult = $imgQuery->get_result();
                                if ($imgResult->num_rows > 0) {
                                    while ($img = $imgResult->fetch_assoc()) {
                                        echo '<img src="' . htmlspecialchars($img['image_path']) . '" class="w-20 h-20 object-cover rounded-lg border" alt="Team Image">';
                                    }
                                } else {
                                    echo '<p class="text-sm text-gray-500">No images uploaded.</p>';
                                }
                                $imgQuery->close();
                                ?>
                            </div>

                            <p class="text-sm text-gray-600 mb-1">
                                <strong>Makeup Artist:</strong> <?= htmlspecialchars($team['makeup_artist_name'] ?? 'N/A') ?>
                            </p>
                            <p class="text-sm text-gray-600 mb-3">
                                <strong>Hairstylist:</strong> <?= htmlspecialchars($team['hairstylist_name'] ?? 'N/A') ?>
                            </p>
                            <button 
                                class="editTeamBtn bg-lavender-500 hover:bg-lavender-600 text-white text-sm font-medium px-4 py-2 rounded-lg"
                                data-team-id="<?= $team['team_id'] ?>"
                                data-team-name="<?= htmlspecialchars($team['team_name']) ?>"
                                data-makeup-id="<?= $team['makeup_artist_id'] ?>"
                                data-hair-id="<?= $team['hairstylist_id'] ?>"
                            >
                                <i class="fa fa-pen mr-1"></i> Edit
                            </button>
                        </div>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <p id="noTeamsMessage" class="text-gray-500 text-center md:col-span-full">No teams added yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Team Modal -->
<div id="editTeamModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 hidden z-50">
  <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">
    <button id="closeModal" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700">
      <i class="fa fa-times"></i>
    </button>
    <h2 class="text-xl font-heading font-bold text-plum-700 mb-4">Edit Team</h2>
    <form id="editTeamForm" action="update_team.php" method="POST" class="space-y-4" enctype="multipart/form-data">
      <input type="hidden" name="team_id" id="editTeamId">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Team Name</label>
        <input type="text" name="team_name" id="editTeamName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
      </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Profile Image</label>
        <input type="file" name="teamProfile" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
    </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Makeup Artist</label>
        <select name="makeup_artist" id="editMakeupArtist" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
          <option value="">Select Makeup Artist</option>
          <?php foreach ($artists as $a): ?>
            <option value="<?= $a['user_id'] ?>"><?= htmlspecialchars($a['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Hairstylist</label>
        <select name="hairstylist" id="editHairstylist" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
          <option value="">Select Hairstylist</option>
          <?php foreach ($artists as $a): ?>
            <option value="<?= $a['user_id'] ?>"><?= htmlspecialchars($a['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

    <input type="file" name="teamImages[]" multiple accept="image/*">

      <div class="flex justify-end mt-6">
        <button type="submit" class="bg-plum-500 hover:bg-plum-600 text-white px-5 py-2 rounded-lg font-semibold">
          Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const form = document.getElementById("addTeamForm");
  const addTeamBtn = document.getElementById("addTeamBtn");
  const addTeamBtnText = document.getElementById("addTeamBtnText");

  if (!form || !addTeamBtn || !addTeamBtnText) return;

  form.addEventListener("submit", function(e) {
    addTeamBtn.disabled = true;
    addTeamBtn.classList.add('opacity-70', 'cursor-not-allowed');

    addTeamBtnText.textContent = "Adding...";
    const icon = addTeamBtn.querySelector("i");
    if (icon) {
      icon.classList.remove('fa-plus');
      icon.classList.add('fa-spinner', 'fa-spin');
    }

  });

  window.addEventListener("pageshow", function(event) {
    if (event.persisted) {
      addTeamBtn.disabled = false;
      addTeamBtn.classList.remove('opacity-70', 'cursor-not-allowed');
      addTeamBtnText.textContent = "Add Team";
      const icon = addTeamBtn.querySelector("i");
      if (icon) {
        icon.classList.remove('fa-spinner', 'fa-spin');
        icon.classList.add('fa-plus');
      }
    }
  });
});

document.addEventListener("DOMContentLoaded", function() {
    // Edit Modal Controls
    const modal = document.getElementById("editTeamModal");
    const closeModal = document.getElementById("closeModal");
    const editButtons = document.querySelectorAll(".editTeamBtn");

    const editTeamId = document.getElementById("editTeamId");
    const editTeamName = document.getElementById("editTeamName");
    const editMakeupArtist = document.getElementById("editMakeupArtist");
    const editHairstylist = document.getElementById("editHairstylist");

    editButtons.forEach(btn => {
        btn.addEventListener("click", () => {
        modal.classList.remove("hidden");
        editTeamId.value = btn.dataset.teamId;
        editTeamName.value = btn.dataset.teamName;
        editMakeupArtist.value = btn.dataset.makeupId;
        editHairstylist.value = btn.dataset.hairId;
        });
    });

    closeModal.addEventListener("click", () => modal.classList.add("hidden"));
    window.addEventListener("click", (e) => {
        if (e.target === modal) modal.classList.add("hidden");
    });
});
</script>
</body>
</html>