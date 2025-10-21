<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $team_id = intval($_POST['team_id'] ?? 0);
    $team_name = trim($_POST['team_name'] ?? '');
    $makeup_artist = intval($_POST['makeup_artist'] ?? 0);
    $hairstylist = intval($_POST['hairstylist'] ?? 0);

    if ($team_id > 0 && $team_name && $makeup_artist && $hairstylist) {
        // Update team info
        $stmt = $conn->prepare("UPDATE teams SET name = ?, makeup_artist_id = ?, hairstylist_id = ? WHERE team_id = ?");
        $stmt->bind_param("siii", $team_name, $makeup_artist, $hairstylist, $team_id);
        // --- UPDATE PROFILE IMAGE ---
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
                    $stmtProfile->bind_param("si", $relativePath, $team_id);
                    $stmtProfile->execute();
                    $stmtProfile->close();
                }
            }
        }
        $stmt->execute();
        $stmt->close();

        // Handle uploaded images (if any)
        if (!empty($_FILES['teamImages']['name'][0])) {
            $uploadDir = __DIR__ . '/uploads/teams/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileCount = count($_FILES['teamImages']['name']);
            $fileCount = min($fileCount, 6); // max 6

            for ($i = 0; $i < $fileCount; $i++) {
                $fileName = basename($_FILES['teamImages']['name'][$i]);
                $uniqueName = uniqid('team_', true) . '_' . $fileName;
                $targetPath = $uploadDir . $uniqueName;
                $fileType = mime_content_type($_FILES['teamImages']['tmp_name'][$i]);

                if (strpos($fileType, 'image/') === 0 && move_uploaded_file($_FILES['teamImages']['tmp_name'][$i], $targetPath)) {
                    $relativePath = 'uploads/teams/' . $uniqueName;

                    // Insert into team_uploads
                    $stmtImg = $conn->prepare("INSERT INTO team_uploads (team_id, image_path) VALUES (?, ?)");
                    $stmtImg->bind_param("is", $team_id, $relativePath);
                    $stmtImg->execute();
                    $stmtImg->close();
                }
            }
        }

        header("Location: admin_manage_teams.php?updated=1");
        exit;
    } else {
        echo "Invalid input.";
    }
}
?>
