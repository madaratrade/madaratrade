<?php
// api/explore_more.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/app.php';

$dbLoaded = false;

$dbCandidates = [
    __DIR__ . '/../config/db.php',
    __DIR__ . '/../db.php',
    __DIR__ . '/../database/db.php',
];

foreach ($dbCandidates as $dbFile) {
    if (file_exists($dbFile)) {
        require_once $dbFile;
        $dbLoaded = true;
        break;
    }
}

function json_response($success, $message = '', $extra = [], $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!$dbLoaded) {
    json_response(false, 'Database config file not found.', [], 500);
}

if (!isset($conn) && isset($mysqli)) {
    $conn = $mysqli;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    json_response(false, 'MySQL connection variable $conn not found.', [], 500);
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function get_fastapi_base_url() {
    if (defined('FASTAPI_BASE_URL') && FASTAPI_BASE_URL) {
        return rtrim(FASTAPI_BASE_URL, '/');
    }

    if (!empty($GLOBALS['FASTAPI_BASE_URL'])) {
        return rtrim($GLOBALS['FASTAPI_BASE_URL'], '/');
    }

    return 'http://fastapi:8000';
}

function call_fastapi_get($path, $query = []) {
    $baseUrl = get_fastapi_base_url();
    $query = array_filter($query, function ($value) {
        return $value !== null && $value !== '';
    });

    $url = $baseUrl . $path;
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    if (function_exists('fastapi_request')) {
        $result = fastapi_request('GET', $path . (!empty($query) ? '?' . http_build_query($query) : ''));
        if (is_array($result)) {
            return [
                'ok' => !empty($result['ok']),
                'status' => $result['status'] ?? 200,
                'data' => $result['data'] ?? null,
                'url' => $url,
            ];
        }
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);

    $body = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false) {
        return [
            'ok' => false,
            'status' => 0,
            'data' => ['detail' => $curlError ?: 'FastAPI request failed.'],
            'url' => $url,
        ];
    }

    $decoded = json_decode($body, true);

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => is_array($decoded) ? $decoded : ['raw' => $body],
        'url' => $url,
    ];
}

function get_session_username() {
    $keys = ['username', 'userName', 'user_name', 'name', 'login_username'];

    foreach ($keys as $key) {
        if (!empty($_SESSION[$key])) {
            return trim((string) $_SESSION[$key]);
        }
    }

    return '';
}

function get_session_user_id() {
    $keys = ['user_id', 'id', 'uid', 'UserID', 'userId'];

    foreach ($keys as $key) {
        if (!empty($_SESSION[$key]) && is_numeric($_SESSION[$key])) {
            return (int) $_SESSION[$key];
        }
    }

    return 0;
}

function find_user_by_username(mysqli $conn, $username) {
    if ($username === '') {
        return null;
    }

    $queries = [
        "SELECT id, username FROM users WHERE username = ? LIMIT 1",
        "SELECT user_id AS id, username FROM users WHERE username = ? LIMIT 1",
        "SELECT id, userName AS username FROM users WHERE userName = ? LIMIT 1",
        "SELECT user_id AS id, userName AS username FROM users WHERE userName = ? LIMIT 1",
    ];

    foreach ($queries as $sql) {
        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                continue;
            }

            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($row) {
                return [
                    'id' => (int) $row['id'],
                    'username' => (string) $row['username'],
                ];
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    return null;
}

function find_user_by_id(mysqli $conn, $userId) {
    if ($userId <= 0) {
        return null;
    }

    $queries = [
        "SELECT id, username FROM users WHERE id = ? LIMIT 1",
        "SELECT user_id AS id, username FROM users WHERE user_id = ? LIMIT 1",
        "SELECT id, userName AS username FROM users WHERE id = ? LIMIT 1",
        "SELECT user_id AS id, userName AS username FROM users WHERE user_id = ? LIMIT 1",
    ];

    foreach ($queries as $sql) {
        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                continue;
            }

            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($row) {
                return [
                    'id' => (int) $row['id'],
                    'username' => (string) $row['username'],
                ];
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    return null;
}

function get_current_session_user(mysqli $conn) {
    $userId = get_session_user_id();
    $username = get_session_username();

    if ($userId > 0 && $username !== '') {
        return [
            'id' => $userId,
            'username' => $username,
        ];
    }

    if ($userId > 0) {
        $user = find_user_by_id($conn, $userId);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return $user;
        }
    }

    if ($username !== '') {
        $user = find_user_by_username($conn, $username);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            return $user;
        }
    }

    return [
        'id' => 0,
        'username' => '',
    ];
}

function get_following_usernames(mysqli $conn, $currentUserId) {
    if ($currentUserId <= 0) {
        return [];
    }

    $queries = [
        "SELECT u.username FROM follows f JOIN users u ON u.id = f.following_id WHERE f.follower_id = ?",
        "SELECT following_username AS username FROM follows WHERE follower_id = ?",
        "SELECT following AS username FROM follows WHERE follower_id = ?",
    ];

    foreach ($queries as $sql) {
        try {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                continue;
            }

            $stmt->bind_param('i', $currentUserId);
            $stmt->execute();
            $result = $stmt->get_result();
            $usernames = [];

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    if (!empty($row['username'])) {
                        $usernames[] = (string) $row['username'];
                    }
                }
            }

            $stmt->close();

            if (!empty($usernames)) {
                return array_values(array_unique($usernames));
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    return [];
}

function normalize_posts($data) {
    if (!is_array($data)) {
        return [];
    }

    if (isset($data['posts']) && is_array($data['posts'])) {
        return $data['posts'];
    }

    if (isset($data['data']['posts']) && is_array($data['data']['posts'])) {
        return $data['data']['posts'];
    }

    if (isset($data['items']) && is_array($data['items'])) {
        return $data['items'];
    }

    if (array_is_list($data)) {
        return $data;
    }

    return [];
}

function post_value($post, $keys, $default = '') {
    foreach ($keys as $key) {
        if (isset($post[$key]) && $post[$key] !== '') {
            return $post[$key];
        }
    }

    return $default;
}

function normalize_media_urls($post) {
    $urls = [];

    foreach (['images', 'media', 'media_urls', 'files'] as $key) {
        if (!empty($post[$key]) && is_array($post[$key])) {
            foreach ($post[$key] as $item) {
                if (is_string($item)) {
                    $urls[] = $item;
                } elseif (is_array($item)) {
                    $url = post_value($item, ['url', 'src', 'path', 'media_url', 'image'], '');
                    if ($url !== '') {
                        $urls[] = $url;
                    }
                }
            }
        }
    }

    foreach (['image', 'image_url', 'media_url', 'thumbnail', 'photo'] as $key) {
        if (!empty($post[$key]) && is_string($post[$key])) {
            $urls[] = $post[$key];
        }
    }

    return array_values(array_unique(array_filter($urls)));
}

function normalize_post($post) {
    $id = post_value($post, ['id', '_id', 'post_id', 'postId', 'uuid'], '');
    $username = post_value($post, ['username', 'author', 'userName', 'user_name', 'owner_username'], 'unknown');
    $caption = post_value($post, ['caption', 'content', 'text', 'description', 'body'], '');
    $createdAt = post_value($post, ['created_at', 'createdAt', 'date', 'time'], '');
    $avatar = post_value($post, ['avatar', 'profile_image', 'profileImage', 'user_avatar'], 'assets/default-avatar.png');

    return [
        'id' => (string) $id,
        '_id' => (string) $id,
        'post_id' => (string) $id,
        'username' => (string) $username,
        'caption' => (string) $caption,
        'created_at' => (string) $createdAt,
        'avatar' => (string) $avatar,
        'images' => normalize_media_urls($post),
    ];
}

$currentUser = get_current_session_user($conn);

$feed = strtolower(trim($_GET['feed'] ?? 'explore'));
$offset = max(0, (int) ($_GET['offset'] ?? 0));
$limit = max(1, min((int) ($_GET['limit'] ?? 12), 50));
$search = trim((string) ($_GET['search'] ?? ''));
$searchType = trim((string) ($_GET['search_type'] ?? 'all'));

if (!in_array($feed, ['explore', 'following'], true)) {
    $feed = 'explore';
}

$query = [
    'offset' => $offset,
    'limit' => $limit,
    'viewer_username' => $currentUser['username'],
];

if ($search !== '') {
    $query['search'] = $search;
    $query['search_type'] = $searchType;
    $query['q'] = $search;
}

if ($feed === 'following') {
    if ($currentUser['id'] <= 0) {
        json_response(true, '', [
            'posts' => [],
            'html' => '',
            'count' => 0,
            'has_more' => false,
            'empty_message' => 'Please login to see your Following feed.',
        ]);
    }

    $followingUsernames = get_following_usernames($conn, $currentUser['id']);

    if (empty($followingUsernames)) {
        json_response(true, '', [
            'posts' => [],
            'html' => '',
            'count' => 0,
            'has_more' => false,
            'empty_message' => 'You are not following anyone yet.',
        ]);
    }

    $query['authors'] = implode(',', $followingUsernames);
    $query['usernames'] = implode(',', $followingUsernames);
}

$response = call_fastapi_get('/posts', $query);

if (!$response['ok']) {
    $detail = '';
    if (is_array($response['data'])) {
        $detail = $response['data']['detail'] ?? $response['data']['message'] ?? $response['data']['error'] ?? '';
    }

    json_response(false, $detail ?: 'FastAPI request failed.', [
        'debug' => [
            'status' => $response['status'],
            'url' => $response['url'],
        ],
    ], 502);
}

$posts = normalize_posts($response['data']);
$normalizedPosts = [];

foreach ($posts as $post) {
    $normalizedPosts[] = normalize_post($post);
}

$count = count($normalizedPosts);
$hasMore = $count >= $limit;
$emptyMessage = '';

if ($count === 0) {
    if ($search !== '') {
        $emptyMessage = 'No posts found for your search.';
    } elseif ($feed === 'following') {
        $emptyMessage = 'No posts from people you follow yet.';
    } else {
        $emptyMessage = 'No posts found yet.';
    }
}

json_response(true, '', [
    'posts' => $normalizedPosts,
    'html' => '',
    'count' => $count,
    'has_more' => $hasMore,
    'empty_message' => $emptyMessage,
]);
