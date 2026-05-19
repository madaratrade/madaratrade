<?php
session_start();

include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
	    echo json_encode([
		            "status" => "error",
			            "message" => "User not logged in."
				        ]);
	        exit;
}

if (!isset($_FILES['profile_picture'])) {
	    echo json_encode([
		            "status" => "error",
			            "message" => "No file uploaded."
				        ]);
	        exit;
}

$user_id = $_SESSION['user_id'];

if ($conn->connect_error) {
	    echo json_encode([
		            "status" => "error",
			            "message" => "Database connection failed."
				        ]);
	        exit;
}

$file = $_FILES['profile_picture'];

if ($file['error'] !== UPLOAD_ERR_OK) {
	    echo json_encode([
		            "status" => "error",
			            "message" => "Upload error."
				        ]);
	        exit;
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_types)) {
	    echo json_encode([
		            "status" => "error",
			            "message" => "Only JPG, PNG, GIF, WEBP are allowed."
				        ]);
	        exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = "profile_" . $user_id . "_" . time() . "." . $ext;

$upload_dir = __DIR__ . "uploads/";
if (!is_dir($upload_dir)) {
	    mkdir($upload_dir, 0777, true);
}

$target_path = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $target_path)) {
	    echo json_encode([
		            "status" => "error",
			            "message" => "Failed to save file."
				        ]);
	        exit;
}

$sql = "INSERT INTO users_info (user_id, profile_picture)
	        VALUES (?, ?)
		        ON DUPLICATE KEY UPDATE profile_picture = VALUES(profile_picture)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $filename);
$stmt->execute();

echo json_encode([
	    "status" => "success",
	        "file" => "uploads/" . $filename
]);

