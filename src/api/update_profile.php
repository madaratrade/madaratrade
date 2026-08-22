<?php
// فعال‌سازی نمایش خطاهای PHP برای دیباگ
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

session_start();

// تنظیم هدر خروجی به صورت JSON
header('Content-Type: application/json');

try {
    // اتصال به دیتابیس با mysqli
    if (!file_exists('../db.php')) {
        throw new Exception("Database config file (db.php) not found.");
    }
    include '../db.php';

    // بررسی تعریف متغیر $conn (mysqli)
    if (!isset($conn)) {
        throw new Exception("Database connection variable (\$conn) is not defined.");
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $upload_dir = "/var/www/html/src/uploads/";

    // مدیریت دریافت ورودی‌های JSON (مثل ویرایش بایو)
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);

    if ($data && isset($data['action'])) {
        if ($data['action'] == 'update_bio') {
            $bio = isset($data['bio']) ? htmlspecialchars($data['bio'], ENT_QUOTES, 'UTF-8') : '';
            
            // استفاده از INSERT ... ON DUPLICATE KEY UPDATE در MySQLi
            $stmt = $conn->prepare("INSERT INTO users_info (user_id, bio) VALUES (?, ?) ON DUPLICATE KEY UPDATE bio = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("iss", $user_id, $bio, $bio);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
            exit;
        }

        if ($data['action'] == 'delete_photo') {
            $stmt = $conn->prepare("UPDATE users_info SET profile_picture = NULL WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
            exit;
        }
    }

    // مدیریت آپلود تصویر پروفایل (از طریق فرم معمولی)
    if (isset($_POST['action']) && $_POST['action'] == 'upload_photo') {
        if (!isset($_FILES['photo'])) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded']);
            exit;
        }
        
        $file = $_FILES['photo'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "user_" . $user_id . "_" . time() . "." . $ext;
        $target = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $stmt = $conn->prepare("INSERT INTO users_info (user_id, profile_picture) VALUES (?, ?) ON DUPLICATE KEY UPDATE profile_picture = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("iss", $user_id, $filename, $filename);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'filename' => $filename]);
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action or request']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
