<?php
// api/follow_user.php

require_once __DIR__ . '/_bootstrap.php';

$user = require_login();
check_csrf();

$usernameToFollow = post_value('username');

if ($usernameToFollow === '') {
    json_response([
        'success' => false,
        'message' => 'Username is required.'
    ], 422);
}

if ($usernameToFollow === $user['username']) {
    json_response([
        'success' => false,
        'message' => 'You cannot follow yourself.'
    ], 422);
}

$stmt = $mysqli->prepare('SELECT id, username FROM users WHERE username = ? LIMIT 1');
if (!$stmt) {
    json_response([
        'success' => false,
        'message' => 'Database error.'
    ], 500);
}

$stmt->bind_param('s', $usernameToFollow);
$stmt->execute();
$result = $stmt->get_result();
$target = $result->fetch_assoc();
$stmt->close();

if (!$target) {
    json_response([
        'success' => false,
        'message' => 'User not found.'
    ], 404);
}

$followerId = (int)$user['id'];
$followingId = (int)$target['id'];

$stmt = $mysqli->prepare('SELECT id FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1');
$stmt->bind_param('ii', $followerId, $followingId);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) {
    $stmt = $mysqli->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
    $stmt->bind_param('ii', $followerId, $followingId);
    $stmt->execute();
    $stmt->close();

    json_response([
        'success' => true,
        'following' => false,
        'message' => 'User unfollowed.'
    ]);
}

$stmt = $mysqli->prepare('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)');
$stmt->bind_param('ii', $followerId, $followingId);
$stmt->execute();
$stmt->close();

json_response([
    'success' => true,
    'following' => true,
    'message' => 'User followed.'
]);
