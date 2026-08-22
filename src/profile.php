<?php
<<<<<<< HEAD
=======

>>>>>>> d24f06f (Update / fix)
session_start();

require_once __DIR__ . '/db.php';

if (!isset($conn) || !$conn) {
<<<<<<< HEAD
    die("Database connection not initialized.");
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fetchPostsFromApi($username) {
    $result = ['posts' => [], 'postsCount' => 0, 'apiError' => false];
    if ($username === '') return $result;

    $apiUrl = 'http://127.0.0.1:8000/posts/by-username/' . urlencode($username);
    $context = stream_context_create([
        'http' => ['method' => 'GET', 'timeout' => 5, 'header' => "Accept: application/json\r\n"]
    ]);

    $response = @file_get_contents($apiUrl, false, $context);
=======
    die('Database connection not initialized.');
}

/*
|--------------------------------------------------------------------------
| Security
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
*/

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizePostId($value): string
{
    if (is_string($value) || is_int($value)) {
        return trim((string)$value);
    }

    if (is_array($value)) {
        foreach (['$oid', 'oid', 'id', '_id'] as $key) {
            if (isset($value[$key])) {
                return normalizePostId($value[$key]);
            }
        }
    }

    return '';
}

function getPostId(array $post): string
{
    foreach (['post_id', 'post_id', 'postId', 'id'] as $key) {
        if (!array_key_exists($key, $post)) {
            continue;
        }

        $postId = normalizePostId($post[$key]);

        if ($postId !== '') {
            return $postId;
        }
    }

    return '';
}

function normalizePostTags($tags): array
{
    if (is_array($tags)) {
        $normalizedTags = [];

        foreach ($tags as $tag) {
            if (is_scalar($tag) && trim((string)$tag) !== '') {
                $normalizedTags[] = trim((string)$tag);
            }
        }

        return array_values(array_unique($normalizedTags));
    }

    if (is_string($tags) && trim($tags) !== '') {
        $parts = preg_split('/[\s,]+/', trim($tags));

        if (!is_array($parts)) {
            return [];
        }

        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, static function ($tag) {
            return $tag !== '';
        });

        return array_values(array_unique($parts));
    }

    return [];
}

function buildPostUrl(
    string $username,
    string $postId,
    array $tags = []
): string {
    $query = [
        'userName' => $username,
        'postId'   => $postId,
    ];

    if (!empty($tags)) {
        $query['postTags'] = implode(',', $tags);
    }

    return 'showpost.php?' . http_build_query(
        $query,
        '',
        '&',
        PHP_QUERY_RFC3986
    );
}

function getPostImageUrl(array $post): string
{
    if (
        !isset($post['images']) ||
        !is_array($post['images']) ||
        empty($post['images'])
    ) {
        return 'https://via.placeholder.com/300';
    }

    $firstImage = $post['images'][0];

    if (is_array($firstImage)) {
        foreach (['web_path', 'url', 'path', 'image_url'] as $key) {
            if (
                !empty($firstImage[$key]) &&
                is_string($firstImage[$key])
            ) {
                return trim($firstImage[$key]);
            }
        }
    }

    if (is_string($firstImage) && trim($firstImage) !== '') {
        return trim($firstImage);
    }

    return 'https://via.placeholder.com/300';
}

function getPostTitle(array $post, int $maxLength = 20): string
{
    $postId = getPostId($post);
    $idPart = $postId !== '' ? 'ID: ' . $postId : '';

    $mainText = '';

    foreach (['tags', 'car_name', 'caption', 'title'] as $field) {
        if (empty($post[$field])) {
            continue;
        }

        $value = $post[$field];

        if (is_array($value)) {
            $readableItems = [];

            foreach ($value as $item) {
                if (is_scalar($item) && trim((string)$item) !== '') {
                    $readableItems[] = trim((string)$item);
                }
            }

            $mainText = implode(', ', $readableItems);
        } elseif (is_scalar($value)) {
            $mainText = trim((string)$value);
        }

        if ($mainText !== '') {
            break;
        }
    }

    if ($mainText !== '' && mb_strlen($mainText) > $maxLength) {
        $mainText = mb_substr($mainText, 0, $maxLength) . '...';
    }

    if ($idPart !== '' && $mainText !== '') {
        return $idPart . ' | ' . $mainText;
    }

    if ($idPart !== '') {
        return $idPart;
    }

    if ($mainText !== '') {
        return $mainText;
    }

    return 'Untitled Post';
}

function fetchPostsFromApi(string $username): array
{
    $result = [
        'posts'      => [],
        'postsCount' => 0,
        'apiError'   => false,
    ];

    if ($username === '') {
        return $result;
    }

    $apiUrl = 'http://fastapi:8000/posts/by-username/' .
        rawurlencode($username);

    $context = stream_context_create([
        'http' => [
            'method'        => 'GET',
            'timeout'       => 5,
            'ignore_errors' => true,
            'header'        => "Accept: application/json\r\n",
        ],
    ]);

    $response = @file_get_contents($apiUrl, false, $context);

>>>>>>> d24f06f (Update / fix)
    if ($response === false) {
        $result['apiError'] = true;
        return $result;
    }

    $decoded = json_decode($response, true);
<<<<<<< HEAD
=======

>>>>>>> d24f06f (Update / fix)
    if (!is_array($decoded)) {
        $result['apiError'] = true;
        return $result;
    }

<<<<<<< HEAD
    $result['posts'] = $decoded;
    $result['postsCount'] = count($decoded);
    return $result;
}

$profileUsername = isset($_GET['username']) ? trim($_GET['username']) : '';
$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$currentUsername = isset($_SESSION['username']) ? trim($_SESSION['username']) : '';
=======
    if (isset($decoded['posts']) && is_array($decoded['posts'])) {
        $result['posts'] = $decoded['posts'];
    } else {
        $result['posts'] = $decoded;
    }

    $result['posts'] = array_values(
        array_filter($result['posts'], static function ($post) {
            return is_array($post);
        })
    );

    $result['postsCount'] = count($result['posts']);

    return $result;
}

function redirectToProfile(string $username): void
{
    header(
        'Location: profile.php?username=' .
        rawurlencode($username)
    );

    exit;
}

function getUserAvatar(?string $profilePicture): string
{
    $profilePicture = trim((string)$profilePicture);

    if ($profilePicture === '') {
        return 'uploads/default-avatar.png';
    }

    if (
        preg_match(
            '~^(?:https?://|/|uploads/)~i',
            $profilePicture
        )
    ) {
        return $profilePicture;
    }

    return 'uploads/' . ltrim($profilePicture, '/');
}

function getUserDisplayName(array $user): string
{
    $displayName = trim(
        ($user['first_name'] ?? '') .
        ' ' .
        ($user['last_name'] ?? '')
    );

    if ($displayName !== '') {
        return $displayName;
    }

    return trim((string)($user['username'] ?? 'User'));
}

/*
|--------------------------------------------------------------------------
| Current profile and logged-in user
|--------------------------------------------------------------------------
*/

$profileUsername = isset($_GET['username'])
    ? trim((string)$_GET['username'])
    : '';

$currentUserId = isset($_SESSION['user_id'])
    ? (int)$_SESSION['user_id']
    : 0;

$currentUsername = isset($_SESSION['username'])
    ? trim((string)$_SESSION['username'])
    : '';
>>>>>>> d24f06f (Update / fix)

if ($profileUsername === '') {
    if ($currentUsername !== '') {
        $profileUsername = $currentUsername;
    } else {
        die('Profile not specified.');
    }
}

<<<<<<< HEAD
=======
/*
|--------------------------------------------------------------------------
| Load profile
|--------------------------------------------------------------------------
*/

>>>>>>> d24f06f (Update / fix)
$sql = "
    SELECT
        ua.id AS user_id,
        ua.username,
        ua.email,
        ua.first_name,
        ua.last_name,
        ui.profile_picture,
        ui.bio,
        ui.instagram_link,
        COALESCE(ui.balance, 0.00) AS balance
    FROM users_account ua
<<<<<<< HEAD
    LEFT JOIN users_info ui ON ui.user_id = ua.id
=======
    LEFT JOIN users_info ui
        ON ui.user_id = ua.id
>>>>>>> d24f06f (Update / fix)
    WHERE ua.username = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
<<<<<<< HEAD
$stmt->bind_param('s', $profileUsername);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profile) {
    $profile = [
        'user_id' => 0, 'username' => $profileUsername, 'email' => '',
        'profile_picture' => '', 'first_name' => '', 'last_name' => '',
        'bio' => 'No bio available.', 'instagram_link' => '', 'balance' => 0.00
    ];
}

$profile['avatar'] = !empty($profile['profile_picture'])
    ? 'uploads/' . $profile['profile_picture']
    : 'uploads/default-avatar.png';

$profileUserId = (int)$profile['user_id'];
$isOwnProfile = ($currentUserId > 0 && $profileUserId > 0 && $currentUserId === $profileUserId);

$followersCount = 0; $followingCount = 0; $isFollowing = false; $followMessage = '';

if ($profileUserId > 0) {
    $res = $conn->query("SELECT COUNT(*) as total FROM follows WHERE following_id = $profileUserId");
    $followersCount = $res->fetch_assoc()['total'];
    $res = $conn->query("SELECT COUNT(*) as total FROM follows WHERE follower_id = $profileUserId");
    $followingCount = $res->fetch_assoc()['total'];
}

$postData = fetchPostsFromApi($profile['username']);
$posts = $postData['posts'];
$postsCount = $postData['postsCount'];

$displayName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
if ($displayName === '') $displayName = $profile['username'];

// اصلاح ساختار URL برای کپی کردن
$shareUrl = 'https://madaratrade.com/@' . h($profile['username']);
?>
<?php
$shareUrl = 'https://madaratrade.com/@' . urlencode($profile['username']);
?>

=======

if (!$stmt) {
    die('Unable to prepare profile query.');
}

$stmt->bind_param('s', $profileUsername);
$stmt->execute();

$profileResult = $stmt->get_result();
$profile = $profileResult->fetch_assoc();

$stmt->close();

if (!$profile) {
    http_response_code(404);
    die('Profile not found.');
}

$profile['avatar'] = getUserAvatar(
    $profile['profile_picture'] ?? ''
);

$profileUserId = (int)$profile['user_id'];

$isOwnProfile = (
    $currentUserId > 0 &&
    $profileUserId > 0 &&
    $currentUserId === $profileUserId
);

/*
|--------------------------------------------------------------------------
| Follow / Unfollow POST handler
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action'])
        ? trim((string)$_POST['action'])
        : '';

    if ($action === 'toggle_follow') {
        $postedProfileUserId = isset($_POST['profile_user_id'])
            ? (int)$_POST['profile_user_id']
            : 0;

        $postedCsrfToken = isset($_POST['csrf_token'])
            ? (string)$_POST['csrf_token']
            : '';

        if ($currentUserId <= 0) {
            header(
                'Location: login.php?redirect=' .
                rawurlencode(
                    'profile.php?username=' . $profileUsername
                )
            );
            exit;
        }

        if (
            $postedCsrfToken === '' ||
            !hash_equals($csrfToken, $postedCsrfToken)
        ) {
            http_response_code(403);
            die('Invalid security token. Please refresh the page.');
        }

        if (
            $postedProfileUserId <= 0 ||
            $postedProfileUserId !== $profileUserId ||
            $postedProfileUserId === $currentUserId
        ) {
            redirectToProfile($profileUsername);
        }

        $stmt = $conn->prepare("
            SELECT 1
            FROM follows
            WHERE follower_id = ?
              AND following_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            die('Unable to prepare follow check query.');
        }

        $stmt->bind_param(
            'ii',
            $currentUserId,
            $profileUserId
        );

        $stmt->execute();

        $alreadyFollowing = (
            $stmt->get_result()->fetch_assoc() !== null
        );

        $stmt->close();

        if ($alreadyFollowing) {
            $stmt = $conn->prepare("
                DELETE FROM follows
                WHERE follower_id = ?
                  AND following_id = ?
            ");

            if (!$stmt) {
                die('Unable to prepare unfollow query.');
            }

            $stmt->bind_param(
                'ii',
                $currentUserId,
                $profileUserId
            );

            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("
                INSERT INTO follows (
                    follower_id,
                    following_id,
                    created_at
                )
                VALUES (?, ?, NOW())
            ");

            if (!$stmt) {
                die('Unable to prepare follow query.');
            }

            $stmt->bind_param(
                'ii',
                $currentUserId,
                $profileUserId
            );

            $stmt->execute();
            $stmt->close();
        }

        redirectToProfile($profileUsername);
    }
}

/*
|--------------------------------------------------------------------------
| Followers, following and follow status
|--------------------------------------------------------------------------
*/

$followersCount = 0;
$followingCount = 0;
$isFollowing = false;

$followersList = [];
$followingList = [];

if ($profileUserId > 0) {
    // Followers
    $stmt = $conn->prepare("
        SELECT
            ua.id AS user_id,
            ua.username,
            ua.first_name,
            ua.last_name,
            ui.profile_picture
        FROM follows f
        INNER JOIN users_account ua
            ON ua.id = f.follower_id
        LEFT JOIN users_info ui
            ON ui.user_id = ua.id
        WHERE f.following_id = ?
        ORDER BY f.created_at DESC
    ");

    if ($stmt) {
        $stmt->bind_param('i', $profileUserId);
        $stmt->execute();

        $followersResult = $stmt->get_result();

        while ($follower = $followersResult->fetch_assoc()) {
            $follower['avatar'] = getUserAvatar(
                $follower['profile_picture'] ?? ''
            );

            $follower['display_name'] = getUserDisplayName(
                $follower
            );

            $followersList[] = $follower;
        }

        $stmt->close();
    }

    // Following
    $stmt = $conn->prepare("
        SELECT
            ua.id AS user_id,
            ua.username,
            ua.first_name,
            ua.last_name,
            ui.profile_picture
        FROM follows f
        INNER JOIN users_account ua
            ON ua.id = f.following_id
        LEFT JOIN users_info ui
            ON ui.user_id = ua.id
        WHERE f.follower_id = ?
        ORDER BY f.created_at DESC
    ");

    if ($stmt) {
        $stmt->bind_param('i', $profileUserId);
        $stmt->execute();

        $followingResult = $stmt->get_result();

        while ($followedUser = $followingResult->fetch_assoc()) {
            $followedUser['avatar'] = getUserAvatar(
                $followedUser['profile_picture'] ?? ''
            );

            $followedUser['display_name'] = getUserDisplayName(
                $followedUser
            );

            $followingList[] = $followedUser;
        }

        $stmt->close();
    }

    $followersCount = count($followersList);
    $followingCount = count($followingList);
}

if (
    $currentUserId > 0 &&
    $profileUserId > 0 &&
    !$isOwnProfile
) {
    $stmt = $conn->prepare("
        SELECT 1
        FROM follows
        WHERE follower_id = ?
          AND following_id = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param(
            'ii',
            $currentUserId,
            $profileUserId
        );

        $stmt->execute();

        $isFollowing = (
            $stmt->get_result()->fetch_assoc() !== null
        );

        $stmt->close();
    }
}

/*
|--------------------------------------------------------------------------
| Posts
|--------------------------------------------------------------------------
*/

$postData = fetchPostsFromApi($profile['username']);

$posts = $postData['posts'];
$postsCount = $postData['postsCount'];

/*
|--------------------------------------------------------------------------
| Display data
|--------------------------------------------------------------------------
*/

$displayName = getUserDisplayName($profile);

$shareUrl = 'https://madaratrade.com/@' .
    rawurlencode($profile['username']);

?>
>>>>>>> d24f06f (Update / fix)
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($profile['username']) ?> - MadaraTrade</title>
<<<<<<< HEAD
    <style>
        :root {
            --bg1: #07111f; --bg2: #050a12; --panel: rgba(14, 23, 40, 0.68);
            --border: rgba(255, 255, 255, 0.10); --text: #f4f7fb; --muted: #9fb2c8;
            --cyan: #00e5ff; --pink: #ff4fd8; --shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        }
        body { margin: 0; color: var(--text); font-family: Arial, sans-serif; background: radial-gradient(circle at top left, rgba(0,229,255,.15), transparent 30%), radial-gradient(circle at top right, rgba(255,79,216,.14), transparent 28%), linear-gradient(180deg, var(--bg1) 0%, var(--bg2) 100%); min-height: 100vh; }
        .container { width: min(1200px, calc(100% - 24px)); margin: 0 auto; padding: 24px 0 60px; }
        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 24px; backdrop-filter: blur(18px); padding: 24px; }
        .hero { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
        .avatar-wrapper { position: relative; width: 132px; height: 132px; margin: 0 auto 14px; }
        .avatar { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid var(--cyan); }
        .avatar-upload-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; opacity: 0; cursor: pointer; transition: 0.2s; }
        .avatar-wrapper:hover .avatar-upload-overlay { opacity: 1; }
        .btn { border: 1px solid var(--border); background: rgba(255,255,255,.06); color: #fff; padding: 12px 16px; border-radius: 14px; cursor: pointer; font-size: 14px; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, var(--cyan), var(--pink)); color: #03111f; font-weight: bold; border: none; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 22px; }
        .stat { background: rgba(255,255,255,.05); border-radius: 18px; padding: 16px; border: 1px solid var(--border); }
        /* رنگ آبی برای اعداد */
        .stat-value { font-size: 20px; font-weight: 800; color: var(--cyan); }
        
        .posts-section { margin-top: 30px; }
        .no-posts { background: rgba(255,255,255,0.03); border: 1px dashed var(--border); border-radius: 20px; padding: 60px; text-align: center; color: var(--muted); }

        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; z-index: 1000; }
        .profile-card { background: #1e2428; width: 400px; border-radius: 15px; color: white; border: 1px solid var(--border); }
        .profile-header { display: flex; justify-content: space-between; padding: 15px; border-bottom: 1px solid #333; align-items: center; }
        .profile-main { text-align: center; padding: 20px; }
        .dropdown-content { display: none; position: absolute; right: 20px; background: #2d353b; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.5); z-index: 10; min-width: 150px; }
        .dropdown-content a { color: white; padding: 12px 20px; display: block; text-decoration: none; font-size: 14px; }
        .dropdown-content a:hover { background: rgba(255,255,255,0.1); }
        .list-item { display: flex; padding: 12px 20px; align-items: center; border-bottom: 1px solid #2d353b; }
        .item-icon { margin-right: 15px; font-size: 18px; }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
        }
        
        .modal-card {
            background: #151c27;
            width: min(400px, 90%);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            overflow: hidden;
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .share-input {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid #333;
            background: #0a0f16;
            color: #fff;
            box-sizing: border-box;
            font-size: 14px;
        }

=======

    <style>
        :root {
            --bg1: #07111f;
            --bg2: #050a12;
            --panel: rgba(14, 23, 40, 0.68);
            --panel-strong: rgba(12, 20, 35, 0.96);
            --border: rgba(255, 255, 255, 0.10);
            --text: #f4f7fb;
            --muted: #9fb2c8;
            --cyan: #00e5ff;
            --pink: #ff4fd8;
            --danger: #ff708d;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        }

        * {
            box-sizing: border-box;
        }

        html {
            color-scheme: dark;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: Arial, sans-serif;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(0, 229, 255, 0.15),
                    transparent 30%
                ),
                radial-gradient(
                    circle at top right,
                    rgba(255, 79, 216, 0.14),
                    transparent 28%
                ),
                linear-gradient(
                    180deg,
                    var(--bg1) 0%,
                    var(--bg2) 100%
                );
        }

        body.modal-open {
            overflow: hidden;
        }

        button,
        input,
        textarea {
            font: inherit;
        }

        .container {
            width: min(1200px, calc(100% - 24px));
            margin: 0 auto;
            padding: 24px 0 60px;
        }

        .panel {
            padding: 24px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .brand {
            font-size: 24px;
            font-weight: 900;
        }

        .brand-accent {
            color: var(--cyan);
        }

        .hero {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 20px;
        }

        .profile-summary {
            text-align: center;
        }

        .avatar-wrapper {
            position: relative;
            width: 132px;
            height: 132px;
            margin: 0 auto 14px;
            cursor: pointer;
        }

        .avatar {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 3px solid var(--cyan);
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .avatar-wrapper:hover .avatar {
            transform: scale(1.03);
            border-color: var(--pink);
        }

        .avatar-upload-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.68);
            transition: opacity 0.2s ease;
        }

        .avatar-wrapper:hover .avatar-upload-overlay,
        .avatar-wrapper:focus-within .avatar-upload-overlay {
            opacity: 1;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 16px;
            color: #ffffff;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.06);
            transition:
                transform 0.2s ease,
                border-color 0.2s ease,
                background 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            border-color: rgba(0, 229, 255, 0.40);
            background: rgba(255, 255, 255, 0.09);
        }

        .btn-primary {
            color: #03111f;
            font-weight: 800;
            border: 0;
            background:
                linear-gradient(
                    135deg,
                    var(--cyan),
                    var(--pink)
                );
        }

        .btn-danger {
            color: #ffffff;
            font-weight: 800;
            border: 0;
            background:
                linear-gradient(
                    135deg,
                    #ff708d,
                    #ff4fd8
                );
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 8px;
        }

        .profile-details-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .profile-description {
            flex: 1;
            min-width: 260px;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .profile-actions form {
            margin: 0;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-top: 22px;
        }

        .stat {
            display: block;
            width: 100%;
            min-height: 84px;
            padding: 16px;
            color: inherit;
            text-align: left;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.05);
        }

        button.stat {
            appearance: none;
            font: inherit;
        }

        .stat-clickable {
            cursor: pointer;
            transition:
                transform 0.2s ease,
                border-color 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;
        }

        .stat-clickable:hover,
        .stat-clickable:focus-visible {
            outline: none;
            transform: translateY(-3px);
            border-color: rgba(0, 229, 255, 0.45);
            background: rgba(0, 229, 255, 0.08);
            box-shadow:
                0 12px 30px rgba(0, 0, 0, 0.25),
                0 0 25px rgba(0, 229, 255, 0.08);
        }

        .stat-label {
            color: var(--muted);
            font-size: 13px;
        }

        .stat-value {
            margin-top: 5px;
            color: var(--cyan);
            font-size: 20px;
            font-weight: 800;
        }

        .stat-hint {
            margin-top: 4px;
            color: rgba(159, 178, 200, 0.70);
            font-size: 11px;
        }

        .posts-section {
            margin-top: 30px;
        }

        .posts-grid {
            display: grid;
            grid-template-columns:
                repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .post-link {
            display: block;
            color: inherit;
            text-decoration: none;
        }

        .post-card {
            padding: 10px;
            cursor: pointer;
            transition:
                transform 0.2s ease,
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .post-card:hover {
            transform: translateY(-4px);
            border-color: rgba(0, 229, 255, 0.35);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.25);
        }

        .post-card-disabled {
            cursor: default;
        }

        .post-card-disabled:hover {
            transform: none;
            border-color: var(--border);
            box-shadow: var(--shadow);
        }

        .post-image {
            display: block;
            width: 100%;
            object-fit: cover;
            aspect-ratio: 1 / 1;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.04);
        }

        .post-meta {
            padding: 10px 5px;
        }

        .post-title {
            font-weight: 700;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .post-warning {
            margin-top: 7px;
            color: var(--danger);
            font-size: 12px;
        }

        .no-posts {
            padding: 60px;
            color: var(--muted);
            text-align: center;
            border: 1px dashed var(--border);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.03);
        }

        /* Modals style */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(0, 0, 0, 0.82);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .modal-overlay.is-open {
            display: flex;
        }

        .modal-card {
            width: min(430px, 100%);
            max-height: min(760px, calc(100vh - 36px));
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 22px;
            background: var(--panel-strong);
            box-shadow:
                0 30px 100px rgba(0, 0, 0, 0.65),
                0 0 45px rgba(0, 229, 255, 0.06);
        }

        .modal-card-large {
            width: min(560px, 100%);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 17px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.10);
        }

        .modal-title-wrap {
            min-width: 0;
        }

        .modal-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }

        .modal-subtitle {
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .modal-close {
            display: inline-flex;
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 27px;
            line-height: 1;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 12px;
            background: transparent;
            transition:
                color 0.2s ease,
                border-color 0.2s ease,
                background 0.2s ease;
        }

        .modal-close:hover {
            color: #ffffff;
            border-color: var(--border);
            background: rgba(255, 255, 255, 0.06);
        }

        .modal-body {
            padding: 25px;
        }

        .share-input {
            width: 100%;
            padding: 14px;
            color: #ffffff;
            font-size: 14px;
            border: 1px solid #333333;
            border-radius: 12px;
            background: #0a0f16;
        }

        /* Followers lists style */
        .follow-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.025);
        }

        .follow-tab {
            padding: 11px 14px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 13px;
            background: transparent;
            transition:
                color 0.2s ease,
                border-color 0.2s ease,
                background 0.2s ease;
        }

        .follow-tab:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }

        .follow-tab.active {
            color: #06121f;
            border-color: transparent;
            background:
                linear-gradient(
                    135deg,
                    var(--cyan),
                    var(--pink)
                );
        }

        .follow-tab-count {
            opacity: 0.85;
        }

        .follow-list-container {
            height: min(470px, calc(100vh - 235px));
            min-height: 220px;
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        .follow-panel {
            display: none;
            padding: 8px 14px 14px;
        }

        .follow-panel.active {
            display: block;
        }

        .follow-user {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 10px;
            color: inherit;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 14px;
            transition:
                background 0.2s ease,
                transform 0.2s ease;
        }

        .follow-user:last-child {
            border-bottom-color: transparent;
        }

        .follow-user:hover {
            transform: translateX(3px);
            background: rgba(0, 229, 255, 0.06);
        }

        .follow-user-avatar {
            width: 52px;
            height: 52px;
            flex: 0 0 52px;
            object-fit: cover;
            border: 2px solid rgba(0, 229, 255, 0.55);
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }

        .follow-user-info {
            min-width: 0;
            flex: 1;
        }

        .follow-user-name {
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .follow-user-username {
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .follow-user-arrow {
            flex: 0 0 auto;
            color: var(--cyan);
            font-size: 21px;
        }

        .follow-empty {
            display: flex;
            min-height: 260px;
            align-items: center;
            justify-content: center;
            padding: 35px 20px;
            color: var(--muted);
            text-align: center;
        }

        .follow-empty-icon {
            margin-bottom: 12px;
            font-size: 38px;
        }

        .follow-empty-title {
            color: #ffffff;
            font-weight: 800;
        }

        .follow-empty-text {
            margin-top: 6px;
            font-size: 13px;
            line-height: 1.5;
        }

        /* Account Details and Settings Modal (Own Account) */
        .profile-card {
            width: min(400px, 100%);
            overflow: hidden;
            color: #ffffff;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--panel-strong);
            box-shadow: var(--shadow);
        }

        .profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .profile-main {
            padding: 25px 20px;
            text-align: center;
        }

        .dropdown-content {
            position: absolute;
            right: 0;
            z-index: 10;
            display: none;
            min-width: 150px;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #151f2e;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        .dropdown-content a {
            display: block;
            padding: 12px 20px;
            color: #ffffff;
            font-size: 14px;
            text-decoration: none;
        }

        .dropdown-content a:hover {
            background: rgba(255, 255, 255, 0.10);
        }

        .profile-list {
            padding-bottom: 15px;
        }

        .list-item {
            display: flex;
            align-items: flex-start;
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            text-align: left;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .item-icon {
            margin-right: 15px;
            font-size: 18px;
            margin-top: 3px;
        }

        /* Bio inline textarea styles */
        .bio-edit-area {
            width: 100%;
            height: 80px;
            margin-top: 8px;
            padding: 10px;
            color: #ffffff;
            font-size: 13px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.3);
            resize: none;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .bio-edit-area:focus {
            border-color: var(--cyan);
        }

        .bio-actions {
            margin-top: 8px;
            display: flex;
            justify-content: flex-end;
        }

        @media (max-width: 900px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {
            .container {
                width: min(100% - 16px, 1200px);
                padding-top: 14px;
            }

            .panel {
                padding: 18px;
                border-radius: 19px;
            }

            .profile-description {
                min-width: 100%;
            }

            .profile-actions {
                width: 100%;
            }

            .profile-actions form,
            .profile-actions .btn {
                flex: 1;
            }

            .profile-actions form .btn {
                width: 100%;
            }

            .stats {
                grid-template-columns: 1fr 1fr;
                gap: 9px;
            }

            .stat {
                min-height: 78px;
                padding: 13px;
            }

            .no-posts {
                padding: 36px 20px;
            }

            .modal-overlay {
                padding: 8px;
                align-items: flex-end;
            }

            .modal-card,
            .modal-card-large,
            .profile-card {
                width: 100%;
                max-height: calc(100vh - 16px);
                border-radius: 22px 22px 14px 14px;
            }

            .follow-list-container {
                height: min(520px, calc(100vh - 190px));
            }
        }
>>>>>>> d24f06f (Update / fix)
    </style>
</head>
<body>

<div class="container">
<<<<<<< HEAD
    <div class="topbar" style="display:flex; justify-content:space-between; margin-bottom:20px; align-items: center;">
        <!-- بخش Madara آبی شد -->
        <div style="font-size:24px; font-weight:900;"><span style="color:var(--cyan)">Madara</span>Trade</div>
=======

    <div class="topbar">
        <div class="brand">
            <span class="brand-accent">Madara</span>Trade
        </div>
>>>>>>> d24f06f (Update / fix)
        <a class="btn" href="home.php">Back</a>
    </div>

    <div class="hero">
<<<<<<< HEAD
        <div class="panel" style="text-align:center;">
            <div class="avatar-wrapper">
                <img class="avatar" src="<?= h($profile['avatar']) ?>" alt="Avatar">
                <?php if ($isOwnProfile): ?>
                    <div class="avatar-upload-overlay" onclick="openProfile()">
                        <span style="color:var(--cyan); font-size:12px; font-weight:bold;">VIEW PROFILE</span>
                    </div>
                <?php endif; ?>
            </div>
            <h2 style="margin:10px 0 5px;"><?= h($profile['username']) ?></h2>
            <p style="color:var(--muted); margin:0;">@<?= h($profile['username']) ?></p>
        </div>

        <div class="panel">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div style="flex: 1; padding-right: 20px;">
                    <h1 style="margin:0; font-size:30px;"><?= h($displayName) ?></h1>
                    <div style="margin-top:10px; color:#dbe6f2; line-height: 1.5;"><?= nl2br(h($profile['bio'])) ?></div>
                </div>
                <div style="display:flex; gap:10px;">
                    <?php if ($isOwnProfile): ?>
                        <a class="btn btn-primary" href="post.php">+ New Post</a>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="action" value="toggle_follow">
                            <button type="submit" class="btn <?= $isFollowing ? '' : 'btn-primary' ?>">
=======

        <div class="panel profile-summary">
            <!-- Clicking on this avatar wrapper toggles the corresponding modal -->
            <div 
                class="avatar-wrapper" 
                onclick="<?php echo $isOwnProfile ? 'openProfile()' : "openModal('share-modal')" ?>;"
                role="button"
                tabindex="0"
                aria-label="View user profile avatar"
            >
                <img
                    class="avatar"
                    src="<?= h($profile['avatar']) ?>"
                    alt="<?= h($profile['username']) ?> avatar"
                    onerror="this.src='uploads/default-avatar.png'"
                >
                <?php if ($isOwnProfile): ?>
                    <div class="avatar-upload-overlay">
                        <span style="color: var(--cyan); font-size: 12px; font-weight: bold;">
                            VIEW PROFILE
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <h2 style="margin: 10px 0 5px;">
                <?= h($profile['username']) ?>
            </h2>

            <p style="color: var(--muted); margin: 0;">
                @<?= h($profile['username']) ?>
            </p>
        </div>

        <div class="panel">

            <div class="profile-details-header">
                <div class="profile-description">
                    <h1 style="margin: 0; font-size: 30px;">
                        <?= h($displayName) ?>
                    </h1>
                    <div id="profile-page-bio" style="margin-top: 10px; color: #dbe6f2; line-height: 1.5;">
                        <?= nl2br(h($profile['bio'] ?: 'No bio available.')) ?>
                    </div>
                </div>

                <div class="profile-actions">
                    <?php if ($isOwnProfile): ?>
                        <a class="btn btn-primary" href="post.php">+ New Post</a>
                    <?php else: ?>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="toggle_follow">
                            <input type="hidden" name="profile_user_id" value="<?= (int)$profileUserId ?>">
                            <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                            <button
                                type="submit"
                                class="btn <?= $isFollowing ? 'btn-danger' : 'btn-primary' ?>"
                            >
>>>>>>> d24f06f (Update / fix)
                                <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
                            </button>
                        </form>
                    <?php endif; ?>
<<<<<<< HEAD
		    <button class="btn" onclick="openModal('share-modal')">Share</button>
=======

                    <button type="button" class="btn" onclick="openModal('share-modal')">
                        Share
                    </button>
>>>>>>> d24f06f (Update / fix)
                </div>
            </div>

            <div class="stats">
                <div class="stat">
<<<<<<< HEAD
                    <div style="color:var(--muted); font-size:13px;">Posts</div>
                    <div class="stat-value"><?= (int)$postsCount ?></div>
                </div>
                <div class="stat">
                    <div style="color:var(--muted); font-size:13px;">Followers</div>
                    <div class="stat-value"><?= (int)$followersCount ?></div>
                </div>
                <div class="stat">
                    <div style="color:var(--muted); font-size:13px;">Following</div>
                    <div class="stat-value"><?= (int)$followingCount ?></div>
                </div>
                <div class="stat">
                    <div style="color:var(--muted); font-size:13px;">Balance</div>
                    <div class="stat-value">$<?= number_format($profile['balance'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش پست‌ها با چک کردن وضعیت خالی بودن -->
    <div class="posts-section">
        <h3 style="margin-bottom: 20px;">POSTS</h3>
        <?php if ($postsCount > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                <?php foreach ($posts as $post): ?>
                    <div class="panel" style="padding: 10px;">
                        <img src="<?= h($post['images'][0] ?? 'https://via.placeholder.com/300') ?>" style="width: 100%; border-radius: 15px; aspect-ratio: 1/1; object-fit: cover;">
                        <div style="padding: 10px 5px;">
                            <div style="font-weight: bold;"><?= h($post['car_name'] ?? 'Car Post') ?></div>
                        </div>
                    </div>
=======
                    <div class="stat-label">Posts</div>
                    <div class="stat-value"><?= (int)$postsCount ?></div>
                </div>

                <button
                    type="button"
                    class="stat stat-clickable"
                    onclick="openFollowModal('followers')"
                    aria-label="View followers"
                >
                    <div class="stat-label">Followers</div>
                    <div class="stat-value"><?= (int)$followersCount ?></div>
                    <div class="stat-hint">View followers</div>
                </button>

                <button
                    type="button"
                    class="stat stat-clickable"
                    onclick="openFollowModal('following')"
                    aria-label="View following"
                >
                    <div class="stat-label">Following</div>
                    <div class="stat-value"><?= (int)$followingCount ?></div>
                    <div class="stat-hint">View following</div>
                </button>

                <div class="stat">
                    <div class="stat-label">Balance</div>
                    <div class="stat-value">
                        $<?= number_format((float)$profile['balance'], 2) ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="posts-section">
        <h3 style="margin-bottom: 20px;">POSTS</h3>

        <?php if (!empty($postData['apiError'])): ?>
            <div class="no-posts">
                <div style="font-size: 40px; margin-bottom: 10px;">⚠️</div>
                <div>Unable to load posts from API.</div>
            </div>
        <?php elseif ($postsCount > 0): ?>
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                    <?php
                    if (!is_array($post)) {
                        continue;
                    }
                    $postImage = getPostImageUrl($post);
                    $postTitle = getPostTitle($post);
                    $postId = getPostId($post);
                    $postTags = normalizePostTags($post['tags'] ?? []);

                    $postUrl = $postId !== ''
                        ? buildPostUrl($profile['username'], $postId, $postTags)
                        : '';

                    $cardClass = $postUrl !== ''
                        ? 'panel post-card'
                        : 'panel post-card post-card-disabled';
                    ?>

                    <?php if ($postUrl !== ''): ?>
                        <a class="post-link" href="<?= h($postUrl) ?>">
                    <?php endif; ?>

                    <div class="<?= h($cardClass) ?>">
                        <img
                            class="post-image"
                            src="<?= h($postImage) ?>"
                            alt="<?= h($postTitle) ?>"
                            loading="lazy"
                        >
                        <div class="post-meta">
                            <div class="post-title"><?= h($postTitle) ?></div>
                            <?php if ($postId === ''): ?>
                                <div class="post-warning">Post ID is missing</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($postUrl !== ''): ?>
                        </a>
                    <?php endif; ?>
>>>>>>> d24f06f (Update / fix)
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-posts">
                <div style="font-size: 40px; margin-bottom: 10px;">📦</div>
                <div>No posts yet.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<<<<<<< HEAD
<!-- Profile Modal -->
<div id="profile-modal" class="modal-overlay" style="display:none;">
    <div class="profile-card">
        <div class="profile-header">
            <button onclick="closeProfile()" style="background:none; border:none; color:#888; font-size:24px; cursor:pointer; line-height: 1;">&times;</button>
            <span style="font-weight: bold;">User account</span>
            <div style="position:relative;">
                <button onclick="toggleMenu()" style="background:none; border:none; color:#888; font-size:20px; cursor:pointer;">&#8942;</button>
                <div id="menu-content" class="dropdown-content">
                    <a href="#" onclick="triggerUpload()">Add Photo</a>
                    <a href="update_bio.php">Edit Profile</a>
                    <a href="logout.php" style="color:#ff5e5e;">Logout</a>
=======
<!-- Followers / Following modal -->
<div
    id="follow-modal"
    class="modal-overlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="follow-modal-title"
>
    <div class="modal-card modal-card-large">
        <div class="modal-header">
            <div class="modal-title-wrap">
                <h2 id="follow-modal-title" class="modal-title">Connections</h2>
                <div class="modal-subtitle">@<?= h($profile['username']) ?></div>
            </div>
            <button
                type="button"
                class="modal-close"
                onclick="closeModal('follow-modal')"
                aria-label="Close"
            >
                &times;
            </button>
        </div>

        <div class="follow-tabs">
            <button
                type="button"
                id="followers-tab"
                class="follow-tab active"
                onclick="switchFollowTab('followers')"
            >
                Followers <span class="follow-tab-count">(<?= (int)$followersCount ?>)</span>
            </button>
            <button
                type="button"
                id="following-tab"
                class="follow-tab"
                onclick="switchFollowTab('following')"
            >
                Following <span class="follow-tab-count">(<?= (int)$followingCount ?>)</span>
            </button>
        </div>

        <div class="follow-list-container">
            <!-- Followers list -->
            <div id="followers-panel" class="follow-panel active">
                <?php if (!empty($followersList)): ?>
                    <?php foreach ($followersList as $follower): ?>
                        <a
                            class="follow-user"
                            href="profile.php?username=<?= rawurlencode($follower['username']) ?>"
                        >
                            <img
                                class="follow-user-avatar"
                                src="<?= h($follower['avatar']) ?>"
                                alt="<?= h($follower['username']) ?> avatar"
                                loading="lazy"
                                onerror="this.src='uploads/default-avatar.png'"
                            >
                            <div class="follow-user-info">
                                <div class="follow-user-name"><?= h($follower['display_name']) ?></div>
                                <div class="follow-user-username">@<?= h($follower['username']) ?></div>
                            </div>
                            <div class="follow-user-arrow">›</div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="follow-empty">
                        <div>
                            <div class="follow-empty-icon">👥</div>
                            <div class="follow-empty-title">No followers yet</div>
                            <div class="follow-empty-text">This user does not have any followers yet.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Following list -->
            <div id="following-panel" class="follow-panel">
                <?php if (!empty($followingList)): ?>
                    <?php foreach ($followingList as $followedUser): ?>
                        <a
                            class="follow-user"
                            href="profile.php?username=<?= rawurlencode($followedUser['username']) ?>"
                        >
                            <img
                                class="follow-user-avatar"
                                src="<?= h($followedUser['avatar']) ?>"
                                alt="<?= h($followedUser['username']) ?> avatar"
                                loading="lazy"
                                onerror="this.src='uploads/default-avatar.png'"
                            >
                            <div class="follow-user-info">
                                <div class="follow-user-name"><?= h($followedUser['display_name']) ?></div>
                                <div class="follow-user-username">@<?= h($followedUser['username']) ?></div>
                            </div>
                            <div class="follow-user-arrow">›</div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="follow-empty">
                        <div>
                            <div class="follow-empty-icon">🔗</div>
                            <div class="follow-empty-title">Not following anyone</div>
                            <div class="follow-empty-text">This user is not following anyone yet.</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Own account details and settings modal -->
<div
    id="profile-modal"
    class="modal-overlay"
    role="dialog"
    aria-modal="true"
>
    <div class="profile-card">
        <div class="profile-header">
            <button
                type="button"
                onclick="closeProfile()"
                style="background: none; border: none; color: #888; font-size: 24px; cursor: pointer; line-height: 1;"
                aria-label="Close"
            >
                &times;
            </button>
            <span style="font-weight: bold;">User account</span>
            <div style="position: relative;">
                <button
                    type="button"
                    onclick="toggleMenu()"
                    style="background: none; border: none; color: #888; font-size: 20px; cursor: pointer;"
                    aria-label="Account menu"
                >
                    &#8942;
                </button>
                <div id="menu-content" class="dropdown-content">
                    <a href="#" onclick="triggerUpload(); return false;">Add Photo</a>
                    <a href="logout.php" style="color: #ff5e5e;">Logout</a>
>>>>>>> d24f06f (Update / fix)
                </div>
            </div>
        </div>

        <div class="profile-main">
<<<<<<< HEAD
            <div style="position:relative; width:100px; margin:0 auto;">
                <img id="user-avatar" src="<?= h($profile['avatar']) ?>" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border: 2px solid var(--cyan);">
                <div onclick="triggerUpload()" style="position:absolute; bottom:0; right:0; background:var(--cyan); border-radius:50%; padding:6px; cursor:pointer; line-height: 1; font-size: 14px;">📷</div>
            </div>
            <h3 style="margin: 15px 0 5px;"><?= h($displayName) ?></h3>
            <p style="color:#888; margin: 0;">@<?= h($profile['username']) ?> | <span style="color: var(--cyan);">$<?= number_format($profile['balance'], 2) ?></span></p>
=======
            <div style="position: relative; width: 100px; margin: 0 auto;">
                <img
                    id="user-avatar"
                    src="<?= h($profile['avatar']) ?>"
                    alt="User avatar"
                    style="width: 100px; height: 100px; object-fit: cover; border: 2px solid var(--cyan); border-radius: 50%;"
                    onerror="this.src='uploads/default-avatar.png'"
                >
                <div
                    onclick="triggerUpload()"
                    role="button"
                    tabindex="0"
                    style="position: absolute; right: 0; bottom: 0; padding: 6px; cursor: pointer; line-height: 1; font-size: 14px; border-radius: 50%; background: var(--cyan);"
                >
                    📷
                </div>
            </div>

            <h3 style="margin: 15px 0 5px;">
                <?= h($displayName) ?>
            </h3>
            <p style="color: #888; margin: 0;">
                @<?= h($profile['username']) ?> | <span style="color: var(--cyan); font-weight: bold;">$<?= number_format((float)$profile['balance'], 2) ?></span>
            </p>
>>>>>>> d24f06f (Update / fix)
        </div>

        <div class="profile-list">
            <div class="list-item">
                <span class="item-icon">ℹ️</span>
<<<<<<< HEAD
                <div><small style="color:#888; display:block;">About</small><span><?= nl2br(h($profile['bio'])) ?></span></div>
            </div>
            <div class="list-item">
                <span class="item-icon">💰</span>
                <div><small style="color:#888; display:block;">Wallet Balance</small><span style="color: var(--cyan); font-weight: bold;">$<?= number_format($profile['balance'], 2) ?></span></div>
=======
                <div style="width: 100%;">
                    <small style="color: #888; display: block; margin-bottom: 2px;">About</small>
                    <?php if ($isOwnProfile): ?>
                        <!-- Textarea to edit Bio directly inside the modal -->
                        <textarea 
                            id="bio-textarea" 
                            class="bio-edit-area" 
                            placeholder="Tell us about yourself..."
                        ><?= h($profile['bio']) ?></textarea>
                        <div class="bio-actions">
                            <button 
                                type="button" 
                                class="btn btn-primary btn-sm" 
                                onclick="saveBio()"
                            >
                                Save Bio
                            </button>
                        </div>
                    <?php else: ?>
                        <span><?= nl2br(h($profile['bio'] ?: 'No bio available.')) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="list-item">
                <span class="item-icon">💰</span>
                <div>
                    <small style="color: #888; display: block;">Wallet Balance</small>
                    <span style="color: var(--cyan); font-weight: bold;">$<?= number_format((float)$profile['balance'], 2) ?></span>
                </div>
>>>>>>> d24f06f (Update / fix)
            </div>
        </div>
    </div>
</div>

<<<<<<< HEAD
<input type="file" id="photo-input" style="display:none" accept="image/*" onchange="uploadPhoto(this)">

<script>
function openProfile() { document.getElementById("profile-modal").style.display = "flex"; }
function closeProfile() { document.getElementById("profile-modal").style.display = "none"; }
function toggleMenu() {
    let m = document.getElementById('menu-content');
    m.style.display = (m.style.display === 'block') ? 'none' : 'block';
}

window.onclick = function(e) {
    if (e.target.classList.contains('modal-overlay')) closeProfile();
};

// تابع کپی لینک با فرمت درخواستی
function copyProfileUrl() {
    const url = '<?= $shareUrl ?>';
    navigator.clipboard.writeText(url).then(() => {
        alert('Profile link copied: ' + url);
    }).catch(() => {
        const input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        alert('Profile link copied!');
    });
}

function triggerUpload() { document.getElementById('photo-input').click(); }

function uploadPhoto(input) {
    if (input.files && input.files[0]) {
        let formData = new FormData();
        formData.append('profile_picture', input.files[0]);
        formData.append('action', 'upload_photo');

        fetch('api/upload_profile_picture.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || "Upload failed");
            }
        })
        .catch(err => alert("Upload error. Check api folder."));
    }
}
</script>
<div id="share-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <span style="font-weight:bold;">Share Profile</span>
            <button onclick="closeModal('share-modal')" style="background:none; border:none; color:#888; font-size:26px; cursor:pointer;">&times;</button>
        </div>
        <div class="modal-body">
            <p style="color:var(--muted); font-size:14px; margin-top:0;">Copy link to share this profile:</p>
            <input type="text" id="share-url" class="share-input" value="<?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8') ?>" readonly>
            <button class="btn btn-primary" style="width:100%; margin-top:15px;" onclick="copyLinkAction()">Copy Link</button>
        </div>
    </div>
</div>
<script>
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
function copyLinkAction() {
    const copyText = document.getElementById('share-url');

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(copyText.value).then(() => {
            alert('Link copied to clipboard!');
            closeModal('share-modal');
        }).catch(() => {
            fallbackCopy(copyText);
        });
    } else {
        fallbackCopy(copyText);
    }
}

function fallbackCopy(input) {
    input.select();
    input.setSelectionRange(0, 99999);

    try {
        document.execCommand('copy');
        alert('Link copied to clipboard!');
        closeModal('share-modal');
    } catch (err) {
        alert('Please copy the link manually.');
    }
}
</script>
=======
<input
    type="file"
    id="photo-input"
    style="display: none;"
    accept="image/*"
    onchange="uploadPhoto(this)"
>

<!-- Share modal -->
<div
    id="share-modal"
    class="modal-overlay"
    role="dialog"
    aria-modal="true"
>
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title-wrap">
                <h2 class="modal-title">Share Profile</h2>
                <div class="modal-subtitle">@<?= h($profile['username']) ?></div>
            </div>
            <button
                type="button"
                class="modal-close"
                onclick="closeModal('share-modal')"
                aria-label="Close"
            >
                &times;
            </button>
        </div>
        <div class="modal-body">
            <p style="color: var(--muted); font-size: 14px; margin-top: 0;">
                Copy link to share this profile:
            </p>
            <input
                type="text"
                id="share-url"
                class="share-input"
                value="<?= h($shareUrl) ?>"
                readonly
            >
            <button
                type="button"
                class="btn btn-primary"
                style="width: 100%; margin-top: 15px;"
                onclick="copyLinkAction()"
            >
                Copy Link
            </button>
        </div>
    </div>
</div>

<script>
    const profileShareUrl = <?= json_encode($shareUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    function updateBodyModalState() {
        const hasOpenModal = document.querySelector('.modal-overlay.is-open');
        document.body.classList.toggle('modal-open', Boolean(hasOpenModal));
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('is-open');
        updateBodyModalState();
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('is-open');
        updateBodyModalState();
    }

    function openProfile() {
        openModal('profile-modal');
    }

    function closeProfile() {
        closeModal('profile-modal');
    }

    function toggleMenu() {
        const menu = document.getElementById('menu-content');
        if (!menu) return;
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }

    function openFollowModal(tabName) {
        switchFollowTab(tabName);
        openModal('follow-modal');
    }

    function switchFollowTab(tabName) {
        const isFollowers = tabName === 'followers';
        const followersTab = document.getElementById('followers-tab');
        const followingTab = document.getElementById('following-tab');
        const followersPanel = document.getElementById('followers-panel');
        const followingPanel = document.getElementById('following-panel');

        if (!followersTab || !followingTab || !followersPanel || !followingPanel) {
            return;
        }

        followersTab.classList.toggle('active', isFollowers);
        followingTab.classList.toggle('active', !isFollowers);
        followersPanel.classList.toggle('active', isFollowers);
        followingPanel.classList.toggle('active', !isFollowers);
    }

    // Close on overlay click
    document.addEventListener('click', function (event) {
        const overlay = event.target.closest('.modal-overlay');
        if (overlay && event.target === overlay) {
            closeModal(overlay.id);
        }
        
        // Hide dropdown menu if clicked outside
        const menu = document.getElementById('menu-content');
        if (menu && menu.style.display === 'block') {
            const dropdownBtn = event.target.closest('[onclick="toggleMenu()"]');
            if (!dropdownBtn && !menu.contains(event.target)) {
                menu.style.display = 'none';
            }
        }
    });

    // Close on ESC keypress
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.modal-overlay.is-open').forEach(function (modal) {
            closeModal(modal.id);
        });
    });

    function triggerUpload() {
        const photoInput = document.getElementById('photo-input');
        if (photoInput) {
            photoInput.click();
        }
    }

    function uploadPhoto(input) {
        if (!input.files || !input.files[0]) return;

        const formData = new FormData();
        formData.append('profile_picture', input.files[0]);
        formData.append('action', 'upload_photo');
        formData.append('csrf_token', <?= json_encode($csrfToken) ?>);

        fetch('api/upload_profile_picture.php', {
            method: 'POST',
            body: formData
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Upload request failed.');
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                window.location.reload();
                return;
            }
            alert(data.message || 'Upload failed.');
        })
        .catch(function () {
            alert('Upload error. Check the API endpoint.');
        })
        .finally(function () {
            input.value = '';
        });
    }


    function saveBio() {
        const bioText = document.getElementById('bio-textarea').value;
    
        fetch('api/update_profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update_bio',
                bio: bioText
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // بررسی وجود المان قبل از تغییر آن
                const bioDisplay = document.getElementById('bio-display');
                if (bioDisplay) {
                    bioDisplay.innerText = bioText || "No bio yet.";
                } else {
                    // اگر المان پیدا نشد، صفحه را ریلود کن تا تغییرات دیتابیس نمایش داده شود
                    location.reload();
                    return;
                }
                toggleBioEdit(false);
                showNotification('Bio updated successfully!', 'success');
            } else {
                alert('Error updating bio: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating bio. Please check browser console for details.');
        });
    }



    function copyTextToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.pointerEvents = 'none';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            textarea.setSelectionRange(0, textarea.value.length);

            try {
                const copied = document.execCommand('copy');
                document.body.removeChild(textarea);
                if (copied) resolve();
                else reject(new Error('Copy failed.'));
            } catch (error) {
                document.body.removeChild(textarea);
                reject(error);
            }
        });
    }

    function copyLinkAction() {
        const copyText = document.getElementById('share-url');
        if (!copyText) return;
        copyTextToClipboard(copyText.value)
            .then(function () {
                alert('Link copied to clipboard!');
                closeModal('share-modal');
            })
            .catch(function () {
                copyText.focus();
                copyText.select();
                alert('Please copy the link manually.');
            });
    }
</script>

>>>>>>> d24f06f (Update / fix)
</body>
</html>
