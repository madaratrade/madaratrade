<?php
$key = "ZDdlNjlkMWMxZGQ5MjVhNnVmM2hrSWovTlhNLzZpNGpPMjZGNndYSE55VmVkUjNveVBDWm1HenBrZFNQZy9pUjNSZ3BNOTljWUFUTGxYc2w=";

function fetchReymitDataOnce($key) {
    // مقداردهی اولیه صفر برای دریافت کامل آخرین داده‌ها بدون بررسی کش سرور
    $url = "https://reymit.ir/overlay/data.php?NWSL=1"
        . "&key=" . urlencode($key)
        . "&md5sum=0"
        . "&md5overlaydata=0"
        . "&startedat=0"
        . "&lastupdate=0";

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_HTTPHEADER => [
            "Host: reymit.ir",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) Gecko/20100101 Firefox/153.0",
            "Accept: */*",
            "Accept-Language: en-GB,en;q=0.9",
            "Referer: https://reymit.ir/overlay/?key=" . urlencode($key),
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-origin",
            "Te: trailers"
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        return [false, "cURL error: $err"];
    }

    if ($httpCode !== 200) {
        return [false, "HTTP status: $httpCode"];
    }

    $trimmed = trim((string)$response);
    if ($trimmed === '') {
        return [true, null];
    }

    $data = json_decode($trimmed, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [false, "Invalid JSON: " . json_last_error_msg()];
    }

    return [true, $data];
}

// ارسال درخواست
list($ok, $result) = fetchReymitDataOnce($key);
$lastChecked = time();

$transactions = [];
$error = null;
$stateData = [
    'startedat' => 0,
    'lastupdate' => 0,
    'md5sum' => '0',
    'md5overlaydata' => '0'
];

if ($ok) {
    if ($result) {
        $transactions = $result['transactions'] ?? [];
        $stateData['startedat'] = $result['data']['now'] ?? 0;
        $stateData['lastupdate'] = $result['data']['lastupdate'] ?? 0;
        $stateData['md5sum'] = $result['data']['md5'] ?? '0';
        $stateData['md5overlaydata'] = $result['data']['md5overlaydata'] ?? '0';
    }
} else {
    $error = $result;
}

$hit = false;
foreach ($transactions as $tx) {
    $name = $tx['name'] ?? '';
    $amount = $tx['real_amount'] ?? ($tx['amount'] ?? 0);
    $currency = $tx['currency'] ?? '';

    if ($name === 'ammimamad' && (int)$amount === 20000 && $currency === 'Toman') {
        $hit = true;
        break;
    }
}
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reymit Monitor</title>
    <style>
        body { font-family: Arial, sans-serif; direction: rtl; background: #111; color: #eee; margin: 20px; }
        .box { background: #1b1b1b; padding: 16px; border-radius: 12px; margin-bottom: 16px; }
        .ok { color: #5efc8d; }
        .err { color: #ff7b7b; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: right; }
        th { background: #222; }
        .small { color: #aaa; font-size: 13px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Reymit Monitor (تک‌درخواست)</h2>
        <div>زمان بررسی: <?php echo date('Y-m-d H:i:s', $lastChecked); ?></div>
        <div>startedat: <?php echo htmlspecialchars((string)$stateData['startedat']); ?></div>
        <div>lastupdate: <?php echo htmlspecialchars((string)$stateData['lastupdate']); ?></div>
        <div>md5sum: <?php echo htmlspecialchars((string)$stateData['md5sum']); ?></div>
        <div>md5overlaydata: <?php echo htmlspecialchars((string)$stateData['md5overlaydata']); ?></div>
    </div>

    <div class="box">
        <h3>وضعیت</h3>
        <?php if ($error): ?>
            <div class="err"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <div class="ok">داده با موفقیت دریافت شد.</div>
            <?php if ($hit): ?>
                <div class="ok" style="font-size: 18px; margin-top: 8px;">ok - تراکنش مورد نظر پیدا شد</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="box">
        <h3>تراکنش‌ها</h3>
        <?php if (empty($transactions)): ?>
            <div class="small">تراکنشی وجود ندارد.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>نام</th>
                        <th>مبلغ</th>
                        <th>مبلغ واقعی</th>
                        <th>ارز</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tx['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars((string)($tx['amount'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string)($tx['real_amount'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($tx['currency'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
