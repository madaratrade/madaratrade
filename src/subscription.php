<?php
declare(strict_types=1);

session_start();

/**
 * MADARATRADE - SUBSCRIPTION MANAGEMENT
 * Theme: GTA VI Neon Glassmorphism
 */

require_once __DIR__ . '/db.php'; // Ensure this contains your DB connection ($mysqli or $conn)

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
// Assuming $conn is your mysqli connection from config.php
$mysqli = $conn; 

// ─── SUBSCRIPTION PLANS CONFIGURATION ────────────────────────────────────────
const PLANS = [
    '1m'  => ['label' => '1 Month',  'price' => 5.00,  'months' => 1,  'plan_type' => 'monthly', 'desc' => 'Entry level access'],
    '3m'  => ['label' => '3 Months', 'price' => 12.00, 'months' => 3,  'plan_type' => 'monthly', 'desc' => 'Most Popular choice'],
    '6m'  => ['label' => '6 Months', 'price' => 24.00, 'months' => 6,  'plan_type' => 'monthly', 'desc' => 'Extended pro access'],
    '12m' => ['label' => '1 Year',   'price' => 48.00, 'months' => 12, 'plan_type' => 'yearly',  'desc' => 'Ultimate VIP status'],
];

$flash = $_SESSION['subscription_flash'] ?? null;
unset($_SESSION['subscription_flash']);

// ─── POST ACTION: PURCHASE ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'purchase') {
    $planKey = $_POST['plan'] ?? '';
    
    if (!isset(PLANS[$planKey])) {
        $_SESSION['subscription_flash'] = ['type' => 'error', 'message' => 'Invalid subscription plan.'];
        header('Location: subscription.php');
        exit;
    }

    $plan = PLANS[$planKey];
    $price = (float) $plan['price'];
    $months = (int) $plan['months'];
    $planType = $plan['plan_type'];

    $mysqli->begin_transaction();
    try {
        // 1. Check Balance
        $stmt = $mysqli->prepare('SELECT balance FROM users_info WHERE user_id = ? FOR UPDATE');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        $currentBalance = $row ? (float) $row['balance'] : 0.0;

        if ($currentBalance < $price) {
            $mysqli->rollback();
            $_SESSION['subscription_flash'] = ['type' => 'error', 'message' => 'Insufficient balance! Please top up.'];
            header('Location: subscription.php');
            exit;
        }

        // 2. Deduct Balance
        $newBalance = $currentBalance - $price;
        $upd = $mysqli->prepare('UPDATE users_info SET balance = ? WHERE user_id = ?');
        $upd->bind_param('di', $newBalance, $userId);
        $upd->execute();
        $upd->close();

        // 3. Handle Subscription Date Logic
        $subStmt = $mysqli->prepare('SELECT id, payment_expire FROM user_subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1 FOR UPDATE');
        $subStmt->bind_param('i', $userId);
        $subStmt->execute();
        $subRow = $subStmt->get_result()->fetch_assoc();
        $subStmt->close();

        $now = new DateTimeImmutable('now');
        $baseDate = $now;

        // If user has an active subscription, extend from the expiration date
        if ($subRow && !empty($subRow['payment_expire'])) {
            $currentExpire = new DateTimeImmutable($subRow['payment_expire']);
            if ($currentExpire > $now) {
                $baseDate = $currentExpire;
            }
        }

        $newExpireDate = $baseDate->modify('+' . $months . ' months')->format('Y-m-d H:i:s');
        $nowStr = $now->format('Y-m-d H:i:s');

        if ($subRow) {
            $updSub = $mysqli->prepare('UPDATE user_subscriptions SET is_paid = 1, payment_date = ?, payment_expire = ?, plan_type = ? WHERE id = ?');
            $subId = (int)$subRow['id'];
            $updSub->bind_param('sssi', $nowStr, $newExpireDate, $planType, $subId);
            $updSub->execute();
        } else {
            $insSub = $mysqli->prepare('INSERT INTO user_subscriptions (user_id, is_paid, payment_date, payment_expire, plan_type) VALUES (?, 1, ?, ?, ?)');
            $insSub->bind_param('isss', $userId, $nowStr, $newExpireDate, $planType);
            $insSub->execute();
        }

        $mysqli->commit();
        $_SESSION['subscription_flash'] = ['type' => 'success', 'message' => 'Plan activated! Valid until: ' . date('M j, Y', strtotime($newExpireDate))];
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['subscription_flash'] = ['type' => 'error', 'message' => 'Transaction failed. Please try again.'];
    }

    header('Location: subscription.php');
    exit;
}

// ─── DATA FETCHING FOR UI ────────────────────────────────────────────────────
// Fetch Balance
$stmt = $mysqli->prepare('SELECT balance FROM users_info WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$balance = (float)($stmt->get_result()->fetch_assoc()['balance'] ?? 0.0);

// Fetch Current Subscription
$stmt = $mysqli->prepare('SELECT payment_expire FROM user_subscriptions WHERE user_id = ? AND is_paid = 1 AND payment_expire > NOW() ORDER BY id DESC LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$subActive = $stmt->get_result()->fetch_assoc();
$expiryDate = $subActive ? date('M j, Y', strtotime($subActive['payment_expire'])) : 'No Active Plan';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription | MadaraTrade</title>
    <!-- Same fonts and theme as your explore.php -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0a0a0c;
            --glass-bg: rgba(20, 20, 25, 0.7);
            --neon-cyan: #00f3ff;
            --neon-pink: #ff00ff;
            --text-main: #e0e0e0;
            --border-glass: rgba(255, 255, 255, 0.1);
        }

        body {
            margin: 0;
            padding: 0;
            background: var(--bg-color);
            background-image: radial-gradient(circle at 50% -20%, #1e1e2e 0%, #0a0a0c 80%);
            color: var(--text-main);
            font-family: 'Rajdhani', sans-serif;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 4px;
            color: var(--neon-cyan);
            text-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
            margin-bottom: 40px;
        }

        /* Balance & Status Bar */
        .info-bar {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 40px;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--border-glass);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }

        .label { font-size: 14px; text-transform: uppercase; color: #888; letter-spacing: 2px; }
        .value { font-size: 28px; font-weight: 700; margin-top: 5px; color: #fff; }
        .currency { color: var(--neon-pink); font-size: 18px; margin-left: 5px; }

        /* Plans Grid */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .plan-card {
            transition: transform 0.3s ease, border-color 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .plan-card:hover {
            transform: translateY(-5px);
            border-color: var(--neon-cyan);
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.2);
        }

        .plan-price {
            font-size: 36px;
            font-weight: 700;
            color: var(--neon-cyan);
            margin: 15px 0;
        }

        .plan-desc { color: #aaa; margin-bottom: 20px; font-size: 14px; }

        .btn-buy {
            background: transparent;
            border: 1px solid var(--neon-pink);
            color: var(--neon-pink);
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Orbitron', sans-serif;
            text-transform: uppercase;
            font-size: 12px;
            transition: 0.3s;
        }

        .btn-buy:hover {
            background: var(--neon-pink);
            color: #fff;
            box-shadow: 0 0 15px var(--neon-pink);
        }

        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 700;
        }
        .alert-error { background: rgba(255, 0, 0, 0.2); border: 1px solid #ff0000; color: #ffcccc; }
        .alert-success { background: rgba(0, 255, 0, 0.1); border: 1px solid var(--neon-cyan); color: var(--neon-cyan); }

        /* Modal Styles */
        #confirmModal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center; justify-content: center;
        }

        .modal-content {
            width: 90%; max-width: 400px;
            padding: 30px;
            text-align: center;
        }

        .modal-btns { display: flex; gap: 15px; margin-top: 25px; justify-content: center; }
        .btn-yes { background: var(--neon-cyan); color: #000; border: none; padding: 10px 30px; border-radius: 5px; font-weight: 700; cursor: pointer; }
        .btn-no { background: transparent; color: #fff; border: 1px solid #555; padding: 10px 30px; border-radius: 5px; cursor: pointer; }

        @media (max-width: 600px) {
            .info-bar { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Subscription Plans</h1>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- WALLET & STATUS -->
        <div class="info-bar">
            <div class="glass-card">
                <div class="label">Current Balance</div>
                <div class="value">$<?= number_format($balance, 2) ?><span class="currency">USD</span></div>
            </div>
            <div class="glass-card">
                <div class="label">Active Until</div>
                <div class="value" style="color: var(--neon-cyan);"><?= $expiryDate ?></div>
            </div>
        </div>

        <!-- PLANS -->
        <div class="plans-grid">
            <?php foreach (PLANS as $key => $p): ?>
                <div class="glass-card plan-card">
                    <div>
                        <div class="label"><?= $p['label'] ?></div>
                        <div class="plan-price">$<?= number_format($p['price'], 0) ?></div>
                        <div class="plan-desc"><?= $p['desc'] ?></div>
                    </div>
                    <button class="btn-buy" onclick="confirmPurchase('<?= $key ?>', '<?= $p['label'] ?>', <?= $p['price'] ?>)">
                        Purchase Plan
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CONFIRMATION MODAL -->
    <div id="confirmModal">
        <div class="glass-card modal-content">
            <h2 style="font-family: 'Orbitron'; color: var(--neon-pink);">Confirm Purchase</h2>
            <p id="modalText" style="margin: 20px 0; line-height: 1.5;"></p>
            <form id="purchaseForm" method="POST">
                <input type="hidden" name="action" value="purchase">
                <input type="hidden" name="plan" id="planInput">
                <div class="modal-btns">
                    <button type="submit" class="btn-yes">YES, BUY</button>
                    <button type="button" class="btn-no" onclick="closeModal()">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('confirmModal');
        const modalText = document.getElementById('modalText');
        const planInput = document.getElementById('planInput');

        function confirmPurchase(key, label, price) {
            modalText.innerHTML = `Are you sure you want to purchase the <strong>${label}</strong> subscription for <strong>$${price}</strong>?<br><br>Amount will be deducted from your balance.`;
            planInput.value = key;
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Close modal if user clicks outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
