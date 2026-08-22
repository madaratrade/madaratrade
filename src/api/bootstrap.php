<?php
// api/_bootstrap.php

session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function require_login(): array {
    $userId = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? null;
    $role = $_SESSION['role'] ?? 'user';

    if (!$userId || !$username) {
        json_response([
            'success' => false,
            'message' => 'Authentication required.'
        ], 401);
    }

    return [
        'id' => (int)$userId,
        'username' => (string)$username,
        'role' => (string)$role
    ];
}

function check_csrf(): void {
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    $token = $headerToken ?: $postToken;

    if (!$sessionToken || !$token || !hash_equals($sessionToken, $token)) {
        json_response([
            'success' => false,
            'message' => 'Invalid CSRF token.'
        ], 403);
    }
}

function post_value(string $key): string {
    return trim((string)($_POST[$key] ?? ''));
}

function fastapi_request(string $method, string $path, array $payload = []): array {
    $url = rtrim(FASTAPI_BASE_URL, '/') . '/' . ltrim($path, '/');

    $headers = [
        'Content-Type: application/json'
    ];

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15
    ];

    if (!empty($payload)) {
        $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    $ch = curl_init();
    curl_setopt_array($ch, $options);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($errno) {
        return [
            'ok' => false,
            'status' => 500,
            'data' => [
                'success' => false,
                'message' => 'FastAPI connection failed: ' . $error
            ]
        ];
    }

    $data = json_decode((string)$body, true);
    if (!is_array($data)) {
        $data = [
            'success' => false,
            'message' => 'Invalid FastAPI response.'
        ];
    }

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status ?: 500,
        'data' => $data
    ];
}
