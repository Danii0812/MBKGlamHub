<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

function is_image_tmp($tmp) {
    if (!$tmp || !is_uploaded_file($tmp)) return false;
    $t = @mime_content_type($tmp);
    return $t && strpos($t, 'image/') === 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // NOTE: If your main table is actually "teams" (plural), switch table names below accordingly.
    $team_id       = intval($_POST['team_id'] ?? 0);
    $team_name     = trim($_POST['team_name'] ?? '');
    $makeup_artist = intval($_POST['makeup_artist'] ?? 0);
    $hairstylist   = intval($_POST['hairstylist'] ?? 0);

    if (!($team_id > 0 && $team_name !== '' && $makeup_artist > 0 && $hairstylist > 0)) {
        die('Invalid input.');
    }

    /* --------------------------------------------------------
       1) Remove selected existing images (DO THIS BEFORE REDIRECT)
    --------------------------------------------------------- */
    if (!empty($_POST['remove_images']) && is_array($_POST['remove_images'])) {
        $ids = array_map('intval', $_POST['remove_images']);
        $ids = array_values(array_filter($ids, fn($v) => $v > 0));

        if (!empty($ids)) {
            // Build placeholders for IN (...)
            $in = implode(',', array_fill(0, count($ids), '?'));

            // 1a) Fetch paths so we can unlink files
            $sql = "SELECT upload_id, image_path FROM team_uploads
                    WHERE team_id = ? AND upload_id IN ($in)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) die('Prepare failed (select to delete): ' . htmlspecialchars($conn->error));

            $types = 'i' . str_repeat('i', count($ids)); // team_id + ids
            $bind  = array_merge([$types, $team_id], $ids);
            $refs  = [];
            foreach ($bind as $k => $v) $refs[$k] = &$bind[$k];
            call_user_func_array([$stmt, 'bind_param'], $refs);

            $stmt->execute();
            $res = $stmt->get_result();
            $toDelete = [];
            while ($row = $res->fetch_assoc()) $toDelete[] = $row;
            $stmt->close();

            // 1b) Remove files from disk
            foreach ($toDelete as $row) {
                $abs = __DIR__ . '/' . $row['image_path'];
                if (is_file($abs)) @unlink($abs);
            }

            // 1c) Delete rows
            $sql = "DELETE FROM team_uploads
                    WHERE team_id = ? AND upload_id IN ($in)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) die('Prepare failed (delete rows): ' . htmlspecialchars($conn->error));

            $types = 'i' . str_repeat('i', count($ids));
            $bind  = array_merge([$types, $team_id], $ids);
            $refs  = [];
            foreach ($bind as $k => $v) $refs[$k] = &$bind[$k];
            call_user_func_array([$stmt, 'bind_param'], $refs);

            $stmt->execute();
            $stmt->close();
        }
    }

    /* --------------------------------------------------------
       2) Update team info
       Change "team" to "teams" if your table is plural.
    --------------------------------------------------------- */
    $stmt = $conn->prepare("UPDATE teams SET name = ?, makeup_artist_id = ?, hairstylist_id = ? WHERE team_id = ?");
    // If your table is plural, use:
    // $stmt = $conn->prepare("UPDATE teams SET name = ?, makeup_artist_id = ?, hairstylist_id = ? WHERE team_id = ?");
    if ($stmt === false) die('Prepare failed (update team): ' . htmlspecialchars($conn->error));
    $stmt->bind_param("siii", $team_name, $makeup_artist, $hairstylist, $team_id);
    $stmt->execute();
    $stmt->close();

    /* --------------------------------------------------------
       3) Update profile image (optional)
       Change "team" to "teams" if your table is plural.
    --------------------------------------------------------- */
    if (!empty($_FILES['teamProfile']['name']) && is_image_tmp($_FILES['teamProfile']['tmp_name'])) {
        $dir = __DIR__ . '/uploads/team_profiles/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $fileName = uniqid('profile_', true) . '_' . basename($_FILES['teamProfile']['name']);
        $target   = $dir . $fileName;

        if (move_uploaded_file($_FILES['teamProfile']['tmp_name'], $target)) {
            $relative = 'uploads/team_profiles/' . $fileName;

            $stmtProfile = $conn->prepare("UPDATE teams SET profile_image = ? WHERE team_id = ?");
            // If plural: $stmtProfile = $conn->prepare("UPDATE teams SET profile_image = ? WHERE team_id = ?");
            if ($stmtProfile === false) die('Prepare failed (update profile): ' . htmlspecialchars($conn->error));
            $stmtProfile->bind_param("si", $relative, $team_id);
            $stmtProfile->execute();
            $stmtProfile->close();
        }
    }

    /* --------------------------------------------------------
       4) Handle new gallery uploads (optional)
    --------------------------------------------------------- */
    if (!empty($_FILES['teamImages']['name'][0])) {
        $dir = __DIR__ . '/uploads/teams/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $count = count($_FILES['teamImages']['name']);
        $count = min($count, 6); // optional cap

        for ($i = 0; $i < $count; $i++) {
            $tmp = $_FILES['teamImages']['tmp_name'][$i];
            if (!is_image_tmp($tmp)) continue;

            $fileName = uniqid('team_', true) . '_' . basename($_FILES['teamImages']['name'][$i]);
            $target   = $dir . $fileName;

            if (move_uploaded_file($tmp, $target)) {
                $relative = 'uploads/teams/' . $fileName;
                $ins = $conn->prepare("INSERT INTO team_uploads (team_id, image_path) VALUES (?, ?)");
                if ($ins === false) die('Prepare failed (insert upload): ' . htmlspecialchars($conn->error));
                $ins->bind_param("is", $team_id, $relative);
                $ins->execute();
                $ins->close();
            }
        }
    }

    /* --------------------------------------------------------
       5) Redirect AFTER all operations
    --------------------------------------------------------- */
    header("Location: admin_manage_teams.php?updated=1");
    exit;
}

// No direct GET access expected
header("Location: admin_manage_teams.php");
exit;
?>