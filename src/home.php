<?php
session_start();

$isLoggedIn = isset($_SESSION['is_logged']) && $_SESSION['is_logged'] === true;
$username   = $_SESSION['username'] ?? 'Guest';
$nowYear    = date('Y');

function e($s){
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$features = [
    [
        'title' => 'Showcase Garage',
        'desc'  => 'Upload and display your customized GTA Online cars with images, class, colors, wheels, and unique build details.'
    ],
    [
        'title' => 'Explore & Discover',
        'desc'  => 'Browse rare, stylish, and trending cars shared by other players across the Madaratrade community.'
    ],
    [
        'title' => 'Follow System',
        'desc'  => 'Follow other collectors, stay updated with their latest garage activity, and build your own social network.'
    ],
    [
        'title' => 'Trade Requests',
        'desc'  => 'Send and receive trade requests, negotiate directly, and manage car exchange opportunities with other players.'
    ],
    [
        'title' => 'Chat & Social',
        'desc'  => 'Message users, interact with collectors, and create a stronger social identity around your car profile.'
    ],
    [
        'title' => 'Subscription Access',
        'desc'  => 'Unlock premium features, better visibility, more uploads, and advanced tools through subscription-based access.'
    ],
];

$offers = [
    [
        'title' => 'Free Starter',
        'desc'  => 'Create your profile, explore part of the platform, and follow users to begin building your collection identity.',
        'badge' => 'START'
    ],
    [
        'title' => 'Premium Garage',
        'desc'  => 'Get more uploads, stronger profile exposure, featured visibility, and access to premium showcase tools.',
        'badge' => 'POPULAR'
    ],
    [
        'title' => 'Trader Access',
        'desc'  => 'Unlock complete trade-request tools, extended messaging features, and a more professional trading workflow.',
        'badge' => 'PRO'
    ],
];

$stats = [
    [
        'num'   => 'GTA VI',
        'label' => 'Inspired visual identity with neon, luxury, and cinematic atmosphere.'
    ],
    [
        'num'   => 'Social',
        'label' => 'Profiles, following, interaction, and community-driven car identity.'
    ],
    [
        'num'   => 'Trade',
        'label' => 'Specialized tools for car exchange requests and collector communication.'
    ],
];
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Madaratrade | GTA VI Inspired Car Showcase</title>
    <meta name="description" content="Madaratrade is a GTA Online car showcase, social profile, and trade request platform inspired by GTA VI visuals.">

    <style>
        :root{
            --bg:#050811;
            --bg-soft:#09111d;
            --panel:rgba(9,16,28,.58);
            --panel-strong:rgba(10,18,32,.78);
            --line:rgba(255,255,255,.10);
            --line-soft:rgba(255,255,255,.06);
            --text:#f4f8ff;
            --muted:#a6b5cb;
            --cyan:#7cecff;
            --pink:#ff5fcf;
            --purple:#8d7dff;
            --gold:#ffd76a;
            --shadow:0 20px 60px rgba(0,0,0,.38);
            --radius:26px;
            --max:1280px;
        }

        *{box-sizing:border-box}
        html{scroll-behavior:smooth}

        body{
            margin:0;
            font-family:Arial, Helvetica, sans-serif;
            color:var(--text);
            background:
                radial-gradient(circle at 12% 10%, rgba(255,95,207,.16), transparent 20%),
                radial-gradient(circle at 88% 12%, rgba(124,236,255,.14), transparent 18%),
                radial-gradient(circle at 60% 85%, rgba(141,125,255,.12), transparent 20%),
                linear-gradient(180deg, #03050a 0%, #07101a 48%, #04070d 100%);
            min-height:100vh;
            overflow-x:hidden;
            position:relative;
        }

        body::before{
            content:"";
            position:fixed;
            inset:0;
            pointer-events:none;
            opacity:.08;
            background-image:
                linear-gradient(rgba(255,255,255,.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
            background-size: 120px 120px;
            mask-image: radial-gradient(circle at center, black 45%, transparent 100%);
        }

        a{color:inherit;text-decoration:none}
        img{max-width:100%;display:block}
        .container{max-width:var(--max);margin:auto;padding:0 20px}

        .navbar{
            position:sticky;
            top:0;
            z-index:100;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            background:linear-gradient(180deg, rgba(2,6,12,.88), rgba(2,6,12,.60));
            border-bottom:1px solid var(--line-soft);
        }

        .navbar-inner{
            min-height:80px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
        }

        .brand{
            display:flex;
            align-items:center;
            gap:14px;
        }

        .brand-mark{
            width:46px;
            height:46px;
            border-radius:16px;
            background:
                linear-gradient(135deg, rgba(255,95,207,.95), rgba(124,236,255,.95));
            box-shadow:
                0 0 24px rgba(124,236,255,.18),
                0 0 40px rgba(255,95,207,.10);
        }

        .brand-text h1{
            margin:0;
            font-size:20px;
            letter-spacing:.4px;
        }

        .brand-text small{
            display:block;
            margin-top:3px;
            color:var(--muted);
            font-size:12px;
            letter-spacing:.5px;
        }

        .nav-links{
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
        }

        .btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            padding:11px 15px;
            border-radius:14px;
            border:1px solid var(--line);
            background:linear-gradient(180deg, rgba(20,31,48,.70), rgba(10,16,26,.72));
            color:var(--text);
            font-size:14px;
            transition:.25s ease;
            box-shadow:var(--shadow);
        }

        .btn:hover{
            transform:translateY(-2px);
            border-color:rgba(124,236,255,.22);
        }

        .btn-primary{
            background:linear-gradient(135deg, var(--pink), var(--cyan));
            color:#071019;
            font-weight:700;
            border:none;
        }

        .hero{
            position:relative;
            padding:34px 0 22px;
        }

        .hero-grid{
            display:grid;
            grid-template-columns:1.15fr .85fr;
            gap:22px;
        }

        .glass-card{
            position:relative;
            border-radius:var(--radius);
            border:1px solid var(--line-soft);
            background:linear-gradient(180deg, var(--panel), rgba(7,12,20,.60));
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow:var(--shadow);
            overflow:hidden;
        }

        .glass-card::before{
            content:"";
            position:absolute;
            inset:0;
            background:linear-gradient(135deg, rgba(255,255,255,.08), transparent 30%, transparent 70%, rgba(255,255,255,.04));
            pointer-events:none;
        }

        .hero-main{
            min-height:560px;
            padding:34px;
            display:flex;
            flex-direction:column;
            justify-content:flex-end;
            background:
                linear-gradient(180deg, rgba(4,8,14,.18) 0%, rgba(4,8,14,.45) 50%, rgba(4,8,14,.82) 100%),
                radial-gradient(circle at top left, rgba(255,95,207,.15), transparent 25%),
                url('images/gta-vi-images-6.png') center/cover no-repeat;
        }

        .hero-badge{
            display:inline-flex;
            align-self:flex-start;
            padding:8px 13px;
            border-radius:999px;
            border:1px solid rgba(124,236,255,.18);
            background:rgba(124,236,255,.10);
            color:var(--cyan);
            font-size:12px;
            letter-spacing:.9px;
            text-transform:uppercase;
            margin-bottom:14px;
        }

        .hero-main h2{
            margin:0 0 14px;
            font-size:54px;
            line-height:1.02;
            text-transform:uppercase;
            max-width:760px;
        }

        .hero-main p{
            margin:0;
            max-width:760px;
            color:#d2deee;
            font-size:15px;
            line-height:1.95;
        }

        .hero-actions{
            display:flex;
            gap:12px;
            flex-wrap:wrap;
            margin-top:24px;
        }

        .hero-stats{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:12px;
            margin-top:26px;
        }

        .stat-box{
            padding:16px;
            border-radius:18px;
            border:1px solid var(--line-soft);
            background:rgba(255,255,255,.04);
        }

        .stat-box strong{
            display:block;
            font-size:22px;
            margin-bottom:7px;
            color:var(--cyan);
        }

        .stat-box span{
            color:var(--muted);
            font-size:13px;
            line-height:1.7;
        }

        .hero-side{
            padding:22px;
            display:flex;
            flex-direction:column;
            gap:16px;
        }

        .welcome-box,
        .mini-card,
        .image-card{
            border-radius:22px;
            border:1px solid var(--line-soft);
            background:linear-gradient(180deg, rgba(15,24,38,.72), rgba(8,14,24,.72));
            overflow:hidden;
        }

        .welcome-box{
            padding:20px;
        }

        .welcome-box h3{
            margin:0 0 8px;
            font-size:22px;
        }

        .welcome-box p{
            margin:0;
            color:var(--muted);
            line-height:1.85;
            font-size:14px;
        }

        .mini-list{
            display:grid;
            gap:10px;
        }

        .mini-row{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:14px 15px;
            border-radius:16px;
            background:rgba(255,255,255,.03);
            border:1px solid rgba(255,255,255,.05);
        }

        .mini-row strong{
            font-size:14px;
        }

        .mini-row span{
            color:var(--muted);
            font-size:13px;
            text-align:right;
        }

        .image-card{
            min-height:250px;
            background:
                linear-gradient(180deg, rgba(6,10,18,.24), rgba(6,10,18,.56)),
                url('images/gta-vi-images-4.png') center/cover no-repeat;
        }

        .image-card.secondary{
            min-height:250px;
            background:
                linear-gradient(180deg, rgba(6,10,18,.24), rgba(6,10,18,.56)),
                url('images/gta-vi-images-3.png') center/cover no-repeat;
        }

        section{
            padding:18px 0 0;
        }

        .section-head{
            display:flex;
            justify-content:space-between;
            align-items:flex-end;
            gap:12px;
            margin:8px 0 14px;
        }

        .section-head h3{
            margin:0;
            font-size:26px;
        }

        .section-head p{
            margin:0;
            color:var(--muted);
            font-size:14px;
        }

        .grid-3{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:14px;
        }

        .feature-card,
        .offer-card,
        .about-card,
        .cta-banner{
            position:relative;
            border-radius:22px;
            border:1px solid var(--line-soft);
            background:linear-gradient(180deg, var(--panel-strong), rgba(8,14,22,.88));
            box-shadow:var(--shadow);
            overflow:hidden;
        }

        .feature-card,
        .offer-card{
            padding:20px;
        }

        .feature-card h4,
        .offer-card h4{
            margin:0 0 8px;
            font-size:19px;
        }

        .feature-card p,
        .offer-card p,
        .about-card p{
            margin:0;
            color:var(--muted);
            line-height:1.9;
            font-size:14px;
        }

        .feature-tag,
        .offer-tag{
            display:inline-block;
            margin-bottom:12px;
            padding:7px 10px;
            border-radius:999px;
            font-size:12px;
            letter-spacing:.5px;
            border:1px solid rgba(255,255,255,.08);
        }

        .feature-tag{
            color:var(--cyan);
            background:rgba(124,236,255,.09);
        }

        .offer-tag{
            color:var(--pink);
            background:rgba(255,95,207,.10);
        }

        .about-card{
            padding:24px;
        }

        .cta-banner{
            margin-top:16px;
            padding:24px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:18px;
            background:
                linear-gradient(135deg, rgba(255,95,207,.12), rgba(124,236,255,.10)),
                linear-gradient(180deg, rgba(11,18,31,.92), rgba(8,13,20,.92));
        }

        .cta-banner h4{
            margin:0 0 8px;
            font-size:24px;
        }

        .cta-banner p{
            margin:0;
            color:var(--muted);
            line-height:1.8;
            font-size:14px;
            max-width:760px;
        }

        footer{
            padding:30px 0 40px;
        }

        .footer-box{
            border-top:1px solid var(--line-soft);
            padding-top:20px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:14px;
            color:var(--muted);
            font-size:13px;
            flex-wrap:wrap;
        }

        .footer-links{
            display:flex;
            gap:14px;
            flex-wrap:wrap;
        }

        .footer-links a:hover{
            color:var(--text);
        }

        @media (max-width: 1080px){
            .hero-grid,
            .grid-3,
            .hero-stats{
                grid-template-columns:1fr;
            }

            .hero-main{
                min-height:480px;
            }

            .hero-main h2{
                font-size:40px;
            }

            .cta-banner{
                flex-direction:column;
                align-items:flex-start;
            }
        }

        @media (max-width: 760px){
            .navbar-inner{
                flex-direction:column;
                justify-content:center;
                padding:14px 0;
            }

            .nav-links{
                justify-content:center;
            }

            .hero-main{
                padding:24px;
            }

            .hero-main h2{
                font-size:31px;
            }

            .section-head{
                flex-direction:column;
                align-items:flex-start;
            }
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container">
            <div class="navbar-inner">
                <a class="brand" href="/">
                    <div class="brand-mark"></div>
                    <div class="brand-text">
                        <h1>Madaratrade</h1>
                        <small>GTA Online Car Identity Platform</small>
                    </div>
                </a>

                <nav class="nav-links">
                    <a class="btn" href="#about">About</a>
                    <a class="btn" href="#features">Features</a>
                    <a class="btn" href="#offers">Plans</a>
                    <a class="btn" href="explore.php">Explore</a>

                    <?php if($isLoggedIn): ?>
                        <a class="btn" href="profile.php">Profile</a>
                        <a class="btn" href="chat.php">Messages</a>
                        <a class="btn btn-primary" href="logout.php">Logout</a>
                    <?php else: ?>
                        <a class="btn" href="login.php">Login</a>
                        <a class="btn btn-primary" href="register.php">Join Now</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container">
                <div class="hero-grid">

                    <div class="glass-card hero-main">
                        <span class="hero-badge">GTA VI Inspired Visual Experience</span>

                        <h2>Build Your GTA Online Car Identity</h2>

                        <p>
                            Madaratrade is a dedicated platform for GTA Online players who want to showcase custom cars,
                            build social garage profiles, follow other collectors, send trade requests, and create a
                            strong visual identity around their collection.
                        </p>

                        <div class="hero-actions">
                            <a class="btn btn-primary" href="<?php echo $isLoggedIn ? 'explore.php' : 'register.php'; ?>">
                                <?php echo $isLoggedIn ? 'Explore Cars' : 'Create Your Garage'; ?>
                            </a>
                            <a class="btn" href="explore.php">Discover Garages</a>
                            <a class="btn" href="#features">See Features</a>
                        </div>

                        <div class="hero-stats">
                            <?php foreach($stats as $stat): ?>
                                <div class="stat-box">
                                    <strong><?php echo e($stat['num']); ?></strong>
                                    <span><?php echo e($stat['label']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="glass-card hero-side">
                        <div class="welcome-box">
                            <h3>
                                <?php echo $isLoggedIn ? 'Welcome back, ' . e($username) : 'Welcome to Madaratrade'; ?>
                            </h3>
                            <p>
                                A modern collector-focused space for GTA car enthusiasts, designed for visibility,
                                interaction, discovery, and professional trade communication.
                            </p>
                        </div>

                        <div class="mini-card" style="padding:16px;">
                            <div class="mini-list">
                                <div class="mini-row">
                                    <strong>Garage Profiles</strong>
                                    <span>Personal collector identity</span>
                                </div>
                                <div class="mini-row">
                                    <strong>Follow System</strong>
                                    <span>Track users and new builds</span>
                                </div>
                                <div class="mini-row">
                                    <strong>Trade Requests</strong>
                                    <span>Direct exchange workflow</span>
                                </div>
                                <div class="mini-row">
                                    <strong>Subscriptions</strong>
                                    <span>Premium visibility and access</span>
                                </div>
                                <div class="mini-row">
                                    <strong>Social Messaging</strong>
                                    <span>Chat with collectors and traders</span>
                                </div>
                            </div>
                        </div>

                        <div class="image-card"></div>
                        <div class="image-card secondary"></div>
                    </div>

                </div>
            </div>
        </section>

        <section id="about">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h3>What is Madaratrade?</h3>
                        <p>A specialized social and trading platform for GTA Online car collectors.</p>
                    </div>
                </div>

                <div class="about-card">
                    <p>
                        Madaratrade is built for players who want more than a simple gallery. It gives users the ability
                        to publish rare or customized cars, build a recognizable garage identity, gain followers, interact
                        with other collectors, and manage trade-related communication in one focused platform. The visual
                        direction combines neon luxury, dark cinematic gradients, and glassmorphism inspired by GTA VI.
                    </p>
                </div>
            </div>
        </section>

        <section id="features">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h3>Main Features</h3>
                        <p>Everything the visitor should immediately understand on the first page.</p>
                    </div>
                </div>

                <div class="grid-3">
                    <?php foreach($features as $feature): ?>
                        <div class="feature-card">
                            <span class="feature-tag">Feature</span>
                            <h4><?php echo e($feature['title']); ?></h4>
                            <p><?php echo e($feature['desc']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="offers">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h3>Membership Plans</h3>
                        <p>Different access levels for new users, serious collectors, and active traders.</p>
                    </div>
                </div>

                <div class="grid-3">
                    <?php foreach($offers as $offer): ?>
                        <div class="offer-card">
                            <span class="offer-tag"><?php echo e($offer['badge']); ?></span>
                            <h4><?php echo e($offer['title']); ?></h4>
                            <p><?php echo e($offer['desc']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cta-banner">
                    <div>
                        <h4>Start Building Your Digital Garage Identity</h4>
                        <p>
                            Join Madaratrade to showcase your best GTA Online cars, connect with collectors,
                            grow your profile, and unlock access to advanced trade and social features.
                        </p>
                    </div>

                    <a class="btn btn-primary" href="<?php echo $isLoggedIn ? 'profile.php' : 'register.php'; ?>">
                        <?php echo $isLoggedIn ? 'Go to Profile' : 'Join Madaratrade'; ?>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-box">
                <div>
                    © <?php echo e($nowYear); ?> Madaratrade — Built for GTA car collectors and traders.
                </div>

                <div class="footer-links">
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="terms.php">Terms of Service</a>
                    <a href="contact.php">Contact</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
