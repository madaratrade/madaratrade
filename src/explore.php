<?php
// explore.php - standalone version for MadaraTrade

session_start();

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php'; // Defines $conn (mysqli object)
require_once __DIR__ . '/config/mongo.php'; // Defines $db (MongoDB\Database object)

$postsCollection = $db->selectCollection('posts');

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Session helpers
function getSessionUserId(): int
{
    $candidates = [
        $_SESSION['user_id'] ?? null,
        $_SESSION['user']['id'] ?? null,
        $_SESSION['auth']['user_id'] ?? null,
    ];

    foreach ($candidates as $value) {
        if ($value !== null && is_numeric($value)) {
            $id = (int)$value;
            if ($id > 0) {
                return $id;
            }
        }
    }

    return 0;
}

function getSessionUsername(): string
{
    $candidates = [
        $_SESSION['username'] ?? null,
        $_SESSION['user']['username'] ?? null,
        $_SESSION['auth']['username'] ?? null,
    ];

    foreach ($candidates as $value) {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }

    return '';
}

$currentUserId = getSessionUserId();
$currentUsername = getSessionUsername();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function isAjaxRequest(): bool
{
    return (isset($_GET['ajax']) && $_GET['ajax'] === '1');
}

function normalizePostId($value): string
{
    if (is_int($value) || is_string($value)) {
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

function getPublicPostId(array $post): string
{
    foreach (['post_id', 'postId', '_id'] as $key) {
        if (!array_key_exists($key, $post)) {
            continue;
        }

        $id = normalizePostId($post[$key]);
        if ($id !== '') {
            return $id;
        }
    }

    return '';
}

function getNumericPostId(array $post): int
{
    foreach (['post_id', 'postId'] as $key) {
        if (!array_key_exists($key, $post)) {
            continue;
        }

        $value = $post[$key];
        if (is_array($value)) {
            foreach (['$numberLong', '$numberInt', 'id'] as $subKey) {
                if (isset($value[$subKey]) && is_numeric($value[$subKey])) {
                    return (int)$value[$subKey];
                }
            }
        }
        if (is_numeric($value)) {
            $id = (int)$value;
            if ($id > 0) {
                return $id;
            }
        }
    }

    return 0;
}

function normalizePostTags($tags): array
{
    if (is_array($tags)) {
        $tags = array_map(static function ($tag) {
            return trim((string)$tag);
        }, $tags);

        return array_values(array_unique(array_filter($tags, static function ($tag) {
            return $tag !== '';
        })));
    }

    if (is_string($tags) && trim($tags) !== '') {
        $parts = preg_split('/[\s,]+/', trim(urldecode($tags)));
        if (!is_array($parts)) {
            return [];
        }

        $parts = array_map(static function ($tag) {
            return trim((string)$tag);
        }, $parts);

        return array_values(array_unique(array_filter($parts, static function ($tag) {
            return $tag !== '';
        })));
    }

    return [];
}

function getPostImages(array $post): array
{
    $images = [];

    if (!isset($post['images']) || !is_array($post['images'])) {
        return $images;
    }

    foreach ($post['images'] as $image) {
        $path = '';
        if (is_array($image)) {
            $path = (string)($image['web_path'] ?? $image['url'] ?? $image['path'] ?? '');
        } elseif (is_string($image)) {
            $path = trim($image);
        }

        if ($path !== '') {
            $images[] = $path;
        }
    }

    return array_values(array_unique($images));
}

function time_ago($datetime): string
{
    try {
        $timestamp = 0;

        if (is_array($datetime)) {
            if (isset($datetime['$date'])) {
                $dateVal = $datetime['$date'];
                if (is_array($dateVal) && isset($dateVal['$numberLong'])) {
                    $timestamp = (int)($dateVal['$numberLong'] / 1000);
                } elseif (is_numeric($dateVal)) {
                    $timestamp = (int)($dateVal / 1000);
                } else {
                    $timestamp = strtotime((string)$dateVal);
                }
            }
        } elseif ($datetime instanceof MongoDB\BSON\UTCDateTime) {
            $timestamp = $datetime->toDateTime()->getTimestamp();
        } elseif ($datetime instanceof DateTimeInterface) {
            $timestamp = $datetime->getTimestamp();
        } elseif (is_numeric($datetime)) {
            $timestamp = (int)$datetime;
        } elseif (is_string($datetime) && trim($datetime) !== '') {
            $timestamp = strtotime($datetime);
        }

        if ($timestamp <= 0) {
            return 'Just now';
        }

        $diff = time() - $timestamp;
        if ($diff < 0) {
            $diff = 0;
        }

        if ($diff < 60) {
            return $diff . 's ago';
        }
        if ($diff < 3600) {
            return (int)floor($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return (int)floor($diff / 3600) . 'h ago';
        }
        if ($diff < 604800) {
            return (int)floor($diff / 86400) . 'd ago';
        }
        if ($diff < 2592000) {
            return (int)floor($diff / 604800) . 'w ago';
        }
        if ($diff < 31536000) {
            return (int)floor($diff / 2592000) . 'mo ago';
        }

        return (int)floor($diff / 31536000) . 'y ago';
    } catch (Throwable $e) {
        return 'Just now';
    }
}

function buildMongoPostQuery(string $search, string $searchType): array
{
    $search = trim($search);
    if ($search === '') {
        return [];
    }

    $regex = new MongoDB\BSON\Regex(preg_quote($search, '/'), 'i');

    if ($searchType === 'username') {
        return ['username' => $regex];
    }

    if ($searchType === 'tag') {
        return ['tags' => $regex];
    }

    return [
        '$or' => [
            ['username' => $regex],
            ['caption' => $regex],
            ['tags' => $regex],
        ],
    ];
}

function fetchPosts(MongoDB\Collection $collection, int $offset, int $limit, string $search, string $searchType): array
{
    $query = buildMongoPostQuery($search, $searchType);

    $cursor = $collection->find($query, [
        'sort' => ['created_at' => -1],
        'skip' => max(0, $offset),
        'limit' => max(1, $limit),
    ]);

    $posts = [];
    foreach ($cursor as $doc) {
        $post = json_decode(json_encode($doc), true);
        if (is_array($post)) {
            $posts[] = $post;
        }
    }

    return $posts;
}

function bindStmtParams(mysqli_stmt $stmt, string $types, array $params): bool
{
    if ($types === '' || $params === []) {
        return true;
    }

    $bind = [$types];
    foreach ($params as $k => $v) {
        $bind[$k + 1] = &$params[$k];
    }

    return call_user_func_array([$stmt, 'bind_param'], $bind);
}

function runPreparedQuery(mysqli $conn, string $sql, string $types = '', array $params = []): ?mysqli_result
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    if ($types !== '' && $params !== []) {
        if (!bindStmtParams($stmt, $types, $params)) {
            $stmt->close();
            return null;
        }
    }

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $stmt->close();

    return $result ?: null;
}

function fetchUserById(mysqli $conn, int $userId): ?array
{
    if ($userId <= 0) {
        return null;
    }

    $sql = 'SELECT id, username FROM users_account WHERE id = ? LIMIT 1';
    $result = runPreparedQuery($conn, $sql, 'i', [$userId]);

    if (!$result) {
        return null;
    }

    $row = $result->fetch_assoc();
    return is_array($row) ? $row : null;
}

function fetchUserByUsername(mysqli $conn, string $username): ?array
{
    $username = trim($username);
    if ($username === '') {
        return null;
    }

    $sql = 'SELECT id, username FROM users_account WHERE username = ? LIMIT 1';
    $result = runPreparedQuery($conn, $sql, 's', [$username]);

    if (!$result) {
        return null;
    }

    $row = $result->fetch_assoc();
    return is_array($row) ? $row : null;
}

// Fetch user data maps for avatars and ids to support standard routing (profile.php?user_id=XXX)
function fetchAuthorsDataMap(mysqli $conn, array $posts): array
{
    $usernames = [];
    foreach ($posts as $post) {
        $author = trim($post['username'] ?? '');
        if ($author !== '') {
            $usernames[] = $author;
        }
    }

    $usernames = array_values(array_unique($usernames));
    $userMap = [];

    if ($usernames === []) {
        return $userMap;
    }

    $placeholders = implode(',', array_fill(0, count($usernames), '?'));
    $types = str_repeat('s', count($usernames));

    $sql = "
        SELECT ua.id, ua.username, ui.profile_picture 
        FROM users_account ua 
        LEFT JOIN users_info ui ON ua.id = ui.user_id 
        WHERE ua.username IN ($placeholders)
    ";

    $result = runPreparedQuery($conn, $sql, $types, $usernames);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $avatar = !empty($row['profile_picture']) ? "uploads/" . $row['profile_picture'] : "uploads/default-avatar.png";
            $userMap[strtolower($row['username'])] = [
                'id' => (int)$row['id'],
                'avatar' => $avatar
            ];
        }
    }

    return $userMap;
}

function fetchFollowedUsernames(mysqli $conn, int $currentUserId): array
{
    if ($currentUserId <= 0) {
        return [];
    }

    $sql = '
        SELECT DISTINCT ua.username
        FROM follows f
        INNER JOIN users_account ua ON ua.id = f.following_id
        WHERE f.follower_id = ?
        ORDER BY ua.username ASC
    ';

    $result = runPreparedQuery($conn, $sql, 'i', [$currentUserId]);
    if (!$result) {
        return [];
    }

    $followed = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['username'])) {
            $followed[] = (string)$row['username'];
        }
    }

    return $followed;
}

function fetchInteractionMaps(mysqli $conn, array $postIds, int $currentUserId): array
{
    $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds), static function ($id) {
        return $id > 0;
    })));

    $likeCounts = [];
    $likedMap = [];
    $savedMap = [];

    if ($postIds === []) {
        return [$likeCounts, $likedMap, $savedMap];
    }

    $placeholders = implode(',', array_fill(0, count($postIds), '?'));
    $types = str_repeat('i', count($postIds));

    // Like counts
    $sql = "SELECT post_id, COUNT(*) AS cnt FROM post_likes WHERE post_id IN ($placeholders) GROUP BY post_id";
    $result = runPreparedQuery($conn, $sql, $types, $postIds);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pid = (int)($row['post_id'] ?? 0);
            if ($pid > 0) {
                $likeCounts[$pid] = (int)($row['cnt'] ?? 0);
            }
        }
    }

    if ($currentUserId > 0) {
        // Liked by current user
        $sql = "SELECT post_id FROM post_likes WHERE user_id = ? AND post_id IN ($placeholders)";
        $params = array_merge([$currentUserId], $postIds);
        $types2 = 'i' . $types;
        $result = runPreparedQuery($conn, $sql, $types2, $params);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pid = (int)($row['post_id'] ?? 0);
                if ($pid > 0) {
                    $likedMap[$pid] = true;
                }
            }
        }

        // Saved by current user
        $sql = "SELECT post_id FROM post_saves WHERE user_id = ? AND post_id IN ($placeholders)";
        $result = runPreparedQuery($conn, $sql, $types2, $params);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pid = (int)($row['post_id'] ?? 0);
                if ($pid > 0) {
                    $savedMap[$pid] = true;
                }
            }
        }
    }

    return [$likeCounts, $likedMap, $savedMap];
}

function handleSelfPost(
    mysqli $conn,
    int $currentUserId,
    string $currentUsername
): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    header('Content-Type: application/json; charset=utf-8');

    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid CSRF token.',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'toggle_follow') {
        if ($currentUserId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Login required to follow.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $username = trim((string)($_POST['username'] ?? ''));
        if ($username === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid username.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $targetUser = fetchUserByUsername($conn, $username);
        if (!$targetUser) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $followingId = (int)$targetUser['id'];
        if ($followingId === $currentUserId) {
            echo json_encode([
                'success' => false,
                'message' => 'You cannot follow yourself.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = 'SELECT id FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1';
        $existing = runPreparedQuery($conn, $sql, 'ii', [$currentUserId, $followingId]);
        $existingRow = $existing ? $existing->fetch_assoc() : null;

        if ($existingRow) {
            $sql = 'DELETE FROM follows WHERE follower_id = ? AND following_id = ?';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error.',
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt->bind_param('ii', $currentUserId, $followingId);
            $stmt->execute();
            $stmt->close();

            echo json_encode([
                'success' => true,
                'followed' => false,
                'message' => 'Unfollowed.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = 'INSERT INTO follows (follower_id, following_id) VALUES (?, ?)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt->bind_param('ii', $currentUserId, $followingId);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => true,
            'followed' => true,
            'message' => 'Followed.',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'toggle_like') {
        if ($currentUserId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Login required to like.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $postId = (int)($_POST['post_id'] ?? 0);
        if ($postId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Post ID is required.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = 'SELECT id FROM post_likes WHERE user_id = ? AND post_id = ? LIMIT 1';
        $existing = runPreparedQuery($conn, $sql, 'ii', [$currentUserId, $postId]);
        $existingRow = $existing ? $existing->fetch_assoc() : null;

        if ($existingRow) {
            $sql = 'DELETE FROM post_likes WHERE user_id = ? AND post_id = ?';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error.',
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt->bind_param('ii', $currentUserId, $postId);
            $stmt->execute();
            $stmt->close();

            $liked = false;
        } else {
            $sql = 'INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error.',
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt->bind_param('ii', $currentUserId, $postId);
            $stmt->execute();
            $stmt->close();

            $liked = true;
        }

        $sql = 'SELECT COUNT(*) AS cnt FROM post_likes WHERE post_id = ?';
        $countResult = runPreparedQuery($conn, $sql, 'i', [$postId]);
        $likesCount = 0;
        if ($countResult) {
            $countRow = $countResult->fetch_assoc();
            $likesCount = (int)($countRow['cnt'] ?? 0);
        }

        echo json_encode([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $likesCount,
            'message' => $liked ? 'Liked.' : 'Unliked.',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'toggle_save') {
        if ($currentUserId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Login required to save.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $postId = (int)($_POST['post_id'] ?? 0);
        if ($postId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Post ID is required.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = 'SELECT id FROM post_saves WHERE user_id = ? AND post_id = ? LIMIT 1';
        $existing = runPreparedQuery($conn, $sql, 'ii', [$currentUserId, $postId]);
        $existingRow = $existing ? $existing->fetch_assoc() : null;

        if ($existingRow) {
            $sql = 'DELETE FROM post_saves WHERE user_id = ? AND post_id = ?';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Database error.',
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt->bind_param('ii', $currentUserId, $postId);
            $stmt->execute();
            $stmt->close();

            echo json_encode([
                'success' => true,
                'saved' => false,
                'message' => 'Removed from saved.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = 'INSERT INTO post_saves (user_id, post_id) VALUES (?, ?)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode([
                'success' => false,
                'message' => 'Database error.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt->bind_param('ii', $currentUserId, $postId);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => true,
            'saved' => true,
            'message' => 'Saved.',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => false,
        'message' => 'Invalid action.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Handle POST actions before any HTML output
handleSelfPost($conn, $currentUserId, $currentUsername);

// If session username is empty but user id exists, try to recover it
if ($currentUserId > 0 && $currentUsername === '') {
    $userRow = fetchUserById($conn, $currentUserId);
    if ($userRow && !empty($userRow['username'])) {
        $currentUsername = (string)$userRow['username'];
    }
}

// Query params
$search = trim((string)($_GET['search'] ?? ''));
$searchType = (string)($_GET['search_type'] ?? 'all');
$searchType = in_array($searchType, ['all', 'username', 'tag'], true) ? $searchType : 'all';

$offset = max(0, (int)($_GET['offset'] ?? 0));
$limit = max(1, min(24, (int)($_GET['limit'] ?? 6)));

// Fetch posts
$posts = fetchPosts($postsCollection, $offset, $limit, $search, $searchType);

// Prepare interaction state for visible posts
$postIds = [];
foreach ($posts as $post) {
    $pid = getNumericPostId($post);
    if ($pid > 0) {
        $postIds[] = $pid;
    }
}

[$likeCounts, $likedMap, $savedMap] = fetchInteractionMaps($conn, $postIds, $currentUserId);
$followedUsernames = fetchFollowedUsernames($conn, $currentUserId);
$authorsDataMap = fetchAuthorsDataMap($conn, $posts);

function renderPostCard(
    array $post,
    string $currentUsername,
    int $currentUserId,
    array $followedUsernames,
    array $likedMap,
    array $savedMap,
    array $likeCounts,
    array $authorsDataMap
): string {
    $author = (string)($post['username'] ?? '');
    $caption = (string)($post['caption'] ?? '');
    $tagsArray = normalizePostTags($post['tags'] ?? []);
    $tagsString = implode('-', $tagsArray);

    $images = getPostImages($post);
    
    // Ensure accurate created_at retrieval from MongoDB document structures
    $createdAt = $post['created_at'] ?? $post['createdAt'] ?? null;

    $publicPostId = getPublicPostId($post);
    $numericPostId = getNumericPostId($post);
    $canInteract = $numericPostId > 0;

    $likes = $canInteract
        ? (int)($likeCounts[$numericPostId] ?? (int)($post['stats']['likes'] ?? 0))
        : (int)($post['stats']['likes'] ?? 0);

    $isLiked = $canInteract && !empty($likedMap[$numericPostId]);
    $isSaved = $canInteract && !empty($savedMap[$numericPostId]);
    $isFollowing = in_array($author, $followedUsernames, true);

    $postUrl = 'showpost.php?userName=' . urlencode($author)
        . '&postId=' . urlencode($publicPostId)
        . '&postTags=' . urlencode($tagsString);

    // Dynamic database avatar & ID-based routing mapping
    $authorLower = strtolower($author);
    $targetUserId = isset($authorsDataMap[$authorLower]['id']) ? (int)$authorsDataMap[$authorLower]['id'] : 0;
    $avatarPath = isset($authorsDataMap[$authorLower]['avatar']) ? (string)$authorsDataMap[$authorLower]['avatar'] : "uploads/default-avatar.png";

    // Fallback: If user ID isn't located, route safely using username
    $profileUrl = $targetUserId > 0 ? 'profile.php?username=' . $author : 'profile.php?username=' . urlencode($author);
    
    $postIdAttr = $canInteract ? (string)$numericPostId : '';

    ob_start();
    ?>
    <article class="post-card glass-card" data-post-url="<?= e($postUrl) ?>" data-post-id="<?= e($postIdAttr) ?>">
        <div class="post-card-header">
            <a href="<?= e($profileUrl) ?>" class="author-link">
                <img src="<?= e($avatarPath) ?>" alt="<?= e($author) ?>'s avatar" class="author-avatar-img" onerror="this.src='uploads/default-avatar.png';">
                <div class="author-meta">
                    <div class="author-name">@<?= e($author) ?></div>
                    <div class="author-submeta">
                        <span><?= e(time_ago($createdAt)) ?></span>
                    </div>
                </div>
            </a>

            <?php if ($currentUserId > 0 && $currentUsername !== $author): ?>
                <button class="ghost-btn follow-btn" data-username="<?= e($author) ?>">
                    <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
                </button>
            <?php endif; ?>
        </div>

        <?php if (!empty($images)): ?>
            <div class="post-media-slider" data-slider>
                <div class="slider-wrapper" style="transform: translateX(0%);">
                    <?php foreach ($images as $index => $img): ?>
                        <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= e($img) ?>" alt="Post media">
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($images) > 1): ?>
                    <button class="slider-nav prev" type="button" onclick="moveSlide(this, -1)">&#10094;</button>
                    <button class="slider-nav next" type="button" onclick="moveSlide(this, 1)">&#10095;</button>
                    <div class="slider-dots">
                        <?php foreach ($images as $index => $img): ?>
                            <span class="dot <?= $index === 0 ? 'active' : '' ?>"></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="post-body">
            <?php if ($caption !== ''): ?>
                <div class="post-caption"><?= nl2br(e($caption)) ?></div>
            <?php endif; ?>

            <?php if (!empty($tagsArray)): ?>
                <div class="tag-list">
                    <?php foreach ($tagsArray as $tag): ?>
                        <a class="tag-chip" href="explore.php?search=<?= urlencode($tag) ?>&search_type=tag">#<?= e($tag) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="post-actions">
                <button
                    class="action-btn like-btn"
                    type="button"
                    data-post-id="<?= e($postIdAttr) ?>"
                    data-liked="<?= $isLiked ? '1' : '0' ?>"
                    <?= $canInteract ? '' : 'disabled' ?>>
                    <span class="action-icon"><?= $isLiked ? '♥' : '♡' ?></span>
                    <span class="action-text">Like</span>
                    <span class="action-count"><?= e((string)$likes) ?></span>
                </button>

                <button
                    class="action-btn save-btn"
                    type="button"
                    data-post-id="<?= e($postIdAttr) ?>"
                    data-saved="<?= $isSaved ? '1' : '0' ?>"
                    <?= $canInteract ? '' : 'disabled' ?>>
                    <span class="action-icon"><?= $isSaved ? '✦' : '⟡' ?></span>
                    <span class="action-text"><?= $isSaved ? 'Saved' : 'Save' ?></span>
                </button>

                <button class="action-btn copy-btn" type="button" data-link="<?= e($postUrl) ?>">
                    <span class="action-icon">⎘</span>
                    <span class="action-text">Copy Link</span>
                </button>
            </div>
        </div>
    </article>
    <?php
    return (string)ob_get_clean();
}

function renderPostsHtml(
    array $posts,
    string $currentUsername,
    int $currentUserId,
    array $followedUsernames,
    array $likedMap,
    array $savedMap,
    array $likeCounts,
    array $authorsDataMap
): string {
    $html = '';
    foreach ($posts as $post) {
        $html .= renderPostCard(
            $post,
            $currentUsername,
            $currentUserId,
            $followedUsernames,
            $likedMap,
            $savedMap,
            $likeCounts,
            $authorsDataMap
        );
    }
    return $html;
}

$postsHtml = renderPostsHtml(
    $posts,
    $currentUsername,
    $currentUserId,
    $followedUsernames,
    $likedMap,
    $savedMap,
    $likeCounts,
    $authorsDataMap
);

$hasMore = count($posts) === $limit;

if (isAjaxRequest()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'html' => $postsHtml,
        'count' => count($posts),
        'has_more' => $hasMore,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$initialOffset = $offset + count($posts);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Explore | MadaraTrade</title>
    <style>
        :root {
            --bg: #070b14;
            --panel: rgba(10, 16, 28, 0.62);
            --panel-2: rgba(14, 22, 40, 0.74);
            --line: rgba(125, 211, 252, 0.18);
            --cyan: #22d3ee;
            --pink: #ff4fd8;
            --text: #eaf2ff;
            --muted: #9fb0d0;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
            --radius: 24px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(34, 211, 238, 0.14), transparent 25%),
                radial-gradient(circle at top right, rgba(255, 79, 216, 0.12), transparent 28%),
                linear-gradient(180deg, #05070d 0%, #090f1c 100%);
            color: var(--text);
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page {
            max-width: 600px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }

        .topbar,
        .glass-card,
        .search-bar {
            background: var(--panel);
            border: 1px solid var(--line);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
            border-radius: var(--radius);
        }

        .topbar {
            padding: 20px;
            margin-bottom: 22px;
            text-align: center;
        }

        .brand {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .brand span {
            color: var(--cyan);
        }

        .nav {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .nav a,
        .btn,
        .ghost-btn,
        .action-btn {
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            border-radius: 16px;
            padding: 10px 14px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .nav a:hover,
        .btn:hover,
        .ghost-btn:hover,
        .action-btn:hover {
            border-color: rgba(34, 211, 238, 0.4);
            background: rgba(255, 255, 255, 0.08);
        }

        .search-bar {
            padding: 14px;
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .search-input,
        .search-select {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 12px;
            outline: none;
        }

        .search-input {
            flex: 1;
            min-width: 140px;
        }

        .search-select {
            cursor: pointer;
        }

        .post-feed-container {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .post-card {
            display: flex;
            flex-direction: column;
            width: 100%;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .post-card:hover {
            border-color: rgba(34, 211, 238, 0.3);
            transform: translateY(-2px);
        }

        .post-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px;
        }

        .author-link {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .author-avatar-img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--cyan);
            flex: 0 0 auto;
        }

        .author-meta {
            min-width: 0;
        }

        .author-name {
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .author-submeta {
            color: var(--muted);
            font-size: 12px;
        }

        .post-media-slider {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            background: #000;
        }

        .slider-wrapper {
            display: flex;
            width: 100%;
            height: 100%;
            transition: transform 0.3s ease-in-out;
        }

        .slide {
            min-width: 100%;
            height: 100%;
            position: relative;
        }

        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 50%;
            z-index: 10;
            transition: all 0.2s ease;
        }

        .slider-nav:hover {
            color: var(--cyan);
            border-color: var(--cyan);
            box-shadow: 0 0 10px rgba(34, 211, 238, 0.6);
            background: rgba(0, 0, 0, 0.8);
        }

        .slider-nav.prev { left: 10px; }
        .slider-nav.next { right: 10px; }

        .slider-dots {
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            z-index: 10;
        }

        .slider-dots .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            transition: background-color 0.2s;
        }

        .slider-dots .dot.active {
            background: var(--cyan);
        }

        .post-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .post-caption {
            line-height: 1.6;
            color: #eef4ff;
            word-break: break-word;
        }

        .tag-list {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .tag-chip {
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(34, 211, 238, 0.10);
            border: 1px solid rgba(34, 211, 238, 0.20);
            color: #bff8ff;
            font-size: 12px;
            transition: background 0.2s, border-color 0.2s;
        }

        .tag-chip:hover {
            background: rgba(34, 211, 238, 0.20);
            border-color: rgba(34, 211, 238, 0.40);
        }

        .post-actions {
            display: flex;
            gap: 10px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .post-actions .action-btn {
            flex: 1;
            min-width: 130px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .action-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .loader,
        .feed-status {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 18px;
            color: var(--muted);
        }

        .spinner {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.15);
            border-top-color: var(--cyan);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .empty {
            padding: 36px 18px;
            text-align: center;
            color: var(--muted);
            border: 1px dashed rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.03);
        }

        .sentinel {
            height: 1px;
        }

        @media (max-width: 560px) {
            .page {
                padding: 18px 12px 32px;
            }

            .topbar,
            .search-bar {
                border-radius: 20px;
            }

            .post-actions .action-btn {
                min-width: 100%;
            }

            .nav a,
            .btn,
            .ghost-btn,
            .action-btn {
                padding: 10px 12px;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="topbar">
        <div class="brand">Madara<span>Trade</span> Explore</div>
        <div class="nav">
            <a href="home.php">Home</a>
            <a href="explore.php">Explore</a>
            <?php if ($currentUserId > 0): ?>
                <a href="profile.php?user_id=<?= (int)$currentUserId ?>">Profile</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="search-bar">
        <input
            type="text"
            class="search-input"
            id="searchInput"
            placeholder="Search by username or tag..."
            value="<?= e($search) ?>">
        <select class="search-select" id="searchType">
            <option value="all" <?= $searchType === 'all' ? 'selected' : '' ?>>All</option>
            <option value="username" <?= $searchType === 'username' ? 'selected' : '' ?>>Username</option>
            <option value="tag" <?= $searchType === 'tag' ? 'selected' : '' ?>>Tag</option>
        </select>
        <button class="btn" id="searchBtn" type="button">Search</button>
    </div>

    <div class="post-feed-container" id="feedContainer">
        <?php if ($postsHtml !== ''): ?>
            <?= $postsHtml ?>
        <?php else: ?>
            <div class="empty">No posts found.</div>
        <?php endif; ?>
    </div>

    <div class="loader" id="loader" style="display:none;">
        <div class="spinner"></div>
    </div>
    <div class="feed-status" id="feedStatus">
        <?= $hasMore ? 'Scroll to load more.' : 'No more posts.' ?>
    </div>
    <div class="sentinel" id="sentinel"></div>
</div>

<script>
const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
let offset = <?= (int)$initialOffset ?>;
const limit = <?= (int)$limit ?>;
let loading = false;
let hasMore = <?= $hasMore ? 'true' : 'false' ?>;

function applySearch() {
    const search = document.getElementById('searchInput').value.trim();
    const searchType = document.getElementById('searchType').value;

    const url = new URL(window.location.href);
    url.searchParams.set('search', search);
    url.searchParams.set('search_type', searchType);
    url.searchParams.set('offset', '0');
    url.searchParams.delete('ajax');

    window.location.href = url.toString();
}

document.getElementById('searchBtn').addEventListener('click', applySearch);
document.getElementById('searchInput').addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        applySearch();
    }
});

function moveSlide(btn, direction) {
    const slider = btn.closest('[data-slider]');
    if (!slider) return;

    const wrapper = slider.querySelector('.slider-wrapper');
    const slides = slider.querySelectorAll('.slide');
    const dots = slider.querySelectorAll('.dot');

    if (!wrapper || slides.length === 0) return;

    let activeIndex = Array.from(slides).findIndex(slide => slide.classList.contains('active'));
    if (activeIndex < 0) activeIndex = 0;

    slides[activeIndex].classList.remove('active');
    if (dots[activeIndex]) dots[activeIndex].classList.remove('active');

    activeIndex = (activeIndex + direction + slides.length) % slides.length;

    slides[activeIndex].classList.add('active');
    if (dots[activeIndex]) dots[activeIndex].classList.add('active');

    wrapper.style.transform = `translateX(-${activeIndex * 100}%)`;
}

// Fallback copy function
function fallbackCopyText(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.opacity = "0";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
        document.body.removeChild(textArea);
        return true;
    } catch (err) {
        document.body.removeChild(textArea);
        return false;
    }
}

document.addEventListener('click', async (event) => {
    // 1. Check if user clicked an interactive element first
    const interactiveElement = event.target.closest('button, a, input, select');
    
    // If not clicking an interactive element, check if card body is clicked
    if (!interactiveElement) {
        const clickablePost = event.target.closest('.post-card');
        if (clickablePost) {
            const postUrl = clickablePost.dataset.postUrl;
            if (postUrl) {
                window.location.href = postUrl;
                return;
            }
        }
    }

    const followBtn = event.target.closest('.follow-btn');
    if (followBtn) {
        event.preventDefault();
        const username = followBtn.dataset.username || '';
        try {
            const res = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'toggle_follow',
                    username,
                    csrf_token: csrfToken
                })
            });

            const data = await res.json();
            if (data.success) {
                followBtn.textContent = data.followed ? 'Unfollow' : 'Follow';
            } else {
                alert(data.message || 'Action failed.');
            }
        } catch (err) {
            alert('Network error.');
        }
        return;
    }

    const likeBtn = event.target.closest('.like-btn');
    if (likeBtn) {
        event.preventDefault();
        const postId = likeBtn.dataset.postId || '';
        if (!postId) return;

        try {
            const res = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'toggle_like',
                    post_id: postId,
                    csrf_token: csrfToken
                })
            });

            const data = await res.json();
            if (data.success) {
                const icon = likeBtn.querySelector('.action-icon');
                const count = likeBtn.querySelector('.action-count');

                if (icon) icon.textContent = data.liked ? '♥' : '♡';
                if (count) count.textContent = data.likes_count;

                likeBtn.dataset.liked = data.liked ? '1' : '0';
            } else {
                alert(data.message || 'Action failed.');
            }
        } catch (err) {
            alert('Network error.');
        }
        return;
    }

    const saveBtn = event.target.closest('.save-btn');
    if (saveBtn) {
        event.preventDefault();
        const postId = saveBtn.dataset.postId || '';
        if (!postId) return;

        try {
            const res = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'toggle_save',
                    post_id: postId,
                    csrf_token: csrfToken
                })
            });

            const data = await res.json();
            if (data.success) {
                const icon = saveBtn.querySelector('.action-icon');
                const text = saveBtn.querySelector('.action-text');

                if (icon) icon.textContent = data.saved ? '✦' : '⟡';
                if (text) text.textContent = data.saved ? 'Saved' : 'Save';

                saveBtn.dataset.saved = data.saved ? '1' : '0';
            } else {
                alert(data.message || 'Action failed.');
            }
        } catch (err) {
            alert('Network error.');
        }
        return;
    }

    const copyBtn = event.target.closest('.copy-btn');
    if (copyBtn) {
        event.preventDefault();
        const relativeLink = copyBtn.dataset.link || '';
        if (!relativeLink) return;

        const absoluteUrl = new URL(relativeLink, window.location.href).toString();
        const textSpan = copyBtn.querySelector('.action-text');

        const proceedWithVisualFeedback = () => {
            if (!textSpan) return;
            const originalText = textSpan.textContent;
            textSpan.textContent = 'Copied!';
            setTimeout(() => {
                textSpan.textContent = originalText;
            }, 1500);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(absoluteUrl).then(() => {
                proceedWithVisualFeedback();
            }).catch(() => {
                if (fallbackCopyText(absoluteUrl)) {
                    proceedWithVisualFeedback();
                } else {
                    alert('Failed to copy link.');
                }
            });
        } else {
            if (fallbackCopyText(absoluteUrl)) {
                proceedWithVisualFeedback();
            } else {
                alert('Failed to copy link.');
            }
        }
    }
});

const sentinel = document.getElementById('sentinel');
if (sentinel) {
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && hasMore && !loading) {
            loadMorePosts();
        }
    }, { threshold: 0.1 });
    observer.observe(sentinel);
}

async function loadMorePosts() {
    loading = true;
    document.getElementById('loader').style.display = 'flex';

    const search = document.getElementById('searchInput').value.trim();
    const searchType = document.getElementById('searchType').value;

    const url = new URL(window.location.href);
    url.searchParams.set('ajax', '1');
    url.searchParams.set('offset', String(offset));
    url.searchParams.set('limit', String(limit));
    url.searchParams.set('search', search);
    url.searchParams.set('search_type', searchType);

    try {
        const response = await fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const data = await response.json();

        if (data.success && data.html && data.html.trim() !== '') {
            const container = document.getElementById('feedContainer');
            container.insertAdjacentHTML('beforeend', data.html);

            offset += Number(data.count || 0);
            hasMore = !!data.has_more;
            document.getElementById('feedStatus').textContent = hasMore ? 'Scroll to load more.' : 'No more posts.';
        } else {
            hasMore = false;
            document.getElementById('feedStatus').textContent = 'No more posts.';
        }
    } catch (err) {
        console.error(err);
        document.getElementById('feedStatus').textContent = 'Failed to load more posts.';
    } finally {
        loading = false;
        document.getElementById('loader').style.display = 'none';
    }
}
</script>
</body>
</html>
