<?php
require_once __DIR__ . '/security/session.php';

function pascco_header(string $title, string $active = ''): void
{
    $logged_in = isset($_SESSION['user_id']);
    $destination = $logged_in ? 'dashboard.php' : 'login.php';
    $account_label = $logged_in ? 'Dashboard' : 'Login';
    $nav_items = [
        'home' => ['Home', 'home.php'],
        'savings' => ['Savings', 'savings.php'],
        'credit' => ['Credit', 'credit.php'],
        'requirements' => ['Requirements', 'requirements.php'],
        'about' => ['About', 'about.php'],
    ];
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> | PASCCO</title>
        <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
        <style>
            :root { --blue: #1456a0; --deep-blue: #071d49; --gold: #e7b84b; --cream: #f4f7fb; --ink: #182a45; --line: #d8e0ee; }
            * { box-sizing: border-box; }
            body { margin: 0; color: var(--ink); font-family: Georgia, 'Times New Roman', serif; background: var(--cream); }
            .site-header { align-items: center; background: linear-gradient(90deg, var(--deep-blue), var(--blue)); color: white; display: flex; gap: 20px; min-height: 78px; padding: 14px clamp(18px, 5vw, 72px); }
            .brand { align-items: center; color: white; display: flex; gap: 10px; margin-right: auto; text-decoration: none; }
            .brand img { height: 48px; width: 48px; object-fit: contain; }
            .brand strong { font-size: 1.2rem; letter-spacing: .08em; }
            .site-nav { display: flex; flex-wrap: wrap; gap: 4px; justify-content: flex-end; }
            .site-nav a, .header-action { border-bottom: 2px solid transparent; color: white; padding: 9px 10px; text-decoration: none; white-space: nowrap; }
            .site-nav a:hover, .site-nav a.active { border-bottom-color: var(--gold); color: #ffe7a1; }
            .header-action { border: 1px solid var(--gold); border-radius: 4px; color: #ffe7a1; white-space: nowrap; }
            .page-shell { margin: 0 auto; max-width: 1120px; padding: 54px 22px 70px; }
            .eyebrow { color: var(--blue); font-size: .82rem; font-weight: bold; letter-spacing: .18em; text-transform: uppercase; }
            h1 { color: var(--deep-blue); font-size: clamp(2rem, 5vw, 3.8rem); line-height: 1.05; margin: 10px 0 18px; }
            h2 { color: var(--blue); margin-top: 0; }
            .intro { font-size: 1.1rem; line-height: 1.75; max-width: 760px; }
            .hero-panel { background: linear-gradient(125deg, rgba(7,29,73,.97), rgba(20,86,160,.9)), url('paco2.png') center/cover; color: white; padding: clamp(32px, 7vw, 78px); }
            .hero-panel h1, .hero-panel h2 { color: white; }
            .hero-panel .eyebrow { color: #ffe7a1; }
            .button { background: var(--gold); border: 0; border-radius: 4px; color: var(--deep-blue); display: inline-block; font-weight: bold; margin: 8px 8px 0 0; padding: 13px 18px; text-decoration: none; }
            .button.secondary { background: transparent; border: 1px solid var(--gold); color: #ffe7a1; }
            .grid { display: grid; gap: 20px; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); margin-top: 28px; }
            .panel { background: white; border: 1px solid var(--line); border-top: 4px solid var(--blue); padding: 25px; text-align: center; }
            .panel p, .panel li { line-height: 1.65; }
            .panel ul, .panel ol { padding-left: 22px; }
            .data-table { background: white; border-collapse: collapse; margin-top: 22px; width: 100%; }
            .data-table th, .data-table td { border-bottom: 1px solid var(--line); padding: 15px; text-align: left; }
            .data-table th { background: #e6edf8; color: var(--deep-blue); }
            
            /* Footer Styles */
            .site-footer { background: var(--deep-blue); color: #edf3ff; display: flex; flex-wrap: wrap; gap: 28px; justify-content: space-between; padding: 34px clamp(22px, 6vw, 80px); }
            .site-footer a { color: #ffe7a1; text-decoration: none; }
            .site-footer a:hover { text-decoration: underline; }
            .site-footer img { height: 54px; vertical-align: middle; width: 54px; object-fit: contain; }
            .copyright { border-top: 1px solid rgba(255,255,255,.2); flex-basis: 100%; padding-top: 18px; }

            @media (max-width: 980px) { .site-header { align-items: flex-start; flex-wrap: wrap; } .site-nav { flex: 1 1 100%; justify-content: flex-start; order: 3; overflow-x: auto; padding-bottom: 2px; } .header-action { margin-left: auto; } }
            @media (max-width: 560px) { .site-header { gap: 10px; padding: 12px 16px; } .brand strong { font-size: 1.05rem; } .brand img { height: 40px; width: 40px; } .site-nav { display: grid; gap: 4px; grid-template-columns: repeat(2, minmax(0, 1fr)); overflow: visible; width: 100%; } .site-nav a { border: 1px solid rgba(255,255,255,.18); padding: 10px 8px; text-align: center; } }
            @media print {.site-header, .site-footer, .slip-form, .no-print {display: none;} .page-shell { padding: 0;}
}
        </style>
    </head>
    <body>
    <header class="site-header">
        <a class="brand" href="home.php"><img src="LOGO.png" alt="PASCCO logo"><strong>PASCCO</strong></a>
        <nav class="site-nav" aria-label="Primary navigation">
            <?php foreach ($nav_items as $key => [$label, $url]): ?>
                <a class="<?php echo $active === $key ? 'active' : ''; ?>" href="<?php echo $url; ?>" <?php echo $active === $key ? 'aria-current="page"' : ''; ?>><?php echo $label; ?></a>
            <?php endforeach; ?>
        </nav>
        <a class="header-action" href="<?php echo $destination; ?>"><?php echo $account_label; ?></a>
    </header>
    <?php
}

function pascco_footer(): void
{
    ?>
    <footer class="site-footer">
        <div>
            <strong>PACO SAVINGS &amp; CREDIT COOPERATIVE</strong>
            <p>Helping People Help Themselves</p>
            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 12px;">
                <a href="support.php" style="display: flex; align-items: center; gap: 6px;">
                    <i class='bx bx-help-circle' style="font-size: 1.2em;"></i> Help &amp; Support
                </a>
                <a href="privacy.php" style="display: flex; align-items: center; gap: 6px;">
                    <i class='bx bx-shield' style="font-size: 1.2em;"></i> Privacy &amp; Terms
                </a>
            </div>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 10px; text-align: left;">
            <a href="https://www.facebook.com/pacopascco" style="display: flex; align-items: center; gap: 6px;">
                <i class='bx bxl-facebook-square' style="font-size: 1.2em;"></i> facebook.com/pacopascco
            </a>
            <span style="color: #edf3ff; display: flex; align-items: center; gap: 6px;">
                <i class='bx bx-envelope' style="font-size: 1.2em;"></i> pascco30@gmail.com
            </span>
            <span style="color: #edf3ff; display: flex; align-items: center; gap: 6px;">
                <i class='bx bx-mobile' style="font-size: 1.2em;"></i> 0908-3688038 (SMART)
            </span>
        </div>

        <div>
            <img src="LOGO.png" alt="PASCCO logo">
            <p>1461 Merced corner Perdigon Streets,<br>Paco 1007 Manila</p>
        </div>
        
        <div class="copyright">&copy; <?php echo date('Y'); ?> PASCCO. All rights reserved.</div>
    </footer>
    <script src="animated-button.js"></script>
    </body>
    </html>
    <?php
}
