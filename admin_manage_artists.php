<?php
/**
 * admin_manage_artists.php
 * Admin CRUD for Artist accounts: Create, List, Edit, Delete.
 * - Includes your existing admin_sidebar.php
 * - PRG flow so edit panel closes after update
 * - Create form has password (blank => auto-generate)
 */

session_start();
require_once __DIR__ . '/db.php';

// Require admin
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
  header("Location: login.php"); exit;
}

// Flash helpers
function set_flash($key, $val) { $_SESSION['flash'][$key] = $val; }
function get_flash($key) {
  if (!isset($_SESSION['flash'][$key])) return null;
  $v = $_SESSION['flash'][$key];
  unset($_SESSION['flash'][$key]);
  return $v;
}

$errors = [];
$success = null;

function str_clean($v) { return trim((string)$v); }
function valid_email($e) { return (bool)filter_var($e, FILTER_VALIDATE_EMAIL); }

// ---------------------------
// CREATE
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
  $first  = str_clean($_POST['first_name'] ?? '');
  $middle = str_clean($_POST['middle_name'] ?? '');
  $last   = str_clean($_POST['last_name'] ?? '');
  $birth  = $_POST['birth_date'] ?? null;
  $sex    = str_clean($_POST['sex'] ?? '');
  $contact= str_clean($_POST['contact_no'] ?? '');
  $email  = str_clean($_POST['email'] ?? '');
  $bio    = str_clean($_POST['bio'] ?? '');
  $password = str_clean($_POST['password'] ?? '');

  if ($first === '' || $last === '') $errors[] = "First and last name are required.";
  if (!valid_email($email)) $errors[] = "Valid email required.";

  // Duplicate email check
  if (!$errors) {
    $chk = $conn->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
    $chk->bind_param("s", $email);
    $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) $errors[] = "Email is already in use.";
    $chk->close();
  }

  if (!$errors) {
    // If password blank, generate a temp one and show in flash
    if ($password === '') {
      $password = bin2hex(random_bytes(4)); // 8 chars
      $generated = true;
    } else {
      $generated = false;
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("
      INSERT INTO users (first_name, middle_name, last_name, birth_date, sex, contact_no, email, password, is_verified, role, bio)
      VALUES (?,?,?,?,?,?,?,?,1,'artist',?)
    ");
    if (!$stmt) {
      $errors[] = "Create failed: ".$conn->error;
    } else {
      $stmt->bind_param("sssssssss",
        $first, $middle, $last, $birth, $sex, $contact, $email, $hash, $bio
      );
      if ($stmt->execute()) {
        $msg = "Artist created.";
        if ($generated) $msg .= " Temp password: ".$password;
        set_flash('success', $msg);
        header("Location: admin_manage_artists.php"); // PRG
        exit;
      } else {
        $errors[] = "Create failed: " . htmlspecialchars($stmt->error);
      }
      $stmt->close();
    }
  }
  // Fall through to render with inline errors (no redirect)
}

// ---------------------------
// UPDATE (EDIT)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
  $uid    = (int)($_POST['user_id'] ?? 0);
  $first  = str_clean($_POST['first_name'] ?? '');
  $middle = str_clean($_POST['middle_name'] ?? '');
  $last   = str_clean($_POST['last_name'] ?? '');
  $birth  = $_POST['birth_date'] ?? null;
  $sex    = str_clean($_POST['sex'] ?? '');
  $contact= str_clean($_POST['contact_no'] ?? '');
  $email  = str_clean($_POST['email'] ?? '');
  $bio    = str_clean($_POST['bio'] ?? '');
  $newpass= str_clean($_POST['new_password'] ?? '');

  if ($uid <= 0) $errors[] = "Invalid artist ID.";
  if ($first === '' || $last === '') $errors[] = "First and last name are required.";
  if (!valid_email($email)) $errors[] = "Valid email required.";

  // Duplicate email (excluding this user)
  if (!$errors) {
    $chk = $conn->prepare("SELECT 1 FROM users WHERE email = ? AND user_id <> ? LIMIT 1");
    $chk->bind_param("si", $email, $uid);
    $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) $errors[] = "Email is already in use by another user.";
    $chk->close();
  }

  if (!$errors) {
    if ($newpass !== '') {
      $hash = password_hash($newpass, PASSWORD_BCRYPT);
      $stmt = $conn->prepare("
        UPDATE users
           SET first_name=?, middle_name=?, last_name=?, birth_date=?, sex=?, contact_no=?, email=?, bio=?, password=?
         WHERE user_id=? AND role='artist'
      ");
      if (!$stmt) {
        $errors[] = "Update failed: ".$conn->error;
      } else {
        $stmt->bind_param("sssssssssi", $first,$middle,$last,$birth,$sex,$contact,$email,$bio,$hash,$uid);
        if ($stmt->execute()) {
          set_flash('success', "Artist updated (password reset).");
          header("Location: admin_manage_artists.php"); // PRG: closes edit panel
          exit;
        } else {
          $errors[] = "Update failed: ".htmlspecialchars($stmt->error);
        }
        $stmt->close();
      }
    } else {
      $stmt = $conn->prepare("
        UPDATE users
           SET first_name=?, middle_name=?, last_name=?, birth_date=?, sex=?, contact_no=?, email=?, bio=?
         WHERE user_id=? AND role='artist'
      ");
      if (!$stmt) {
        $errors[] = "Update failed: ".$conn->error;
      } else {
        $stmt->bind_param("ssssssssi", $first,$middle,$last,$birth,$sex,$contact,$email,$bio,$uid);
        if ($stmt->execute()) {
          set_flash('success', "Artist updated.");
          header("Location: admin_manage_artists.php"); // PRG
          exit;
        } else {
          $errors[] = "Update failed: ".htmlspecialchars($stmt->error);
        }
        $stmt->close();
      }
    }
  }
  // Fall through to render with inline errors (no redirect on error)
}

// ---------------------------
// DELETE
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
  $id = (int)$_POST['delete_id'];
  $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'artist'");
  if ($stmt) {
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
      set_flash('success', "Artist deleted.");
      header("Location: admin_manage_artists.php"); // PRG
      exit;
    } else {
      $errors[] = "Delete failed: ".htmlspecialchars($stmt->error);
    }
    $stmt->close();
  } else {
    $errors[] = "Delete failed: ".$conn->error;
  }
}

// ---------------------------
// Load list + optional edit target
// ---------------------------
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
$edit_artist = null;
if ($edit_id) {
  $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=? AND role='artist' LIMIT 1");
  $stmt->bind_param("i", $edit_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $edit_artist = $res ? $res->fetch_assoc() : null;
  $stmt->close();
}

$res = $conn->query("
  SELECT user_id, first_name, middle_name, last_name, email, sex, contact_no, bio, birth_date, created_at
    FROM users
   WHERE role='artist'
ORDER BY created_at DESC
");
$artists = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Pull flash after potential redirects
$flash_success = get_flash('success');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Artists</title>
  <link rel="icon" type="image/png" href="mbk_logo.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
              50: '#F8F5FA', 100: '#F0EBF5', 200: '#E0D6EB', 300: '#D0C1E1', 400: '#C0ACD7',
              500: '#a06c9e', 600: '#804f7e', 700: '#60325e', 800: '#40153e', 900: '#20001e',
            },
            plum: {
              50: '#F5F0F5', 100: '#EBE0EB', 200: '#D7C0D7', 300: '#C3A0C3', 400: '#AF80AF',
              500: '#4b2840', 600: '#673f68', 700: '#4A2D4B', 800: '#2E1B2E', 900: '#120912',
            },
            'card-pink': '#FF6B6B',
            'card-orange': '#FFA07A',
            'card-blue': '#6A82FB',
            'card-light-blue': '#FCFCFC',
            'card-green': '#2ECC71',
            'card-light-green': '#A8E6CF',
            'card-purple': '#9B59B6',
            'card-light-purple': '#D2B4DE',
          },
          boxShadow: {
            soft: '0 8px 24px rgba(0,0,0,0.07)',
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-lavender-50 font-body overflow-x-hidden">
  <div class="flex min-h-screen w-full">

    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col bg-white rounded-tl-3xl shadow-inner overflow-y-auto">
      <?php include 'admin_header.php'; ?>

      <main class="flex-grow bg-white ml-64 pt-6 pb-10 px-8">
        <div class="max-w-7xl mx-auto">

          <!-- Title -->
          <h1 class="text-3xl font-heading font-bold text-plum-700 mb-6 flex items-center gap-3">
            <i class="fas fa-user-pen text-2xl"></i>
            Manage Artists
          </h1>

          <!-- Alerts -->
          <?php if (!empty($flash_success)): ?>
            <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 shadow-sm">
              <?= htmlspecialchars($flash_success) ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($errors)): ?>
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 shadow-sm">
              <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
            </div>
          <?php endif; ?>

          <!-- Create Form -->
          <section class="mb-8 bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="border-b border-gray-200 px-5 py-3">
              <h2 class="text-xl font-heading font-semibold text-plum-700 flex items-center gap-2">
                <i class="fa-solid fa-user-plus"></i>
                Add New Artist
              </h2>
            </div>

            <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4 p-5">
              <input name="first_name" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2" placeholder="First name" required>
              <input name="middle_name" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2" placeholder="Middle name">
              <input name="last_name" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2" placeholder="Last name" required>
              <input type="date" name="birth_date" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2">

              <select name="sex" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2">
                <option value="">Sex</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
              </select>

              <input name="contact_no" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2" placeholder="Contact no">
              <input type="email" name="email" class="md:col-span-2 border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2" placeholder="Email" required>
              <input type="text" name="password" class="md:col-span-2 border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2" placeholder="Initial password (leave blank to auto-generate)">
              <textarea name="bio" class="md:col-span-2 border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2" placeholder="Short bio"></textarea>

              <div class="md:col-span-2">
                <button type="submit" name="create" value="1"
                        class="inline-flex items-center gap-2 bg-plum-500 hover:bg-plum-600 text-white px-5 py-2.5 rounded-lg shadow-soft transition">
                  <i class="fa-solid fa-plus"></i>
                  Add Artist
                </button>
              </div>
            </form>
          </section>

          <!-- Edit Section -->
          <?php if (!empty($edit_artist)): ?>
            <section class="mb-8 bg-white border border-gray-200 rounded-xl shadow-sm">
              <div class="border-b border-gray-200 px-5 py-3">
                <h2 class="text-xl font-heading font-semibold text-plum-700 flex items-center gap-2">
                  <i class="fa-solid fa-user-pen"></i>
                  Edit Artist:
                  <span class="font-bold"><?= htmlspecialchars(($edit_artist['first_name'] ?? '').' '.($edit_artist['last_name'] ?? '')) ?></span>
                </h2>
              </div>

              <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4 p-5">
                <input type="hidden" name="user_id" value="<?= (int)$edit_artist['user_id'] ?>">
                <input name="first_name" value="<?= htmlspecialchars($edit_artist['first_name']) ?>" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2" required>
                <input name="middle_name" value="<?= htmlspecialchars($edit_artist['middle_name'] ?? '') ?>" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2">
                <input name="last_name" value="<?= htmlspecialchars($edit_artist['last_name']) ?>" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2" required>
                <input type="date" name="birth_date" value="<?= htmlspecialchars($edit_artist['birth_date'] ?? '') ?>" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2">

                <?php $sexVal = $edit_artist['sex'] ?? ''; ?>
                <select name="sex" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2">
                  <option value="">Sex</option>
                  <option <?= $sexVal==='Male'?'selected':'' ?>>Male</option>
                  <option <?= $sexVal==='Female'?'selected':'' ?>>Female</option>
                  <option <?= $sexVal==='Other'?'selected':'' ?>>Other</option>
                </select>

                <input name="contact_no" value="<?= htmlspecialchars($edit_artist['contact_no'] ?? '') ?>" class="border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2">
                <input type="email" name="email" value="<?= htmlspecialchars($edit_artist['email']) ?>" class="md:col-span-2 border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2" required>
                <textarea name="bio" class="md:col-span-2 border border-gray-200 focus:border-lavender-400 rounded-lg px-3 py-2"><?= htmlspecialchars($edit_artist['bio'] ?? '') ?></textarea>

                <div class="md:col-span-2 bg-gray-50 border border-gray-200 rounded-lg p-4">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Reset Password (optional)</label>
                  <input type="text" name="new_password" class="border border-gray-200 rounded-lg px-3 py-2 w-full" placeholder="Enter new password to reset (leave blank to keep current)">
                </div>

                <div class="md:col-span-2 flex items-center gap-3">
                  <button type="submit" name="update" value="1"
                          class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg shadow-soft transition">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                  </button>
                  <a href="admin_manage_artists.php" class="inline-flex items-center gap-2 border border-gray-200 hover:bg-gray-50 text-gray-800 px-5 py-2.5 rounded-lg transition">
                    <i class="fa-solid fa-xmark"></i>
                    Cancel
                  </a>
                </div>
              </form>
            </section>
          <?php endif; ?>

          <!-- Artists Table -->
          <section class="bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="border-b border-gray-200 px-5 py-3 flex items-center justify-between">
              <h2 class="text-xl font-heading font-semibold text-plum-700 flex items-center gap-2">
                <i class="fa-solid fa-users"></i>
                Artist Accounts
              </h2>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-lavender-50 text-plum-700 uppercase font-medium">
                  <tr>
                    <th class="px-5 py-3 text-left">Name</th>
                    <th class="px-5 py-3 text-left">Email</th>
                    <th class="px-5 py-3 text-left">Contact</th>
                    <th class="px-5 py-3 text-left">Created</th>
                    <th class="px-5 py-3 text-left">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php if (!empty($artists)): ?>
                    <?php foreach ($artists as $a): ?>
                      <?php
                        $name = trim(($a['first_name'] ?? '').' '.($a['middle_name'] ?? '').' '.($a['last_name'] ?? ''));
                        $name = preg_replace('/\s+/', ' ', $name);
                      ?>
                      <tr class="hover:bg-lavender-50/60">
                        <td class="px-5 py-3"><?= htmlspecialchars($name) ?></td>
                        <td class="px-5 py-3"><?= htmlspecialchars($a['email']) ?></td>
                        <td class="px-5 py-3"><?= htmlspecialchars($a['contact_no'] ?? '') ?></td>
                        <td class="px-5 py-3"><?= htmlspecialchars($a['created_at']) ?></td>
                        <td class="px-5 py-3">
                          <div class="flex items-center gap-3">
                            <a href="admin_manage_artists.php?edit_id=<?= (int)$a['user_id'] ?>" class="text-lavender-600 hover:text-lavender-700 hover:underline">Edit</a>
                            <form method="post" onsubmit="return confirm('Delete this artist? This may orphan teams they belong to.');" class="inline-block">
                              <input type="hidden" name="delete_id" value="<?= (int)$a['user_id'] ?>">
                              <button type="submit" class="inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md transition">
                                <i class="fa-solid fa-trash-can"></i>
                                Delete
                              </button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td class="px-5 py-6 text-center text-gray-500" colspan="5">No artists yet.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>

        </div>
      </main>
    </div>
  </div>
</body>
</html>