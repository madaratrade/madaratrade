<?php
// api/update_post.php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$action = $input['action'] ?? '';
// تلاش برای خواندن شناسه از فیلدهای مختلف
$postId = $input['_id'] ?? $input['post_id'] ?? $input['postId'] ?? '';

if (is_array($postId)) {
    // اگر شناسه به صورت آرایه دریافت شده باشد (مثل MongoDB Extended JSON)
    foreach (['$oid', 'oid', 'id', '_id'] as $key) {
        if (isset($postId[$key])) {
            $postId = $postId[$key];
            break;
        }
    }
}

$postId = trim((string)$postId);

if (empty($postId)) {
    echo json_encode(['success' => false, 'error' => 'Missing post ID in request']);
    exit;
}

// بررسی طول و فرمت ObjectId (باید ۲۴ کاراکتر هگزادسیمال باشد)
if (!preg_match('/^[a-f\d]{24}$/i', $postId)) {
    echo json_encode([
        'success' => false,
        'error' => "PHP Validation: The extracted post ID '{$postId}' is not a valid 24-character hexadecimal MongoDB ObjectId."
    ]);
    exit;
}

$fastApiUrl = 'http://fastapi:8000/posts/' . rawurlencode($postId);

if ($action === 'update') {
    $payload = [
        'caption' => $input['caption'] ?? '',
        'tags' => $input['tags'] ?? []
    ];
    $method = 'PUT';
} elseif ($action === 'delete') {
    $payload = [];
    $method = 'DELETE';
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

$context = stream_context_create([
    'http' => [
        'method' => $method,
        'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
        'content' => json_encode($payload),
        'ignore_errors' => true,
        'timeout' => 10
    ]
]);

$response = @file_get_contents($fastApiUrl, false, $context);

if ($response === false) {
    echo json_encode(['success' => false, 'error' => 'Failed to connect to FastAPI']);
    exit;
}

$status = 0;
if (isset($http_response_header[0]) && preg_match('/[0-9]{3}/', $http_response_header[0], $matches)) {
    $status = (int)$matches[0];
}

$decodedResponse = json_decode($response, true);

if ($status >= 200 && $status < 300) {
    echo json_encode(['success' => true, 'data' => $decodedResponse]);
} else {
    $errorMsg = is_array($decodedResponse) && isset($decodedResponse['detail']) 
        ? json_encode($decodedResponse['detail']) 
        : $response;
    echo json_encode([
        'success' => false, 
        'error' => "FastAPI Error ({$status}): {$errorMsg} (Sent ID: {$postId} via URL: {$fastApiUrl})"
    ]);
}

