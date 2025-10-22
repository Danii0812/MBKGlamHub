<?php
/**
 * submit_group_booking.php (fixed)
 * - Accepts multiple aliases for date/time (no more "Missing Selection Data")
 * - Team is no longer required (predicted in-code)
 * - Optional address
 * - Availability-aware fallback if model fails
 * - PRG-safe redirect to a summary page
 */

session_start();
require_once __DIR__ . '/db.php';

// Require login
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// ---------- Helpers ----------
function s($v) { return trim((string)$v); }
function arr_get($arr, $i, $def=null){ return (is_array($arr) && array_key_exists($i,$arr)) ? $arr[$i] : $def; }

// Accept multiple possible field names from various forms
function get_date_from_request() {
  $candidates = [
    $_POST['booking_date'] ?? null,
    $_POST['date'] ?? null,
    $_POST['selected_date'] ?? null,
    $_POST['event_date'] ?? null,
    $_SESSION['booking_date'] ?? null,
  ];
  foreach ($candidates as $v) { if ($v && s($v) !== '') return s($v); }
  return null;
}
function get_time_from_request() {
  $candidates = [
    $_POST['booking_time'] ?? null,
    $_POST['time'] ?? null,
    $_POST['selected_time'] ?? null,
    $_POST['event_time'] ?? null,
    $_SESSION['booking_time'] ?? null,
  ];
  foreach ($candidates as $v) { if ($v && s($v) !== '') return s($v); }
  return null;
}

function map_value($category, $value) {
  static $maps = [
    'hair_style'        => ["Curls","Straight","Bun","Braided","Ponytail","Others"],
    'makeup_style'      => ["Natural","Glam","Bold","Matte","Dewy","Themed"],
    'price_range'       => ["Low","Medium","High"],
    'event_type'        => ["Wedding","Debut","Photoshoot","Graduation","Birthday","Others"],
    'skin_tone'         => ["Fair","Medium","Olive","Dark"],
    'face_shape'        => ["Round","Oval","Square","Heart","Diamond","Others"],
    'gender_preference' => ["No preference","Male","Female"],
    'hair_length'       => ["Short","Medium","Long"]
  ];
  if (!isset($maps[$category])) return null;
  $idx = array_search($value, $maps[$category], true);
  return ($idx === false) ? null : $idx;
}

function call_python_predict_team(array $input, string $workdir) {
  $cmd = "python3 predict_team.py";
  $descriptorspec = [
    0 => ["pipe","r"], 1 => ["pipe","w"], 2 => ["pipe","w"]
  ];
  $process = proc_open($cmd, $descriptorspec, $pipes, $workdir);
  if (!is_resource($process)) return [null, "Failed to start Python process."];

  fwrite($pipes[0], json_encode($input));
  fclose($pipes[0]);

  $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
  $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
  $exit = proc_close($process);

  $team_id = null;
  if ($exit === 0 && strlen(trim($stdout)) > 0) $team_id = (int)trim($stdout);
  return [$team_id, $stderr];
}

function select_available_team_fallback(mysqli $conn, $booking_date, $booking_time, $gender_pref_idx = 0) {
  // gender_pref_idx: 0 none, 1 male, 2 female
  $wantSex = null;
  if ($gender_pref_idx === 1) $wantSex = 'Male';
  if ($gender_pref_idx === 2) $wantSex = 'Female';

  $sql = "
    SELECT t.team_id
    FROM teams t
    LEFT JOIN booking_clients bc ON bc.team_id = t.team_id
    LEFT JOIN bookings b ON b.booking_id = bc.booking_id
         AND b.booking_date = ?
         AND b.booking_time = ?
    LEFT JOIN users ma ON t.makeup_artist_id = ma.user_id
    LEFT JOIN users hs ON t.hairstylist_id = hs.user_id
    WHERE 1 = 1
  ";

  $params = [$booking_date, $booking_time];
  $types  = "ss";

  if ($wantSex !== null) {
    $sql   .= " AND ma.sex = ? AND hs.sex = ? ";
    $params[] = $wantSex;
    $params[] = $wantSex;
    $types   .= "ss";
  }

  $sql .= "
    GROUP BY t.team_id
    HAVING SUM(CASE WHEN b.booking_id IS NULL THEN 0 ELSE 1 END) = 0
    ORDER BY t.created_at DESC
    LIMIT 1
  ";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return null;
  }
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  return $row ? (int)$row['team_id'] : null;
}

// ---------- Collect inputs (now robust to different form names) ----------
$user_id        = (int)$_SESSION['user_id'];
$booking_date   = get_date_from_request();                  // REQUIRED
$booking_time   = get_time_from_request();                  // REQUIRED
$booking_addr   = s($_POST['booking_address'] ?? ($_POST['address'] ?? '')); // OPTIONAL
$package_id     = isset($_POST['package_id']) ? (int)$_POST['package_id'] : null;

// Client arrays from the preference form
$client_names       = $_POST['client_name']       ?? $_POST['clients']       ?? [];
$hair_style_arr     = $_POST['hair_style']        ?? [];
$makeup_style_arr   = $_POST['makeup_style']      ?? [];
$price_range_arr    = $_POST['price_range']       ?? [];
$event_type_arr     = $_POST['event_type']        ?? [];
// Persist the chosen event type (first client) for package filtering
if (!empty($event_type_arr)) {
    $allowedEvents = ["Wedding","Debut","Photoshoot","Graduation","Birthday","Others"];
    $firstEvent = $event_type_arr[0] ?? null;
    if ($firstEvent && in_array($firstEvent, $allowedEvents, true)) {
        $_SESSION['preferred_event'] = $firstEvent;
    }
}
$skin_tone_arr      = $_POST['skin_tone']         ?? [];
$face_shape_arr     = $_POST['face_shape']        ?? [];
$gender_pref_arr    = $_POST['gender_preference'] ?? $_POST['gender_pref'] ?? [];
$first_idx = 0;
$first_gender_pref_idx = map_value('gender_preference', arr_get($gender_pref_arr, $first_idx));
$hair_length_arr    = $_POST['hair_length']       ?? [];

// Old forms sometimes POST a preselected team. We will ignore it for validation,
// but we’ll keep it if you ever want to honor a manual selection.
$preselected_team = isset($_POST['team_id']) ? (int)$_POST['team_id'] : null;

// ---------- Validation (no "team required" any more) ----------
$errors = [];
if (!$booking_date) $errors[] = "Booking date is required.";
if (!$booking_time) $errors[] = "Booking time is required.";
if (empty($client_names)) $errors[] = "At least one client is required.";

if ($errors) {
  http_response_code(422);
  echo implode("<br>", array_map('htmlspecialchars', $errors));
  exit;
}

// Persist date/time in case later pages need them
$_SESSION['booking_date'] = $booking_date;
$_SESSION['booking_time'] = $booking_time;

// ---------- Transaction ----------
$conn->begin_transaction();
try {
  // Create booking (address optional)
  $stmt = $conn->prepare("
    INSERT INTO bookings (user_id, booking_date, booking_time, booking_address, payment_status, is_confirmed, package_id)
    VALUES (?, ?, ?, ?, 'pending', 0, ?)
  ");
  if (!$stmt) throw new Exception("Prepare failed: ".$conn->error);
  $stmt->bind_param("isssi", $user_id, $booking_date, $booking_time, $booking_addr, $package_id);
  if (!$stmt->execute()) throw new Exception("Execute failed: ".$stmt->error);
  $booking_id = $stmt->insert_id;
  $stmt->close();

  // ---------- Predict team using FIRST client (current behavior) ----------
  $first_idx = 0;
  $first_input = [
    "hair_style"         => map_value('hair_style',        arr_get($hair_style_arr,   $first_idx)),
    "makeup_style"       => map_value('makeup_style',      arr_get($makeup_style_arr, $first_idx)),
    "price_range"        => map_value('price_range',       arr_get($price_range_arr,  $first_idx)),
    "event_type"         => map_value('event_type',        arr_get($event_type_arr,   $first_idx)),
    "skin_tone"          => map_value('skin_tone',         arr_get($skin_tone_arr,    $first_idx)),
    "face_shape"         => map_value('face_shape',        arr_get($face_shape_arr,   $first_idx)),
    "gender_preference"  => map_value('gender_preference', arr_get($gender_pref_arr,  $first_idx)),
    "hair_length"        => map_value('hair_length',       arr_get($hair_length_arr,  $first_idx)),
    "booking_date"       => $booking_date,
    "booking_time"       => $booking_time
  ];

  $team_id = null;

  // If a team was manually picked upstream, you may prioritize it.
  // Otherwise, call the Python model for a recommendation.
  if ($preselected_team) {
    $team_id = (int)$preselected_team;
  } else {
    [$pred_team_id, $py_stderr] = call_python_predict_team($first_input, __DIR__);
    if (!empty($py_stderr)) error_log("[predict_team.py] STDERR: ".$py_stderr);
    if ($pred_team_id) $team_id = (int)$pred_team_id;
  }

  // Fallback to any available team at that slot
  if (!$team_id) {
    $team_id = select_available_team_fallback($conn, $booking_date, $booking_time, (int)$first_gender_pref_idx);
  }
  // Final last resort: latest created team (or 1 if table is empty)
  if (!$team_id) {
    $res = $conn->query("SELECT team_id FROM teams ORDER BY created_at DESC LIMIT 1");
    $row = $res ? $res->fetch_assoc() : null;
    $team_id = $row ? (int)$row['team_id'] : 1;
  }

  // ---------- Insert booking clients (same team for the group) ----------
  $stmtCli = $conn->prepare("
    INSERT INTO booking_clients
      (booking_id, client_name, hair_style, makeup_style, price_range, event_type, skin_tone, face_shape, gender_preference, hair_length, team_id)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)
  ");
  if (!$stmtCli) throw new Exception("Prepare failed: ".$conn->error);

  $cnt = count($client_names);
  for ($i=0; $i<$cnt; $i++) {
    $cname        = s(arr_get($client_names, $i, 'Client '.($i+1)));
    $hair_style   = map_value('hair_style',        arr_get($hair_style_arr,   $i));
    $makeup_style = map_value('makeup_style',      arr_get($makeup_style_arr, $i));
    $price_range  = map_value('price_range',       arr_get($price_range_arr,  $i));
    $event_type   = map_value('event_type',        arr_get($event_type_arr,   $i));
    $skin_tone    = map_value('skin_tone',         arr_get($skin_tone_arr,    $i));
    $face_shape   = map_value('face_shape',        arr_get($face_shape_arr,   $i));
    $gender_pref  = map_value('gender_preference', arr_get($gender_pref_arr,  $i));
    $hair_length  = map_value('hair_length',       arr_get($hair_length_arr,  $i));

    // Bind: booking_id (i), client_name (s), 8 ints (or NULL), team_id (i)
    $stmtCli->bind_param(
      "issiiiiiiii",
      $booking_id, $cname,
      $hair_style, $makeup_style, $price_range, $event_type,
      $skin_tone, $face_shape, $gender_pref, $hair_length,
      $team_id
    );
    if (!$stmtCli->execute()) throw new Exception("Insert client failed: ".$stmtCli->error);
  }
  $stmtCli->close();

  $conn->commit();

    // Make sure show_team_selection.php gets what it expects
    $_SESSION['selected_date'] = $booking_date;   // for show_team_selection.php
    $_SESSION['selected_time'] = $booking_time;   // for show_team_selection.php

    // Redirect to the team selection page with team_id + booking_id
    header("Location: show_team_selection.php?team_id=".$team_id."&booking_id=".$booking_id);
    exit;

} catch (Exception $ex) {
  $conn->rollback();
  error_log("Booking submission failed: ".$ex->getMessage());
  http_response_code(500);
  echo "Sorry, we couldn't save your booking. Please try again.";
  exit;
}
