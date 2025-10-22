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
  <meta charset="UTF-8">
  <title>Manage Artists</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Tailwind (CDN) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css">
  <style>
    /* Minimal fallback if Tailwind fails to load */
    .btn { border:1px solid #4f46e5; padding:8px 14px; border-radius:8px; background:#4f46e5; color:#fff; cursor:pointer; }
    .btn-secondary { border:1px solid #d1d5db; padding:8px 14px; border-radius:8px; background:#fff; color:#111827; }
    .btn-danger { border:1px solid #dc2626; padding:6px 10px; border-radius:6px; background:#dc2626; color:#fff; cursor:pointer; }
  </style>
</head>
<body class="bg-gray-50">

  <!-- Include your existing admin sidebar -->


  <div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Manage Artists</h1>

    <?php if ($flash_success): ?>
      <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?= htmlspecialchars($flash_success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <!-- CREATE FORM -->
    <div class="bg-white rounded shadow mb-8">
      <div class="border-b px-4 py-3 font-medium">Add New Artist</div>
      <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
        <input name="first_name" class="border p-2 rounded" placeholder="First name" required>
        <input name="middle_name" class="border p-2 rounded" placeholder="Middle name">
        <input name="last_name" class="border p-2 rounded" placeholder="Last name" required>
        <input type="date" name="birth_date" class="border p-2 rounded" placeholder="Birth date">
        <select name="sex" class="border p-2 rounded">
          <option value="">Sex</option>
          <option>Male</option>
          <option>Female</option>
          <option>Other</option>
        </select>
        <input name="contact_no" class="border p-2 rounded" placeholder="Contact no">
        <input type="email" name="email" class="border p-2 rounded md:col-span-2" placeholder="Email" required>

        <!-- NEW: password field -->
        <input type="text" name="password" class="border p-2 rounded md:col-span-2" placeholder="Initial password (leave blank to auto-generate)">

        <textarea name="bio" class="border p-2 rounded md:col-span-2" placeholder="Short bio"></textarea>
        <div class="md:col-span-2">
          <button type="submit" name="create" value="1"
                  class="btn bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
            Add Artist
          </button>
        </div>
      </form>
    </div>

    <!-- EDIT PANEL (appears when ?edit_id=...) -->
    <?php if ($edit_artist): ?>
    <div class="bg-white rounded shadow mb-8">
      <div class="border-b px-4 py-3 font-medium">
        Edit Artist: <?= htmlspecialchars(($edit_artist['first_name'] ?? '').' '.($edit_artist['last_name'] ?? '')) ?>
      </div>
      <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
        <input type="hidden" name="user_id" value="<?= (int)$edit_artist['user_id'] ?>">
        <input name="first_name" class="border p-2 rounded" value="<?= htmlspecialchars($edit_artist['first_name']) ?>" placeholder="First name" required>
        <input name="middle_name" class="border p-2 rounded" value="<?= htmlspecialchars($edit_artist['middle_name'] ?? '') ?>" placeholder="Middle name">
        <input name="last_name" class="border p-2 rounded" value="<?= htmlspecialchars($edit_artist['last_name']) ?>" placeholder="Last name" required>
        <input type="date" name="birth_date" class="border p-2 rounded" value="<?= htmlspecialchars($edit_artist['birth_date'] ?? '') ?>" placeholder="Birth date">
        <?php $sexVal = $edit_artist['sex'] ?? ''; ?>
        <select name="sex" class="border p-2 rounded">
          <option value="">Sex</option>
          <option <?= $sexVal==='Male'?'selected':'' ?>>Male</option>
          <option <?= $sexVal==='Female'?'selected':'' ?>>Female</option>
          <option <?= $sexVal==='Other'?'selected':'' ?>>Other</option>
        </select>
        <input name="contact_no" class="border p-2 rounded" value="<?= htmlspecialchars($edit_artist['contact_no'] ?? '') ?>" placeholder="Contact no">
        <input type="email" name="email" class="border p-2 rounded md:col-span-2" value="<?= htmlspecialchars($edit_artist['email']) ?>" placeholder="Email" required>
        <textarea name="bio" class="border p-2 rounded md:col-span-2" placeholder="Short bio"><?= htmlspecialchars($edit_artist['bio'] ?? '') ?></textarea>

        <div class="md:col-span-2 bg-gray-50 border rounded p-3">
          <label class="block text-sm font-medium mb-1">Reset Password (optional)</label>
          <input type="text" name="new_password" class="border p-2 rounded w-full" placeholder="Enter new password to reset (leave blank to keep current)">
        </div>

        <div class="md:col-span-2 flex items-center gap-3">
          <!-- VISIBLE Save button -->
          <button type="submit" name="update" value="1"
                  class="btn bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded">
            Save Changes
          </button>
          <a href="admin_manage_artists.php"
             class="btn-secondary border px-4 py-2 rounded hover:bg-gray-100">
            Cancel
          </a>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <!-- LIST TABLE -->
    <div class="bg-white rounded shadow">
      <div class="border-b px-4 py-3 font-medium">Artist Accounts</div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100">
            <tr>
              <th class="text-left p-3">Name</th>
              <th class="text-left p-3">Email</th>
              <th class="text-left p-3">Contact</th>
              <th class="text-left p-3">Created</th>
              <th class="text-left p-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($artists as $a): ?>
              <?php
                $name = trim(($a['first_name'] ?? '').' '.($a['middle_name'] ?? '').' '.($a['last_name'] ?? ''));
                $name = preg_replace('/\s+/', ' ', $name);
              ?>
              <tr class="border-t">
                <td class="p-3"><?= htmlspecialchars($name) ?></td>
                <td class="p-3"><?= htmlspecialchars($a['email']) ?></td>
                <td class="p-3"><?= htmlspecialchars($a['contact_no'] ?? '') ?></td>
                <td class="p-3"><?= htmlspecialchars($a['created_at']) ?></td>
                <td class="p-3 flex items-center gap-3">
                  <a class="text-indigo-600 hover:underline"
                     href="admin_manage_artists.php?edit_id=<?= (int)$a['user_id'] ?>">Edit</a>
                  <form method="post"
                        onsubmit="return confirm('Delete this artist? This may orphan teams they belong to.');"
                        style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= (int)$a['user_id'] ?>">
                    <button type="submit" class="btn-danger">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$artists): ?>
              <tr><td class="p-3" colspan="5">No artists yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</body>
</html>
