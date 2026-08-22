<?php
// config/app.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تنظیمات آدرس‌ها - اگر پورت FastAPI شما متفاوت است (مثلا 8000)، آن را اصلاح کنید
define('FASTAPI_BASE_URL', 'http://fastapi:8000');
define('APP_NAME', 'MadaraTrade');

/**
 * ارسال درخواست به FastAPI و دریافت پاسخ JSON
 */
function fastapi_request(string $method, string $path, array $data = []): array {
    $url = FASTAPI_BASE_URL . $path;
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // جلوگیری از معطل ماندن در صورت قطعی backend

    if ($method === 'POST' || $method === 'PUT') {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['ok' => false, 'error' => $error, 'data' => null];
    }

    $decoded = json_decode($response, true);
    return [
        'ok' => ($httpCode >= 200 && $httpCode < 300),
        'status' => $httpCode,
        'data' => $decoded
    ];
}

