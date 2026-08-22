<?php
session_start();

function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

// --- توابع منطقی شما از کد اصلی ---
function normalizePostId($value): string {
    if (is_string($value) || is_int($value)) return trim((string)$value);
    if (is_array($value)) {
        foreach (['$oid', 'oid', 'id', '_id'] as $key) {
            if (isset($value[$key])) return normalizePostId($value[$key]);
        }
    }
    return '';
}

function getPostId(array $post): string {
    foreach (['post_id', '_id', 'postId'] as $key) {
        if (array_key_exists($key, $post)) {
            $postId = normalizePostId($post[$key]);
            if ($postId !== '') return $postId;
        }
    }
    return '';
}

// برای عملیات API ما حتما به این آیدی نیاز داریم
function getMongoId(array $post): string {
    if (isset($post['_id'])) {
        $id = is_array($post['_id']) ? ($post['_id']['$oid'] ?? '') : (string)$post['_id'];
        if (preg_match('/^[a-f\d]{24}$/i', $id)) return $id;
    }
    return '';
}

function normalizePostTags($tags): array {
    if (is_array($tags)) return array_values(array_unique(array_filter($tags)));
    if (is_string($tags) && trim($tags) !== '') {
        return array_values(array_unique(array_filter(preg_split('/[\s,\-]+/', trim(urldecode($tags))))));
    }
    return [];
}

function getPostImages(array $post): array {
    $images = [];
    if (!isset($post['images']) || !is_array($post['images'])) return $images;
    foreach ($post['images'] as $image) {
        $path = is_array($image) ? ($image['web_path'] ?? $image['url'] ?? $image['path'] ?? '') : $image;
        if ($path !== '') $images[] = $path;
    }
    return array_values(array_unique($images));
}

function fetchPostsFromApi(string $username): array {
    $apiUrl = 'http://fastapi:8000/posts/by-username/' . rawurlencode($username);
    $resp = @file_get_contents($apiUrl);
    if ($resp === false) return ['posts' => [], 'error' => 'API Connection Failed'];
    $decoded = json_decode($resp, true);
    return ['posts' => $decoded['posts'] ?? $decoded ?? [], 'error' => null];
}

// --- دریافت پارامترها ---
$username = trim((string)($_GET['userName'] ?? $_GET['username'] ?? ''));
$requestedPostId = normalizePostId($_GET['postId'] ?? '');
$profileUrl = 'profile.php?username=' . urlencode($username);

$errorMessage = null;
$selectedPost = null;

if ($username === '' || $requestedPostId === '') {
    $errorMessage = 'Missing parameters.';
} else {
    $postData = fetchPostsFromApi($username);
    if ($postData['error']) {
        $errorMessage = $postData['error'];
    } else {
        foreach ($postData['posts'] as $post) {
            if (getPostId($post) === $requestedPostId) {
                $selectedPost = $post;
                break;
            }
        }
        if (!$selectedPost) $errorMessage = 'Post not found.';
    }
}

// آماده‌سازی برای نمایش
if ($selectedPost) {
    $postCaption = trim((string)($selectedPost['caption'] ?? ''));
    $postImages = getPostImages($selectedPost);
    $postTags = normalizePostTags($selectedPost['tags'] ?? []);
    $actualMongoId = getMongoId($selectedPost);
}

$isOwner = (isset($_SESSION['username']) && strcasecmp($_SESSION['username'], $username) === 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post - MadaraTrade</title>
    <style>
        :root {
            --bg1: #07111f; --bg2: #050a12; --panel: rgba(14, 23, 40, 0.78);
            --border: rgba(255, 255, 255, 0.10); --cyan: #00e5ff; --pink: #ff4fd8;
            --danger: #ff355e; --danger-dark: #8b002a;
            --text: #f4f7fb; --muted: #9fb2c8; --shadow: 0 20px 60px rgba(0,0,0,0.45);
        }
        body {
            margin: 0; background: linear-gradient(180deg, var(--bg1), var(--bg2));
            color: var(--text); font-family: Arial, sans-serif; min-height: 100vh;
        }
        .container { width: min(1180px, calc(100% - 24px)); margin: 0 auto; padding: 24px 0 60px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
        .brand { font-size: 24px; font-weight: 900; text-decoration: none; color: #fff; }
        .brand span { color: var(--cyan); }
        
        .post-card {
            display: grid; grid-template-columns: 1.25fr 0.75fr; min-height: 620px;
            background: var(--panel); border: 1px solid var(--border); border-radius: 28px;
            overflow: hidden; backdrop-filter: blur(18px); box-shadow: var(--shadow);
        }
        
        /* Slider Styles */
        .post-media { position: relative; background: #000; overflow: hidden; display: flex; }
        .slider-track { display: flex; width: 100%; transition: transform 0.3s ease; }
        .slide { flex: 0 0 100%; width: 100%; height: 100%; }
        .slide img { width: 100%; height: 100%; object-fit: contain; min-height: 620px; }
        
        /* دکمه‌های اسلایدر در حالت عادی */
        .slider-btn {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(15, 23, 42, 0.6); color: rgba(255, 255, 255, 0.7); 
            border: 1px solid rgba(255, 255, 255, 0.15);
            width: 46px; height: 46px; border-radius: 50%; cursor: pointer; z-index: 10;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; transition: all 0.25s ease;
            backdrop-filter: blur(5px);
        }
        /* افکت هاور دکمه اسلایدر - تغییر به آبی نئونی */
        .slider-btn:hover {
            color: var(--cyan);
            border-color: var(--cyan);
            background: rgba(0, 229, 255, 0.15);
            box-shadow: 0 0 15px rgba(0, 229, 255, 0.4);
            transform: translateY(-50%) scale(1.08);
        }
        .slider-btn.prev { left: 16px; } .slider-btn.next { right: 16px; }
        .slider-counter {
            position: absolute; bottom: 16px; left: 50%; transform: translateX(-50%);
            background: rgba(0,0,0,0.6); padding: 5px 12px; border-radius: 20px; font-size: 12px;
        }

        .post-info { padding: 30px; display: flex; flex-direction: column; }
        .author { display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--border); padding-bottom: 20px; }
        .avatar { width: 46px; height: 46px; border-radius: 50%; background: linear-gradient(135deg, var(--cyan), var(--pink)); display: flex; align-items: center; justify-content: center; font-weight: 900; color: #000; }
        
        /* Base Buttons */
        .btn {
            padding: 12px 20px; border-radius: 14px; border: 1px solid var(--border);
            color: #fff; text-decoration: none; cursor: pointer; background: rgba(255,255,255,0.06);
            font-weight: bold; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center;
        }
        .btn:hover { background: rgba(255,255,255,0.1); transform: translateY(-2px); border-color: var(--cyan); }
        
        /* Edit Post (پویا و شیشه‌ای - غیرثابت) */
        .btn-edit-post {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--border);
            color: #fff;
        }
        .btn-edit-post:hover {
            border-color: var(--cyan);
            background: rgba(0, 229, 255, 0.1);
            box-shadow: 0 0 15px rgba(0, 229, 255, 0.2);
        }
        
        /* Copy Link (ثابت نئونی) */
        .btn-copy-link {
            background: linear-gradient(135deg, var(--cyan), var(--pink)) !important;
            color: #000 !important;
            border: none !important;
            box-shadow: 0 4px 15px rgba(0, 229, 255, 0.25);
        }
        .btn-copy-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(0, 229, 255, 0.45);
        }
        
        /* Delete Button */
        .btn-delete {
            color: var(--danger) !important;
            border-color: rgba(255, 53, 94, 0.2) !important;
        }
        .btn-delete:hover {
            background: linear-gradient(135deg, var(--danger), var(--danger-dark)) !important;
            color: #fff !important;
            border-color: transparent !important;
            box-shadow: 0 0 15px rgba(255, 53, 94, 0.4);
        }
        
        .tag { display: inline-block; padding: 6px 12px; background: rgba(0,229,255,0.07); border: 1px solid rgba(0,229,255,0.2); border-radius: 20px; font-size: 13px; margin: 4px; }
        
        /* Modal */
        .gta-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.85); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(10px); }
        .modal-content { background: #0f172a; border: 1px solid var(--cyan); padding: 30px; border-radius: 24px; width: 480px; }
        input, textarea { width: 100%; background: #1e293b; border: 1px solid var(--border); color: #fff; padding: 12px; border-radius: 10px; margin-bottom: 15px; font-family: inherit; }
        
        /* Toast Notification Styles */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 25px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 800;
            z-index: 2000;
            box-shadow: var(--shadow);
            opacity: 0;
            transform: translateY(20px) scale(0.95);
            transition: opacity 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: none;
            display: none;
            backdrop-filter: blur(10px);
        }
        
        .toast.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
            display: block;
        }

        /* Neon Cyan/Pink Toast */
        .toast-neon {
            background: linear-gradient(135deg, var(--cyan), var(--pink));
            color: #050a12;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 10px 30px rgba(0, 229, 255, 0.35);
        }

        /* Danger Red Toast */
        .toast-danger {
            background: linear-gradient(135deg, var(--danger), var(--danger-dark));
            color: #fff;
            border: 1px solid rgba(255, 53, 94, 0.4);
            box-shadow: 0 10px 30px rgba(255, 53, 94, 0.35);
        }

        @media (max-width: 900px) { .post-card { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <header class="topbar">
        <a href="home.php" class="brand"><span>Madara</span>Trade</a>
        <a href="<?= h($profileUrl) ?>" class="btn">Back to Profile</a>
    </header>

    <?php if ($errorMessage): ?>
        <div style="text-align:center; padding: 100px;"><h1>!</h1><p><?= h($errorMessage) ?></p></div>
    <?php else: ?>
        <main class="post-card">
            <section class="post-media">
                <?php if(!empty($postImages)): ?>
                    <div class="slider-track" id="sliderTrack">
                        <?php foreach($postImages as $img): ?>
                            <div class="slide"><img src="<?= h($img) ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <?php if(count($postImages) > 1): ?>
                        <button class="slider-btn prev" onclick="moveSlide(-1)">&#10094;</button>
                        <button class="slider-btn next" onclick="moveSlide(1)">&#10095;</button>
                        <div class="slider-counter" id="counter">1 / <?= count($postImages) ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="padding:100px; color:var(--muted)">No Image Available</div>
                <?php endif; ?>
            </section>

            <section class="post-info">
                <div class="author">
                    <div class="avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
                    <div>
                        <div style="font-weight: 800;">@<?= h($username) ?></div>
                        <div style="font-size: 11px; color: var(--muted);">ID: <?= h($requestedPostId) ?></div>
                    </div>
                </div>

                <div style="flex:1; padding: 24px 0;">
                    <p style="white-space: pre-wrap; line-height: 1.7;"><?= h($postCaption) ?></p>
                    <div style="margin-top: 15px;">
                        <?php foreach($postTags as $tag): ?>
                            <span class="tag">#<?= h($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display:flex; flex-wrap: wrap; gap: 10px; border-top: 1px solid var(--border); padding-top: 20px;">
                    <?php if($isOwner): ?>
                        <button class="btn btn-edit-post" onclick="openEditModal()">Edit Post</button>
                        <button class="btn btn-delete" onclick="deletePost()">Delete</button>
                    <?php endif; ?>
                    <button class="btn btn-copy-link" onclick="copyLink()">Copy Link</button>
                    <a href="<?= h($profileUrl) ?>" class="btn">View Profile</a>
                </div>
            </section>
        </main>
    <?php endif; ?>
</div>

<div id="editModal" class="gta-modal">
    <div class="modal-content">
        <h2 style="margin-top:0; color:var(--cyan)">Edit Post</h2>
        <label style="font-size: 12px; color: var(--muted);">Caption:</label>
        <textarea id="editCaption" rows="5"><?= h($postCaption) ?></textarea>
        
        <label style="font-size: 12px; color: var(--muted);">Tags (comma separated):</label>
        <input type="text" id="editTags" value="<?= h(implode(', ', $postTags)) ?>">
        
        <div style="display:flex; gap:10px;">
            <button class="btn btn-primary" id="saveBtn" onclick="saveEdit()" style="flex:1">Save Changes</button>
            <button class="btn" onclick="closeEditModal()" style="flex:1">Cancel</button>
        </div>
    </div>
</div>

<!-- ظرف اصلی پاپ‌آپ -->
<div id="toast" class="toast">Link copied!</div>

<script>
let currentSlide = 0;
const totalSlides = <?= count($postImages) ?>;
const MONGO_ID = "<?= $actualMongoId ?>";

function moveSlide(step) {
    currentSlide = (currentSlide + step + totalSlides) % totalSlides;
    document.getElementById('sliderTrack').style.transform = `translateX(-${currentSlide * 100}%)`;
    document.getElementById('counter').innerText = `${currentSlide + 1} / ${totalSlides}`;
}

function openEditModal() { document.getElementById('editModal').style.display = 'flex'; }
function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

// تابع نمایش توست با افکت و تم رنگی تمیز
function showToast(message, type = 'neon') {
    const toast = document.getElementById('toast');
    if (!toast) return;

    // پاکسازی کلاس‌های قبلی
    toast.className = 'toast';
    
    // انتخاب رنگ تم
    if (type === 'danger') {
        toast.classList.add('toast-danger');
    } else {
        toast.classList.add('toast-neon');
    }

    toast.textContent = message;
    
    // نمایش و انیمیشن
    toast.style.display = 'block';
    // زمان کوتاه برای اعمال درست ترانزیشن مرورگر
    setTimeout(() => {
        toast.classList.add('visible');
    }, 20);

    setTimeout(() => {
        toast.classList.remove('visible');
        setTimeout(() => {
            toast.style.display = 'none';
        }, 300);
    }, 2200);
}

async function saveEdit() {
    const btn = document.getElementById('saveBtn');
    btn.disabled = true; btn.innerText = 'Saving...';
    
    const caption = document.getElementById('editCaption').value;
    const tags = document.getElementById('editTags').value.split(',').map(t => t.trim()).filter(t => t !== "");

    try {
        const res = await fetch('api/update_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', _id: MONGO_ID, caption, tags })
        });
        const data = await res.json();
        if(data.success) {
            showToast('Changes saved successfully!', 'neon');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Error: ' + data.error, 'danger');
        }
    } catch(e) { 
        showToast('Request failed', 'danger'); 
    }
    finally { btn.disabled = false; btn.innerText = 'Save Changes'; }
}

async function deletePost() {
    if(!confirm('Delete this post forever?')) return;
    try {
        const res = await fetch('api/update_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', _id: MONGO_ID })
        });
        const data = await res.json();
        if(data.success) {
            showToast('Post deleted successfully!', 'danger');
            setTimeout(() => {
                window.location.href = "<?= $profileUrl ?>";
            }, 1800);
        } else {
            showToast('Error: ' + data.error, 'danger');
        }
    } catch(e) { 
        showToast('Delete failed', 'danger'); 
    }
}

// کپی لینک با پشتیبانی همه‌جانبه (حتی در محیط‌های HTTP بدون SSL)
function copyLink() {
    const textToCopy = window.location.href;
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(textToCopy).then(() => {
            showToast('Post link copied!', 'neon');
        }).catch(() => {
            fallbackCopy(textToCopy);
        });
    } else {
        fallbackCopy(textToCopy);
    }
}

// روش قدیمی‌تر و امن برای تضمین کپی موفق در لوکال‌هاست یا HTTP
function fallbackCopy(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed"; 
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if(successful) {
            showToast('Post link copied!', 'neon');
        } else {
            showToast('Failed to copy link', 'danger');
        }
    } catch (err) {
        showToast('Failed to copy link', 'danger');
    }
    document.body.removeChild(textArea);
}
</script>
</body>
</html>
