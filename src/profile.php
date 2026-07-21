<?php
session_start();

require_once __DIR__ . '/db.php';

if (!isset($conn) || !$conn) {
    die("Database connection not initialized.");
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fetchPostsFromApi($username) {
    $result = ['posts' => [], 'postsCount' => 0, 'apiError' => false];
    if ($username === '') return $result;

    $apiUrl = 'http://127.0.0.1:8000/posts/by-username/' . urlencode($username);
    $context = stream_context_create([
        'http' => ['method' => 'GET', 'timeout' => 5, 'header' => "Accept: application/json\r\n"]
    ]);

    $response = @file_get_contents($apiUrl, false, $context);
    if ($response === false) {
        $result['apiError'] = true;
        return $result;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        $result['apiError'] = true;
        return $result;
    }

    $result['posts'] = $decoded;
    $result['postsCount'] = count($decoded);
    return $result;
}

$profileUsername = isset($_GET['username']) ? trim($_GET['username']) : '';
$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$currentUsername = isset($_SESSION['username']) ? trim($_SESSION['username']) : '';

if ($profileUsername === '') {
    if ($currentUsername !== '') {
        $profileUsername = $currentUsername;
    } else {
        die('Profile not specified.');
    }
}

$sql = "
    SELECT
        ua.id AS user_id,
        ua.username,
        ua.email,
        ua.first_name,
        ua.last_name,
        ui.profile_picture,
        ui.bio,
        ui.instagram_link,
        COALESCE(ui.balance, 0.00) AS balance
    FROM users_account ua
    LEFT JOIN users_info ui ON ui.user_id = ua.id
    WHERE ua.username = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $profileUsername);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profile) {
    $profile = [
        'user_id' => 0, 'username' => $profileUsername, 'email' => '',
        'profile_picture' => '', 'first_name' => '', 'last_name' => '',
        'bio' => 'No bio available.', 'instagram_link' => '', 'balance' => 0.00
    ];
}

$profile['avatar'] = !empty($profile['profile_picture'])
    ? 'uploads/' . $profile['profile_picture']
    : 'uploads/default-avatar.png';

$profileUserId = (int)$profile['user_id'];
$isOwnProfile = ($currentUserId > 0 && $profileUserId > 0 && $currentUserId === $profileUserId);

$followersCount = 0; $followingCount = 0; $isFollowing = false; $followMessage = '';

if ($profileUserId > 0) {
    $res = $conn->query("SELECT COUNT(*) as total FROM follows WHERE following_id = $profileUserId");
    $followersCount = $res->fetch_assoc()['total'];
    $res = $conn->query("SELECT COUNT(*) as total FROM follows WHERE follower_id = $profileUserId");
    $followingCount = $res->fetch_assoc()['total'];
}

$postData = fetchPostsFromApi($profile['username']);
$posts = $postData['posts'];
$postsCount = $postData['postsCount'];

$displayName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
if ($displayName === '') $displayName = $profile['username'];

// اصلاح ساختار URL برای کپی کردن
$shareUrl = 'https://madaratrade.com/@' . h($profile['username']);
?>
<?php
$shareUrl = 'https://madaratrade.com/@' . urlencode($profile['username']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($profile['username']) ?> - MadaraTrade</title>
    <style>
        :root {
            --bg1: #07111f; --bg2: #050a12; --panel: rgba(14, 23, 40, 0.68);
            --border: rgba(255, 255, 255, 0.10); --text: #f4f7fb; --muted: #9fb2c8;
            --cyan: #00e5ff; --pink: #ff4fd8; --shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        }
        body { margin: 0; color: var(--text); font-family: Arial, sans-serif; background: radial-gradient(circle at top left, rgba(0,229,255,.15), transparent 30%), radial-gradient(circle at top right, rgba(255,79,216,.14), transparent 28%), linear-gradient(180deg, var(--bg1) 0%, var(--bg2) 100%); min-height: 100vh; }
        .container { width: min(1200px, calc(100% - 24px)); margin: 0 auto; padding: 24px 0 60px; }
        .panel { background: var(--panel); border: 1px solid var(--border); border-radius: 24px; backdrop-filter: blur(18px); padding: 24px; }
        .hero { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
        .avatar-wrapper { position: relative; width: 132px; height: 132px; margin: 0 auto 14px; }
        .avatar { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid var(--cyan); }
        .avatar-upload-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; opacity: 0; cursor: pointer; transition: 0.2s; }
        .avatar-wrapper:hover .avatar-upload-overlay { opacity: 1; }
        .btn { border: 1px solid var(--border); background: rgba(255,255,255,.06); color: #fff; padding: 12px 16px; border-radius: 14px; cursor: pointer; font-size: 14px; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: linear-gradient(135deg, var(--cyan), var(--pink)); color: #03111f; font-weight: bold; border: none; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 22px; }
        .stat { background: rgba(255,255,255,.05); border-radius: 18px; padding: 16px; border: 1px solid var(--border); }
        /* رنگ آبی برای اعداد */
        .stat-value { font-size: 20px; font-weight: 800; color: var(--cyan); }
        
        .posts-section { margin-top: 30px; }
        .no-posts { background: rgba(255,255,255,0.03); border: 1px dashed var(--border); border-radius: 20px; padding: 60px; text-align: center; color: var(--muted); }

        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; z-index: 1000; }
        .profile-card { background: #1e2428; width: 400px; border-radius: 15px; color: white; border: 1px solid var(--border); }
        .profile-header { display: flex; justify-content: space-between; padding: 15px; border-bottom: 1px solid #333; align-items: center; }
        .profile-main { text-align: center; padding: 20px; }
        .dropdown-content { display: none; position: absolute; right: 20px; background: #2d353b; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.5); z-index: 10; min-width: 150px; }
        .dropdown-content a { color: white; padding: 12px 20px; display: block; text-decoration: none; font-size: 14px; }
        .dropdown-content a:hover { background: rgba(255,255,255,0.1); }
        .list-item { display: flex; padding: 12px 20px; align-items: center; border-bottom: 1px solid #2d353b; }
        .item-icon { margin-right: 15px; font-size: 18px; }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.85);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
        }
        
        .modal-card {
            background: #151c27;
            width: min(400px, 90%);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            overflow: hidden;
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .share-input {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: 1px solid #333;
            background: #0a0f16;
            color: #fff;
            box-sizing: border-box;
            font-size: 14px;
        }

    </style>
</head>
<body>

<div class="container">
    <div class="topbar" style="display:flex; justify-content:space-between; margin-bottom:20px; align-items: center;">
        <!-- بخش Madara آبی شد -->
        <div style="font-size:24px; font-weight:900;"><span style="color:var(--cyan)">Madara</span>Trade</div>
        <a class="btn" href="home.php">Back</a>
    </div>

    <div class="hero">
        <div class="panel" style="text-align:center;">
            <div class="avatar-wrapper">
                <img class="avatar" src="<?= h($profile['avatar']) ?>" alt="Avatar">
                <?php if ($isOwnProfile): ?>
                    <div class="avatar-upload-overlay" onclick="openProfile()">
                        <span style="color:var(--cyan); font-size:12px; font-weight:bold;">VIEW PROFILE</span>
                    </div>
                <?php endif; ?>
            </div>
            <h2 style="margin:10px 0 5px;"><?= h($profile['username']) ?></h2>
            <p style="color:var(--muted); margin:0;">@<?= h($profile['username']) ?></p>
        </div>

        <div class="panel">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div style="flex: 1; padding-right: 20px;">
                    <h1 style="margin:0; font-size:30px;"><?= h($displayName) ?></h1>
                    <div style="margin-top:10px; color:#dbe6f2; line-height: 1.5;"><?= nl2br(h($profile['bio'])) ?></div>
                </div>
                <div style="display:flex; gap:10px;">
                    <?php if ($isOwnProfile): ?>
                        <a class="btn btn-primary" href="post.php">+ New Post</a>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="action" value="toggle_follow">
                            <button type="submit" class="btn <?= $isFollowing ? '' : 'btn-primary' ?>">
                                <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
                            </button>
                        </form>
                    <?php endif; ?>
		    <button class="btn" onclick="openModal('share-modal')">Share</button>
                </div>
            </div>

            <div class="stats">
                <div class="stat">
                    <div style="color:var(--muted); font-size:13px;">Posts</div>
                    <div class="stat-value"><?= (int)$postsCount ?></div>
                </div>
                <div class="stat">
                    <div style="color:var(--muted); font-size:13px;">Followers</div>
                    <div class="stat-value"><?= (int)$followersCount ?></div>
                </div>
                <div class="stat">
                    <div style="color:var(--muted); font-size:13px;">Following</div>
                    <div class="stat-value"><?= (int)$followingCount ?></div>
                </div>
                <div class="stat">
                    <div style="color:var(--muted); font-size:13px;">Balance</div>
                    <div class="stat-value">$<?= number_format($profile['balance'], 2) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش پست‌ها با چک کردن وضعیت خالی بودن -->
    <div class="posts-section">
        <h3 style="margin-bottom: 20px;">POSTS</h3>
        <?php if ($postsCount > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                <?php foreach ($posts as $post): ?>
                    <div class="panel" style="padding: 10px;">
                        <img src="<?= h($post['images'][0] ?? 'https://via.placeholder.com/300') ?>" style="width: 100%; border-radius: 15px; aspect-ratio: 1/1; object-fit: cover;">
                        <div style="padding: 10px 5px;">
                            <div style="font-weight: bold;"><?= h($post['car_name'] ?? 'Car Post') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-posts">
                <div style="font-size: 40px; margin-bottom: 10px;">📦</div>
                <div>No posts yet.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Profile Modal -->
<div id="profile-modal" class="modal-overlay" style="display:none;">
    <div class="profile-card">
        <div class="profile-header">
            <button onclick="closeProfile()" style="background:none; border:none; color:#888; font-size:24px; cursor:pointer; line-height: 1;">&times;</button>
            <span style="font-weight: bold;">User account</span>
            <div style="position:relative;">
                <button onclick="toggleMenu()" style="background:none; border:none; color:#888; font-size:20px; cursor:pointer;">&#8942;</button>
                <div id="menu-content" class="dropdown-content">
                    <a href="#" onclick="triggerUpload()">Add Photo</a>
                    <a href="update_bio.php">Edit Profile</a>
                    <a href="logout.php" style="color:#ff5e5e;">Logout</a>
                </div>
            </div>
        </div>

        <div class="profile-main">
            <div style="position:relative; width:100px; margin:0 auto;">
                <img id="user-avatar" src="<?= h($profile['avatar']) ?>" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border: 2px solid var(--cyan);">
                <div onclick="triggerUpload()" style="position:absolute; bottom:0; right:0; background:var(--cyan); border-radius:50%; padding:6px; cursor:pointer; line-height: 1; font-size: 14px;">📷</div>
            </div>
            <h3 style="margin: 15px 0 5px;"><?= h($displayName) ?></h3>
            <p style="color:#888; margin: 0;">@<?= h($profile['username']) ?> | <span style="color: var(--cyan);">$<?= number_format($profile['balance'], 2) ?></span></p>
        </div>

        <div class="profile-list">
            <div class="list-item">
                <span class="item-icon">ℹ️</span>
                <div><small style="color:#888; display:block;">About</small><span><?= nl2br(h($profile['bio'])) ?></span></div>
            </div>
            <div class="list-item">
                <span class="item-icon">💰</span>
                <div><small style="color:#888; display:block;">Wallet Balance</small><span style="color: var(--cyan); font-weight: bold;">$<?= number_format($profile['balance'], 2) ?></span></div>
            </div>
        </div>
    </div>
</div>

<input type="file" id="photo-input" style="display:none" accept="image/*" onchange="uploadPhoto(this)">

<script>
function openProfile() { document.getElementById("profile-modal").style.display = "flex"; }
function closeProfile() { document.getElementById("profile-modal").style.display = "none"; }
function toggleMenu() {
    let m = document.getElementById('menu-content');
    m.style.display = (m.style.display === 'block') ? 'none' : 'block';
}

window.onclick = function(e) {
    if (e.target.classList.contains('modal-overlay')) closeProfile();
};

// تابع کپی لینک با فرمت درخواستی
function copyProfileUrl() {
    const url = '<?= $shareUrl ?>';
    navigator.clipboard.writeText(url).then(() => {
        alert('Profile link copied: ' + url);
    }).catch(() => {
        const input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        alert('Profile link copied!');
    });
}

function triggerUpload() { document.getElementById('photo-input').click(); }

function uploadPhoto(input) {
    if (input.files && input.files[0]) {
        let formData = new FormData();
        formData.append('profile_picture', input.files[0]);
        formData.append('action', 'upload_photo');

        fetch('api/upload_profile_picture.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || "Upload failed");
            }
        })
        .catch(err => alert("Upload error. Check api folder."));
    }
}
</script>
<div id="share-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <span style="font-weight:bold;">Share Profile</span>
            <button onclick="closeModal('share-modal')" style="background:none; border:none; color:#888; font-size:26px; cursor:pointer;">&times;</button>
        </div>
        <div class="modal-body">
            <p style="color:var(--muted); font-size:14px; margin-top:0;">Copy link to share this profile:</p>
            <input type="text" id="share-url" class="share-input" value="<?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8') ?>" readonly>
            <button class="btn btn-primary" style="width:100%; margin-top:15px;" onclick="copyLinkAction()">Copy Link</button>
        </div>
    </div>
</div>
<script>
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}
function copyLinkAction() {
    const copyText = document.getElementById('share-url');

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(copyText.value).then(() => {
            alert('Link copied to clipboard!');
            closeModal('share-modal');
        }).catch(() => {
            fallbackCopy(copyText);
        });
    } else {
        fallbackCopy(copyText);
    }
}

function fallbackCopy(input) {
    input.select();
    input.setSelectionRange(0, 99999);

    try {
        document.execCommand('copy');
        alert('Link copied to clipboard!');
        closeModal('share-modal');
    } catch (err) {
        alert('Please copy the link manually.');
    }
}
</script>
</body>
</html>
