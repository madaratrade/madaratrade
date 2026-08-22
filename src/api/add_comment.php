<?php
// api/add_comment.php

require_once __DIR__ . '/_bootstrap.php';

$user = require_login();
check_csrf();

$postId = post_value('post_id');
$comment = post_value('comment');

if ($postId === '') {
    json_response([
        'success' => false,
        'message' => 'Post ID is required.'
    ], 422);
}

if ($comment === '') {
    json_response([
        'success' => false,
        'message' => 'Comment cannot be empty.'
    ], 422);
}

if (mb_strlen($comment) > 1000) {
    json_response([
        'success' => false,
        'message' => 'Comment is too long.'
    ], 422);
}

$res = fastapi_request('POST', '/posts/' . rawurlencode($postId) . '/comments', [
    'user_id' => $user['id'],
    'username' => $user['username'],
    'comment' => $comment
]);

json_response($res['data'], $res['status']);

