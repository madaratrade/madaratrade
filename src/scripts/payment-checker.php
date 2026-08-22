<?php
session_start();

$key = "ZDdlNjlkMWMxZGQ5MjVhNnVmM2hrSWovTlhNLzZpNGpPMjZGNndYSE55VmVkUjNveVBDWm1HenBrZFNQZy9pUjNSZ3BNOTljWUFUTGxYc2w=";
$refreshRate = 10; // seconds

if (!isset($_SESSION['state'])) {
    $_SESSION['state'] = [
        'startedat' => 0,
        'lastupdate' => 0,
        'md5sum' => '0',
        'md5overlaydata' => '0',
        'last_result' => null,
        'last_error' => null,
        'last_checked' => null,
    ];
}

$state = &$_SESSION['state'];

function fetchReymitData($key, &$state) {
    $url = "https://reymit.ir/overlay/data.php?NWSL=1"
        . "&key=" . urlencode($key)
        . "&md5sum=" . urlencode($state['md5sum'])
        . "&md5overlaydata=" . urlencode($state['md5overlaydata'])
        . "&startedat=" . urlencode($state['startedat'])
        . "&lastupdate=" . urlencode($state['lastupdate']);

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
        return [true, null]; // no change
    }

    $data = json_decode($trimmed, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [false, "Invalid JSON: " . json_last_error_msg()];
    }

    // update state
    if (isset($data['data']['md5'])) {
        $state['md5sum'] = $data['data']['md5'];
    }
    if (empty($state['startedat']) && isset($data['data']['now'])) {
        $state['startedat'] = $data['data']['now'];
    }
    if (isset($data['data']['lastupdate'])) {
        $state['lastupdate'] = $data['data']['lastupdate'];
    }
    if (isset($data['data']['md5overlaydata'])) {
        $state['md5overlaydata'] = $data['data']['md5overlaydata'];
    }

    return [true, $data];
}

list($ok, $result) = fetchReymitData($key, $state);
$state['last_checked'] = time();

if ($ok) {
    $state['last_result'] = $result;
    $state['last_error'] = null;
} else {
    $state['last_error'] = $result;
}

$transactions = [];
if (is_array($state['last_result']) && isset($state['last_result']['transactions']) && is_array($state['last_result']['transactions'])) {
    $transactions = $state['last_result']['transactions'];
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
    <meta http-equiv="refresh" content="<?php echo (int)$refreshRate; ?>">
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
        <h2>Reymit Monitor</h2>
        <div>آخرین بررسی: <?php echo $state['last_checked'] ? date('Y-m-d H:i:s', $state['last_checked']) : '-'; ?></div>
        <div>startedat: <?php echo htmlspecialchars((string)$state['startedat']); ?></div>
        <div>lastupdate: <?php echo htmlspecialchars((string)$state['lastupdate']); ?></div>
        <div>md5sum: <?php echo htmlspecialchars((string)$state['md5sum']); ?></div>
        <div>md5overlaydata: <?php echo htmlspecialchars((string)$state['md5overlaydata']); ?></div>
    </div>

    <div class="box">
        <h3>وضعیت</h3>
        <?php if ($state['last_error']): ?>
            <div class="err"><?php echo htmlspecialchars($state['last_error']); ?></div>
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
            <div class="small">تراکنشی وجود ندارد یا داده جدیدی نرسیده است.</div>
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
