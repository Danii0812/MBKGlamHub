<?php
include 'db.php'; // make sure it defines $conn

$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;

if ($team_id > 0) {
    // Fetch main team info + both artists
    $stmt = $conn->prepare("
        SELECT 
            t.team_id, t.name AS team_name, t.profile_image,
            mu.first_name AS makeup_first, mu.last_name AS makeup_last, mu.bio AS makeup_bio, mu.sex AS makeup_gender,
            hu.first_name AS hair_first, hu.last_name AS hair_last, hu.bio AS hair_bio, hu.sex AS hair_gender
        FROM teams t
        JOIN users mu ON mu.user_id = t.makeup_artist_id
        JOIN users hu ON hu.user_id = t.hairstylist_id
        WHERE t.team_id = ?
    ");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $team_result = $stmt->get_result()->fetch_assoc();

    // Fetch portfolio uploads
    $uploads_stmt = $conn->prepare("SELECT image_path FROM team_uploads WHERE team_id = ? ORDER BY uploaded_at DESC");
    $uploads_stmt->bind_param("i", $team_id);
    $uploads_stmt->execute();
    $uploads_result = $uploads_stmt->get_result();

    $uploads = [];
    while ($row = $uploads_result->fetch_assoc()) {
        $uploads[] = $row['image_path'];
    }

    // Combine all into one response
    echo json_encode([
        'team' => $team_result,
        'uploads' => $uploads
    ]);
} else {
    echo json_encode(['error' => 'Invalid team ID']);
}
?>
