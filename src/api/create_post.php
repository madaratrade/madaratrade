<?php
// ۱. جلوگیری از نمایش ارورهای متنی که JSON را خراب می‌کنند
error_reporting(E_ALL);
ini_set('display_errors', 0); 

header('Content-Type: application/json');

session_start();

function sendError($message, $debug = null) {
    echo json_encode([
        'success' => false, 
        'message' => $message,
        'debug' => $debug
    ]);
    exit;
}

// ۲. چک کردن لاگین
if (!isset($_SESSION['user_id'], $_SESSION['username'])) {
    sendError('You must be logged in to post.');
}

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'];

try {
    // ۳. لود کردن کانفیگ مونگو (مسیر نسبت به پوشه api)
    $configFile = __DIR__ . '/../config/mongo.php';
    if (!file_exists($configFile)) {
        sendError("Config file not found at: " . $configFile);
    }
    require_once $configFile;

    // نکته: متغیر $db باید در mongo.php تعریف شده باشد (مثلاً $db = $client->madaratrade)
    if (!isset($db)) {
        sendError("Database connection variable (\$db) is not defined in mongo.php");
    }

    // ۴. دریافت داده‌ها
    $caption = $_POST['caption'] ?? '';
    $tags = $_POST['tags'] ?? [];
    $files = $_FILES['images'] ?? null;

    if (!$files || empty($files['name'][0])) {
        sendError('Please select at least one image.');
    }

    // ۵. گرفتن ID ترتیبی برای پست کاربر
    $counterCol = $db->post_counters;
    $res = $counterCol->findOneAndUpdate(
        ['username' => $username],
        ['$inc' => ['last_id' => 1]],
        ['upsert' => true, 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
    );
    $new_post_id = $res['last_id'];

    // ۶. تنظیم مسیر ذخیره در پوشه images (مسیر فیزیکی روی سرور)
    // ساختار: /var/www/html/src/images/posts/username/post_id/
    $uploadBase = __DIR__ . "/../images/posts/{$username}/{$new_post_id}/";
    
    if (!is_dir($uploadBase)) {
        if (!mkdir($uploadBase, 0777, true)) {
            sendError("Failed to create directory: " . $uploadBase);
        }
    }

    $uploadedFiles = [];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    foreach ($files['name'] as $key => $name) {
        $tmpName = $files['tmp_name'][$key];
        $type = $files['type'][$key];
        $size = $files['size'][$key];

        if (!in_array($type, $allowedTypes)) continue;

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $fileName = date('Y-m-d') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $uploadBase . $fileName;

        if (move_uploaded_file($tmpName, $destination)) {
            $uploadedFiles[] = [
                'filename' => $fileName,
                'original_name' => $name,
                // مسیر برای نمایش در تگ img: images/posts/username/id/file.jpg
                'web_path' => "images/posts/{$username}/{$new_post_id}/{$fileName}",
                'size' => $size
            ];
        }
    }

    if (empty($uploadedFiles)) {
        sendError('No valid images were uploaded.');
    }

    // ۷. درج در MongoDB
    $postsCol = $db->posts;
    $insertResult = $postsCol->insertOne([
        'post_id'    => (int)$new_post_id,
        'user_id'    => $user_id,
        'username'   => $username,
        'caption'    => $caption,
        'tags'       => $tags,
        'images'     => $uploadedFiles,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'stats'      => ['likes' => 0, 'comments' => 0]
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Post published!',
        'post_id' => $new_post_id,
        'redirect' => "profile.php?username=" . urlencode($username)
    ]);

} catch (Exception $e) {
    // اگر هر اروری داد، اینجا به جای ۵۰۰، ارور رو به صورت JSON برمی‌گردونه
    sendError("Server exception: " . $e->getMessage());
} catch (Error $e) {
    // برای خطاهای سیستمی مثل نبودن کلاس مونگو
    sendError("PHP Fatal Error: " . $e->getMessage());
}
