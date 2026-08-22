<?php

session_start();

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizePostId($value): string
{
    if (is_string($value) || is_int($value)) {
        return trim((string)$value);
    }

    if (is_array($value)) {
        foreach (['$oid', 'oid', 'id', '_id'] as $key) {
            if (isset($value[$key])) {
                return normalizePostId($value[$key]);
            }
        }
    }

    return '';
}

function getPostId(array $post): string
{
    foreach (['post_id', '_id', 'post_id', 'postId'] as $key) {
        if (array_key_exists($key, $post)) {
            $postId = normalizePostId($post[$key]);

            if ($postId !== '') {
                return $postId;
            }
        }
    }

    return '';
}

function normalizePostTags($tags): array
{
    if (is_array($tags)) {
        $normalizedTags = [];

        foreach ($tags as $tag) {
            if (is_scalar($tag) && trim((string)$tag) !== '') {
                $normalizedTags[] = trim((string)$tag);
            }
        }

        return array_values(array_unique($normalizedTags));
    }

    if (is_string($tags) && trim($tags) !== '') {
        $decodedTags = urldecode($tags);
        $parts = preg_split('/[\s,\-]+/', trim($decodedTags));

        if (!is_array($parts)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            'trim',
            $parts
        ))));
    }

    return [];
}

function getPostImages(array $post): array
{
    $images = [];

    if (!isset($post['images']) || !is_array($post['images'])) {
        return $images;
    }

    foreach ($post['images'] as $image) {
        $imagePath = '';

        if (is_array($image)) {
            foreach (['web_path', 'url', 'path', 'image_url'] as $key) {
                if (!empty($image[$key]) && is_string($image[$key])) {
                    $imagePath = trim($image[$key]);
                    break;
                }
            }
        } elseif (is_string($image)) {
            $imagePath = trim($image);
        }

        if ($imagePath !== '') {
            $images[] = $imagePath;
        }
    }

    return array_values(array_unique($images));
}

function getPostTitle(array $post): string
{
    foreach (['car_name', 'title', 'name'] as $field) {
        if (!empty($post[$field])) {
            return trim((string)$post[$field]);
        }
    }

    if (!empty($post['caption'])) {
        $caption = preg_replace(
            "/\r\n|\r|\n/",
            ' ',
            trim((string)$post['caption'])
        );

        return mb_strimwidth($caption, 0, 70, '...');
    }

    return 'MadaraTrade Post';
}

function fetchPostsFromApi(string $username): array
{
    $result = [
        'posts' => [],
        'error' => null
    ];

    if ($username === '') {
        $result['error'] = 'Username is required.';
        return $result;
    }

    $apiUrl = 'http://fastapi:8000/posts/by-username/'
        . rawurlencode($username);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 8,
            'ignore_errors' => true,
            'header' =>
                "Accept: application/json\r\n" .
                "User-Agent: MadaraTrade-PHP/1.0\r\n"
        ]
    ]);

    $response = @file_get_contents($apiUrl, false, $context);

    if ($response === false) {
        $result['error'] = 'Unable to connect to posts API.';
        return $result;
    }

    $statusCode = 0;

    if (isset($http_response_header[0])) {
        if (preg_match(
            '/\s(\d{3})\s/',
            $http_response_header[0],
            $matches
        )) {
            $statusCode = (int)$matches[1];
        }
    }

    if ($statusCode >= 400) {
        $result['error'] = 'Posts API returned HTTP ' . $statusCode . '.';
        return $result;
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        $result['error'] = 'Posts API returned invalid JSON.';
        return $result;
    }

    if (isset($decoded['posts']) && is_array($decoded['posts'])) {
        $result['posts'] = $decoded['posts'];
    } else {
        $result['posts'] = $decoded;
    }

    return $result;
}

$username = trim((string)(
    $_GET['userName']
    ?? $_GET['username']
    ?? ''
));

$requestedPostId = normalizePostId($_GET['postId'] ?? '');
$urlTags = normalizePostTags($_GET['postTags'] ?? '');

$profileUrl = 'profile.php?' . http_build_query(
    ['username' => $username],
    '',
    '&',
    PHP_QUERY_RFC3986
);

$errorMessage = null;
$selectedPost = null;

if ($username === '') {
    $errorMessage = 'Username was not provided.';
} elseif ($requestedPostId === '') {
    $errorMessage = 'Post ID was not provided.';
} else {
    $postData = fetchPostsFromApi($username);

    if ($postData['error'] !== null) {
        $errorMessage = $postData['error'];
    } else {
        foreach ($postData['posts'] as $post) {
            if (!is_array($post)) {
                continue;
            }

            $currentPostId = getPostId($post);

            if ($currentPostId !== '' && $currentPostId === $requestedPostId) {
                $selectedPost = $post;
                break;
            }
        }

        if ($selectedPost === null) {
            $errorMessage = 'The requested post was not found.';
        }
    }
}

$postTitle = '';
$postCaption = '';
$postImages = [];
$postTags = $urlTags;

if ($selectedPost !== null) {
    $postTitle = getPostTitle($selectedPost);
    $postCaption = trim((string)($selectedPost['caption'] ?? ''));
    $postImages = getPostImages($selectedPost);

    $apiTags = normalizePostTags($selectedPost['tags'] ?? []);

    if (!empty($apiTags)) {
        $postTags = $apiTags;
    }
}

$currentUrl = '';

if ($username !== '' && $requestedPostId !== '') {
    $query = [
        'userName' => $username,
        'postId' => $requestedPostId
    ];

    if (!empty($postTags)) {
        $query['postTags'] = implode(',', $postTags);
    }

    $currentUrl = 'showpost.php?' . http_build_query(
        $query,
        '',
        '&',
        PHP_QUERY_RFC3986
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= h($postTitle !== '' ? $postTitle : 'Post') ?> - MadaraTrade
    </title>

    <style>
        :root {
            --bg1: #07111f;
            --bg2: #050a12;
            --panel: rgba(14, 23, 40, 0.78);
            --border: rgba(255, 255, 255, 0.10);
            --text: #f4f7fb;
            --muted: #9fb2c8;
            --cyan: #00e5ff;
            --pink: #ff4fd8;
            --danger: #ff6680;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        }

	.post-title {
	    display: none;

	}

        * {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--text);
            font-family: Arial, sans-serif;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(0, 229, 255, 0.15),
                    transparent 30%
                ),
                radial-gradient(
                    circle at top right,
                    rgba(255, 79, 216, 0.14),
                    transparent 28%
                ),
                linear-gradient(
                    180deg,
                    var(--bg1) 0%,
                    var(--bg2) 100%
                );
        }

        button,
        a {
            font-family: inherit;
        }

        .container {
            width: min(1180px, calc(100% - 24px));
            margin: 0 auto;
            padding: 24px 0 60px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .brand {
            color: var(--text);
            font-size: 24px;
            font-weight: 900;
            text-decoration: none;
        }

        .brand span {
            color: var(--cyan);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 11px 16px;
            color: #ffffff;
            font-size: 14px;
            text-decoration: none;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.06);
            cursor: pointer;
            transition:
                transform 0.2s ease,
                border-color 0.2s ease,
                background 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            border-color: rgba(0, 229, 255, 0.45);
            background: rgba(255, 255, 255, 0.10);
        }

        .btn-primary {
            color: #03111f;
            font-weight: 800;
            border: none;
            background:
                linear-gradient(135deg, var(--cyan), var(--pink));
        }

        .post-card {
            display: grid;
            grid-template-columns:
                minmax(0, 1.25fr)
                minmax(320px, 0.75fr);
            min-height: 620px;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 28px;
            background: var(--panel);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }

        .post-media {
            position: relative;
            min-width: 0;
            min-height: 620px;
            overflow: hidden;
            background:
                linear-gradient(
                    135deg,
                    rgba(0, 229, 255, 0.05),
                    rgba(255, 79, 216, 0.05)
                ),
                #03070d;
        }

        .slider-track {
            display: flex;
            width: 100%;
            height: 100%;
            transition: transform 0.3s ease;
        }

        .slide {
            flex: 0 0 100%;
            width: 100%;
            min-width: 100%;
            height: 100%;
        }

        .slide img {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 620px;
            object-fit: contain;
            background: #03070d;
        }

        .empty-image {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 620px;
            padding: 30px;
            color: var(--muted);
            text-align: center;
        }

        .slider-button {
            position: absolute;
            top: 50%;
            z-index: 5;
            width: 46px;
            height: 46px;
            padding: 0;
            color: #ffffff;
            font-size: 28px;
            line-height: 1;
            border: 1px solid rgba(255, 255, 255, 0.20);
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.60);
            cursor: pointer;
            transform: translateY(-50%);
            backdrop-filter: blur(8px);
        }

        .slider-button:hover {
            border-color: var(--cyan);
        }

        .slider-button.previous {
            left: 16px;
        }

        .slider-button.next {
            right: 16px;
        }

        .slider-counter {
            position: absolute;
            bottom: 16px;
            left: 50%;
            z-index: 5;
            padding: 7px 12px;
            color: #ffffff;
            font-size: 12px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.62);
            transform: translateX(-50%);
            backdrop-filter: blur(8px);
        }

        .post-information {
            display: flex;
            flex-direction: column;
            min-width: 0;
            padding: 28px;
        }

        .author {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .author-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            color: #03111f;
            font-size: 18px;
            font-weight: 900;
            border-radius: 50%;
            background:
                linear-gradient(135deg, var(--cyan), var(--pink));
        }

        .author-name {
            color: #ffffff;
            font-weight: 800;
            text-decoration: none;
            word-break: break-word;
        }

        .post-identifier {
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            word-break: break-all;
        }

        .content {
            flex: 1;
            padding: 24px 0;
        }

        .post-title {
            margin: 0 0 18px;
            font-size: clamp(25px, 3vw, 38px);
            line-height: 1.15;
            overflow-wrap: anywhere;
        }

        .post-caption {
            color: #dbe6f2;
            font-size: 15px;
            line-height: 1.75;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .empty-caption {
            color: var(--muted);
        }

        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 24px;
        }

        .tag {
            padding: 7px 11px;
            color: #ffffff;
            font-size: 13px;
            border: 1px solid rgba(0, 229, 255, 0.20);
            border-radius: 999px;
            background: rgba(0, 229, 255, 0.07);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .error-card {
            max-width: 560px;
            margin: 90px auto;
            padding: 38px;
            text-align: center;
            border: 1px solid var(--border);
            border-radius: 24px;
            background: var(--panel);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }

        .error-icon {
            margin-bottom: 14px;
            color: var(--danger);
            font-size: 50px;
        }

        .error-title {
            margin: 0 0 10px;
        }

        .error-message {
            margin: 0 0 24px;
            color: var(--muted);
            line-height: 1.6;
        }

        .toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 50;
            padding: 13px 17px;
            color: #03111f;
            font-size: 14px;
            font-weight: 800;
            border-radius: 14px;
            background:
                linear-gradient(135deg, var(--cyan), var(--pink));
            box-shadow: var(--shadow);
            opacity: 0;
            pointer-events: none;
            transform: translateY(16px);
            transition:
                opacity 0.2s ease,
                transform 0.2s ease;
        }

        .toast.visible {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 900px) {
            .post-card {
                grid-template-columns: 1fr;
            }

            .post-media,
            .slide img,
            .empty-image {
                min-height: min(72vw, 520px);
            }
        }

        @media (max-width: 560px) {
            .container {
                width: min(100% - 16px, 1180px);
                padding-top: 12px;
            }

            .topbar {
                margin-bottom: 12px;
            }

            .brand {
                font-size: 20px;
            }

            .post-card {
                border-radius: 20px;
            }

            .post-information {
                padding: 20px;
            }

            .post-media,
            .slide img,
            .empty-image {
                min-height: calc(100vw - 18px);
            }

            .slider-button {
                width: 40px;
                height: 40px;
            }

            .actions .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <header class="topbar">
        <a class="brand" href="home.php">
            <span>Madara</span>Trade
        </a>

        <a class="btn" href="<?= h($profileUrl) ?>">
            Back to Profile
        </a>
    </header>

    <?php if ($errorMessage !== null): ?>
        <section class="error-card">
            <div class="error-icon">!</div>

            <h1 class="error-title">Post Unavailable</h1>

            <p class="error-message">
                <?= h($errorMessage) ?>
            </p>

            <a class="btn btn-primary" href="<?= h($profileUrl) ?>">
                Back to Profile
            </a>
        </section>
    <?php else: ?>
        <main class="post-card">
            <section class="post-media">
                <?php if (!empty($postImages)): ?>
                    <div class="slider-track" id="sliderTrack">
                        <?php foreach ($postImages as $index => $image): ?>
                            <div class="slide">
                                <img
                                    src="<?= h($image) ?>"
                                    alt="<?= h($postTitle) ?> image <?= $index + 1 ?>"
                                    loading="<?= $index === 0 ? 'eager' : 'lazy' ?>"
                                >
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($postImages) > 1): ?>
                        <button
                            class="slider-button previous"
                            type="button"
                            onclick="changeSlide(-1)"
                            aria-label="Previous image"
                        >
                            &#8249;
                        </button>

                        <button
                            class="slider-button next"
                            type="button"
                            onclick="changeSlide(1)"
                            aria-label="Next image"
                        >
                            &#8250;
                        </button>

                        <div class="slider-counter" id="sliderCounter">
                            1 / <?= count($postImages) ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-image">
                        No image is available for this post.
                    </div>
                <?php endif; ?>
            </section>

            <section class="post-information">
                <div class="author">
                    <div class="author-avatar">
                        <?= h(mb_strtoupper(mb_substr($username, 0, 1))) ?>
                    </div>

                    <div>
                        <a
                            class="author-name"
                            href="<?= h($profileUrl) ?>"
                        >
                            @<?= h($username) ?>
                        </a>

                        <div class="post-identifier">
                            Post ID: <?= h($requestedPostId) ?>
                        </div>
                    </div>
                </div>

                <div class="content">
                    <h1 class="post-title">
                        <?= h($postTitle) ?>
                    </h1>

                    <?php if ($postCaption !== ''): ?>
                        <div class="post-caption"><?= h($postCaption) ?></div>
                    <?php else: ?>
                        <div class="post-caption empty-caption">
                            No caption was added to this post.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($postTags)): ?>
                        <div class="tags">
                            <?php foreach ($postTags as $tag): ?>
                                <span class="tag">
                                    #<?= h(ltrim($tag, '#')) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <button
                        class="btn btn-primary"
                        type="button"
                        onclick="copyPostLink()"
                    >
                        Copy Post Link
                    </button>

                    <a class="btn" href="<?= h($profileUrl) ?>">
                        View Profile
                    </a>
                </div>
            </section>
        </main>
    <?php endif; ?>
</div>

<div class="toast" id="toast">
    Post link copied
</div>

<script>
let currentSlide = 0;

function updateSlider() {
    const track = document.getElementById('sliderTrack');
    const counter = document.getElementById('sliderCounter');

    if (!track) {
        return;
    }

    const slides = track.querySelectorAll('.slide');

    if (!slides.length) {
        return;
    }

    if (currentSlide < 0) {
        currentSlide = slides.length - 1;
    }

    if (currentSlide >= slides.length) {
        currentSlide = 0;
    }

    track.style.transform =
        `translateX(-${currentSlide * 100}%)`;

    if (counter) {
        counter.textContent =
            `${currentSlide + 1} / ${slides.length}`;
    }
}

function changeSlide(direction) {
    currentSlide += direction;
    updateSlider();
}

function showToast(message) {
    const toast = document.getElementById('toast');

    if (!toast) {
        return;
    }

    toast.textContent = message;
    toast.classList.add('visible');

    window.setTimeout(() => {
        toast.classList.remove('visible');
    }, 2200);
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');

    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';

    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    try {
        document.execCommand('copy');
        showToast('Post link copied');
    } catch (error) {
        showToast('Unable to copy the link');
    }

    textarea.remove();
}

function copyPostLink() {
    const relativeUrl = <?= json_encode(
        $currentUrl,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE |
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    ) ?>;

    const fullUrl = new URL(
        relativeUrl,
        window.location.href
    ).href;

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(fullUrl)
            .then(() => {
                showToast('Post link copied');
            })
            .catch(() => {
                fallbackCopy(fullUrl);
            });

        return;
    }

    fallbackCopy(fullUrl);
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'ArrowLeft') {
        changeSlide(-1);
    }

    if (event.key === 'ArrowRight') {
        changeSlide(1);
    }

    if (event.key === 'Escape') {
        window.location.href = <?= json_encode(
            $profileUrl,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE |
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_AMP |
            JSON_HEX_QUOT
        ) ?>;
    }
});
</script>

</body>
</html>
