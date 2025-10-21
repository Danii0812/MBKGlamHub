<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

$first_name = $_POST['first_name'];
$middle_name = $_POST['middle_name'];
$last_name = $_POST['last_name'];
$birth_date = $_POST['birth_date'];
$sex = $_POST['sex'];
$contact_no = $_POST['contact_no'];
$email = $_POST['email'];
$bio = $_POST['bio'];

$query = "UPDATE users 
          SET first_name=?, middle_name=?, last_name=?, birth_date=?, sex=?, contact_no=?, email=?, bio=? 
          WHERE user_id=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssssssssi", $first_name, $middle_name, $last_name, $birth_date, $sex, $contact_no, $email, $bio, $user_id);

if ($stmt->execute()) {
  $_SESSION['success'] = "Profile updated successfully!";
} else {
  $_SESSION['error'] = "Error updating profile: " . $stmt->error;
}

header("Location: artist_settings.php");
exit;
?>
