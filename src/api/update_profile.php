<?php
session_start();

// Connect to database file route
include '../db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$upload_dir = "/var/www/html/src/uploads/";

// Image upload management
if (isset($_POST['action']) && $_POST['action'] == 'upload_photo') {
    $file = $_FILES['photo'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "user_" . $user_id . "_" . time() . "." . $ext;
    $target = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
	// Update into database
        $stmt = $pdo->prepare("INSERT INTO users_info (user_id, profile_picture) VALUES (?, ?) ON DUPLICATE KEY UPDATE profile_picture = ?");
        $stmt->execute([$user_id, $filename, $filename]);
        echo json_encode(['success' => true, 'filename' => $filename]);
    }
    exit;
}

// Edit bio management (Json input)
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['action'])) {
    if ($data['action'] == 'update_bio') {
        $bio = htmlspecialchars($data['bio']);
        $stmt = $pdo->prepare("INSERT INTO users_info (user_id, bio) VALUES (?, ?) ON DUPLICATE KEY UPDATE bio = ?");
	$stmt->execute([$user_id, $bio, $bio]);
	echo json_encode(['success' => true]);
    }

    if ($data['action'] == 'delete_photo') {
	// Remove file from server (optional to have a better disk management)
        $stmt = $pdo->prepare("UPDATE users_info SET profile_picture = NULL WHERE user_id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true]);
    }
}
?>

