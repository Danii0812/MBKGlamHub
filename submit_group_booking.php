<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "mbk_db");
    if ($conn->connect_error) die("Connection failed");

    function map_value($category, $value) {
        $maps = [
            'hair_style' => ["Curls", "Straight", "Bun", "Braided", "Ponytail", "Others"],
            'makeup_style' => ["Natural", "Glam", "Bold", "Matte", "Dewy", "Themed"],
            'price_range' => ["Low", "Medium", "High"],
            'event_type' => ["Wedding", "Debut", "Photoshoot", "Graduation", "Birthday", "Others"],
            'skin_tone' => ["Fair", "Medium", "Olive", "Dark"],
            'face_shape' => ["Round", "Oval", "Square", "Heart", "Diamond", "Others"],
            'gender_preference' => ["No preference", "Male", "Female"],
            'hair_length' => ["Short", "Medium", "Long"]
        ];
        return array_search($value, $maps[$category]);
    }

    $client_count = intval($_POST['client_count']);
    $_SESSION['client_count'] = $client_count;

    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $booking_address = $_POST['booking_address'];

    $_SESSION['selected_date'] = $booking_date;
    $_SESSION['selected_time'] = $booking_time;

    $user_id = $_SESSION['user_id'] ?? 1;

    $stmt = $conn->prepare("INSERT INTO bookings (user_id, booking_date, booking_time, booking_address, payment_status, is_confirmed) VALUES (?, ?, ?, ?, 'pending', 0)");
    $stmt->bind_param("isss", $user_id, $booking_date, $booking_time, $booking_address);
    $stmt->execute();
    $booking_id = $stmt->insert_id;
    $_SESSION['booking_id'] = $booking_id;

    // Use the first client's preferences for prediction
    $input = [
        "hair_style" => map_value('hair_style', $_POST['hair_style'][0]),
        "makeup_style" => map_value('makeup_style', $_POST['makeup_style'][0]),
        "price_range" => map_value('price_range', $_POST['price_range'][0]),
        "event_type" => map_value('event_type', $_POST['event_type'][0]),
        "skin_tone" => map_value('skin_tone', $_POST['skin_tone'][0]),
        "face_shape" => map_value('face_shape', $_POST['face_shape'][0]),
        "gender_preference" => map_value('gender_preference', $_POST['gender_preference'][0]),
        "hair_length" => map_value('hair_length', $_POST['hair_length'][0])
    ];

    file_put_contents("last_json_input.json", json_encode($input));

    $python = "C:\\Users\\kenny\\AppData\\Local\\Programs\\Python\\Python311\\python.exe";
    $process = proc_open("$python predict_team.py", [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ], $pipes);

    $team_id = 1;
    if (is_resource($process)) {
        fwrite($pipes[0], json_encode($input));
        fclose($pipes[0]);
        $team_output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        $team_id = intval(trim($team_output)) ?: 1;
    }

    // Insert each client individually using arrays
    $stmt = $conn->prepare("INSERT INTO booking_clients (booking_id, client_name, hair_style, makeup_style, price_range, event_type, skin_tone, face_shape, gender_preference, hair_length, team_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    for ($i = 0; $i < $client_count; $i++) {
        $client_name = $_POST['client_name'][$i];
        $hair_style = map_value('hair_style', $_POST['hair_style'][$i]);
        $makeup_style = map_value('makeup_style', $_POST['makeup_style'][$i]);
        $price_range = map_value('price_range', $_POST['price_range'][$i]);
        $event_type = map_value('event_type', $_POST['event_type'][$i]);
        $skin_tone = map_value('skin_tone', $_POST['skin_tone'][$i]);
        $face_shape = map_value('face_shape', $_POST['face_shape'][$i]);
        $gender_pref = map_value('gender_preference', $_POST['gender_preference'][$i]);
        $hair_length = map_value('hair_length', $_POST['hair_length'][$i]);

        $stmt->bind_param(
            "isiiiiiiiii",
            $booking_id,
            $client_name,
            $hair_style,
            $makeup_style,
            $price_range,
            $event_type,
            $skin_tone,
            $face_shape,
            $gender_pref,
            $hair_length,
            $team_id
        );
        $stmt->execute();
    }

    $_SESSION['preferred_event'] = $_POST['event_type'][0];
    $_SESSION['preferred_price'] = $_POST['price_range'][0];
    $_SESSION['selected_team_id'] = $team_id;

    header("Location: show_team_selection.php?team_id=$team_id");
    exit();
}
