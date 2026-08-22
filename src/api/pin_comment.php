<?php
// api/pin_comment.php

require_once __DIR__ . '/_bootstrap.php';

$user = require_login();
check_csrf();

$postId = post_value('post_id');
$commentId = post_value('comment_id');

if ($postId === '' || $commentId === '') {
    json_response([
        'success' => false,
        'message' => 'Post ID and Comment ID are required.'
    ], 422);
}

$res = fastapi_request('POST', '/posts/' . rawurlencode($postId) . '/comments/' . rawurlencode($commentId) . '/pin', [
    'user_id' => $user['id'],
    'username' => $user['username'],
    'role' => $user['role']
]);

json_response($res['data'], $res['status']);

