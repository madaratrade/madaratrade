<?php

session_start();

var_dump("FILE RUNNING");
var_dump($_POST);
var_dump($_SESSION);

$user_id = $_SESSION['user_id'];
$bio = $_POST['bio'] ?? "";


require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("not logged in");
}

$user_id = $_SESSION['user_id'];
$bio = $_POST['bio'] ?? "";

# Check bio is exist
$check = $conn->prepare("SELECT id FROM users_info WHERE user_id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$result = $check->get_result();


if ($result->num_rows === 0) {
    
    # Insert if bio not exist
    $stmt = $conn->prepare("INSERT INTO users_info (user_id, bio) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $bio);
    $stmt->execute();

    
} else {

    $stmt = $conn->prepare("UPDATE users_info SET bio = ? WHERE user_id = ?");
    $stmt->bind_param("si", $bio, $user_id);
    $stmt->execute();

}

exit;

?>
