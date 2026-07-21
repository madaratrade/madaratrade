<?php
session_start();

$isLoggedIn = isset($_SESSION['is_logged']) && $_SESSION['is_logged'] === true;
$username   = $_SESSION['username'] ?? 'Guest';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$contacts = [
    [
        'label' => 'Business Instagram',
        'value' => '@madara.tradeog',
        'href'  => 'https://instagram.com/madara.tradeog',
        'note'  => 'Official business page'
    ],
    [
        'label' => 'Personal Instagram',
        'value' => '@ammighorbani',
        'href'  => 'https://instagram.com/ammighorbani',
        'note'  => 'Personal creator account'
    ],
    [
        'label' => 'Business Telegram',
        'value' => '@madara.tradeog',
        'href'  => 'https://t.me/madara.tradeog',
        'note'  => 'Business messaging channel'
    ],
    [
        'label' => 'Personal Telegram',
        'value' => '@ammighorbani',
        'href'  => 'https://t.me/ammighorbani',
        'note'  => 'Direct personal contact'
    ],
    [
        'label' => 'Business Email',
        'value' => 'info@madaratrade.com',
        'href'  => 'mailto:info@madaratrade.com',
        'note'  => 'For official inquiries'
    ],
    [
        'label' => 'Website Profile / Account',
        'value' => '@ammighorbani',
        'href'  => 'profile.php',
        'note'  => 'In-website profile identity'
    ],
];

?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us | Madaratrade</title>
    <meta name="description" content="Contact Madaratrade through business and personal channels.">

    <style>
        :root{
            --bg:#050811;
            --panel:rgba(9,16,28,.62);
            --panel-strong:rgba(10,18,32,.80);
            --line:rgba(255,255,255,.10);
            --line-soft:rgba(255,255,255,.06);
            --text:#f4f8ff;
            --muted:#a6b5cb;
            --cyan:#7cecff;
            --pink:#ff5fcf;
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
            background-size:120px 120px;
            mask-image: radial-gradient(circle at center, black 45%, transparent 100%);
        }

        a{color:inherit;text-decoration:none}
        .container{max-width:var(--max);margin:auto;padding:0 20px}

        .navbar{
            position:sticky;
            top:0;
            z-index:100;
            backdrop-filter:blur(16px);
            -webkit-backdrop-filter:blur(16px);
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
            background:linear-gradient(135deg, rgba(255,95,207,.95), rgba(124,236,255,.95));
            box-shadow:0 0 24px rgba(124,236,255,.18), 0 0 40px rgba(255,95,207,.10);
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
            padding:34px 0 22px;
        }

        .hero-card{
            position:relative;
            border-radius:var(--radius);
            border:1px solid var(--line-soft);
            background:
                linear-gradient(180deg, rgba(9,16,28,.58), rgba(7,12,20,.78)),
                radial-gradient(circle at top left, rgba(255,95,207,.12), transparent 28%),
                radial-gradient(circle at bottom right, rgba(124,236,255,.10), transparent 28%);
            backdrop-filter:blur(14px);
            -webkit-backdrop-filter:blur(14px);
            box-shadow:var(--shadow);
            overflow:hidden;
            padding:34px;
        }

        .hero-badge{
            display:inline-flex;
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

        .hero-card h2{
            margin:0 0 12px;
            font-size:46px;
            line-height:1.05;
            text-transform:uppercase;
            max-width:760px;
        }

        .hero-card p{
            margin:0;
            max-width:820px;
            color:#d2deee;
            font-size:15px;
            line-height:1.95;
        }

        .contact-grid{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:14px;
            margin:22px 0 0;
        }

        .contact-card{
            position:relative;
            border-radius:22px;
            border:1px solid var(--line-soft);
            background:linear-gradient(180deg, var(--panel-strong), rgba(8,14,22,.88));
            box-shadow:var(--shadow);
            padding:20px;
            overflow:hidden;
        }

        .contact-card::before{
            content:"";
            position:absolute;
            inset:0;
            background:linear-gradient(135deg, rgba(255,255,255,.08), transparent 35%, transparent 70%, rgba(255,255,255,.04));
            pointer-events:none;
        }

        .contact-tag{
            display:inline-block;
            margin-bottom:12px;
            padding:7px 10px;
            border-radius:999px;
            font-size:12px;
            letter-spacing:.5px;
            color:var(--pink);
            background:rgba(255,95,207,.10);
            border:1px solid rgba(255,255,255,.08);
        }

        .contact-card h3{
            margin:0 0 8px;
            font-size:19px;
        }

        .contact-card p{
            margin:0;
            color:var(--muted);
            font-size:14px;
            line-height:1.8;
        }

        .contact-link{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            margin-top:16px;
            padding:14px 15px;
            border-radius:16px;
            background:rgba(255,255,255,.03);
            border:1px solid rgba(255,255,255,.05);
        }

        .contact-link strong{
            font-size:14px;
        }

        .contact-link span{
            color:var(--cyan);
            font-size:13px;
            word-break:break-word;
            text-align:right;
        }

        .section{
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

        .info-box{
            border-radius:22px;
            border:1px solid var(--line-soft);
            background:linear-gradient(180deg, rgba(9,16,28,.62), rgba(8,14,22,.86));
            box-shadow:var(--shadow);
            padding:24px;
        }

        .info-box p{
            margin:0;
            color:var(--muted);
            line-height:1.9;
            font-size:14px;
        }

        .actions{
            display:flex;
            gap:12px;
            flex-wrap:wrap;
            margin-top:18px;
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
            .contact-grid{
                grid-template-columns:1fr;
            }

            .hero-card h2{
                font-size:38px;
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

            .hero{
                padding-top:22px;
            }

            .hero-card{
                padding:24px;
            }

            .hero-card h2{
                font-size:30px;
            }

            .section-head{
                flex-direction:column;
                align-items:flex-start;
            }

            .contact-link{
                flex-direction:column;
                align-items:flex-start;
            }

            .contact-link span{
                text-align:left;
            }
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="container">
        <div class="navbar-inner">
            <a class="brand" href="index.php">
                <div class="brand-mark"></div>
                <div class="brand-text">
                    <h1>Madaratrade</h1>
                    <small>GTA Online Car Identity Platform</small>
                </div>
            </a>

            <nav class="nav-links">
                <a class="btn" href="home.php">Home</a>
                <a class="btn" href="explore.php">Explore</a>
                <a class="btn" href="profile.php">Profile</a>
                <a class="btn" href="account.php">Account</a>
		<?php if($isLoggedIn): ?>
		    <a class="btn" href="chat.php">Messages</a>
                    <a class="btn btn-primary" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="login.php">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container">
            <div class="hero-card">
                <span class="hero-badge">Contact Madaratrade</span>
                <h2>Let’s Connect</h2>
                <p>
                    Reach out through business or personal channels. For website identity, the profile and account
                    pages are associated with <strong>@ammighorbani</strong>.
                </p>

                <div class="contact-grid">
                    <?php foreach ($contacts as $item): ?>
                        <div class="contact-card">
                            <span class="contact-tag">Contact</span>
                            <h3><?php echo e($item['label']); ?></h3>
                            <p><?php echo e($item['note']); ?></p>

                            <a class="contact-link" href="<?php echo e($item['href']); ?>" target="<?php echo str_starts_with($item['href'], 'http') ? '_blank' : '_self'; ?>" rel="noopener">
                                <strong>Open</strong>
                                <span><?php echo e($item['value']); ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <h3>Quick Info</h3>
                    <p>Business and personal contact channels in one clean view.</p>
                </div>
            </div>

            <div class="info-box">
                <p>
                    Business Instagram: <strong>@madara.tradeog</strong><br>
                    Personal Instagram: <strong>@ammighorbani</strong><br>
                    Business Telegram: <strong>@madara.tradeog</strong><br>
                    Personal Telegram: <strong>@ammighorbani</strong><br>
                    Business Email: <strong>info@madaratrade.com</strong><br>
                    Website Profile / Account: <strong>@ammighorbani</strong>
                </p>

                <div class="actions">
                    <a class="btn btn-primary" href="mailto:info@madaratrade.com">Send Email</a>
                    <a class="btn" href="https://instagram.com/madara.tradeog" target="_blank" rel="noopener">Business Instagram</a>
                    <a class="btn" href="https://t.me/madara.tradeog" target="_blank" rel="noopener">Business Telegram</a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="container">
        <div class="footer-box">
            <div>
                Contact page for Madaratrade — built with the same neon glassmorphism theme.
            </div>
            <div class="footer-links">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms of Service</a>
                <a href="index.php">Home</a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
