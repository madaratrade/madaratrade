<?php
// api/save_post.php

require_once __DIR__ . '/_bootstrap.php';

$user = require_login();
check_csrf();

$postId = post_value('post_id');

if ($postId === '') {
    json_response([
        'success' => false,
        'message' => 'Post ID is required.'
    ], 422);
}

$res = fastapi_request('POST', '/posts/' . rawurlencode($postId) . '/save', [
    'user_id' => $user['id'],
    'username' => $user['username']
]);

json_response($res['data'], $res['status']);
