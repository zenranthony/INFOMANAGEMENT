<?php
function pascco_member_header(string $title): void
{
    $current_page = basename($_SERVER['PHP_SELF']);
    $member_links = [
        ['home.php', 'Home'],
        ['dashboard.php', 'Dashboard'],
        ['member_savings.php', 'My Savings'],
        ['open_account.php', 'Open Account'],
        ['deposit.php', 'Deposit Money'],
        ['profile.php', 'My Profile'],
        ['announcements.php', 'Community'],
        ['transfer.php', 'Transfer Funds'],
        ['apply_loan.php', 'Apply for Loan'],
    ];
    ?>
    <header class="pascco-global-header">
        <a class="pascco-global-brand" href="dashboard.php"><img src="LOGO.png" alt="PASCCO logo"><strong>PASCCO</strong></a>
        <button class="pascco-menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <div class="pascco-global-account">
            <span class="pascco-account-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Member', ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="logout.php" class="Btn" aria-label="Logout">
                <div class="sign">
                    <svg viewBox="0 0 512 512">
                        <path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"></path>
                    </svg>
                </div>
                <span class="text">Logout</span>
            </a>
        </div>
    </header>
    <?php
}

function pascco_admin_header(): void
{
    $current_page = basename($_SERVER['PHP_SELF']);
    $admin_links = [
        ['admin_dashboard.php', 'Dashboard'],
        ['admin_members.php', 'Members'],
        ['admin_membership.php', 'Membership'],
        ['admin_account_requests.php', 'Account Requests'],
        ['admin_loans.php', 'Loan Approvals'],
        ['admin_deposits.php', 'Deposit Reviews'],
        ['admin_announcements.php', 'Announcements'],
    ];
    ?>
    <header class="pascco-global-header">
        <a class="pascco-global-brand" href="admin_dashboard.php"><img src="LOGO.png" alt="PASCCO logo"><strong>PASCCO ADMIN</strong></a>
        <nav class="pascco-global-nav" aria-label="Admin navigation">
            <?php foreach ($admin_links as [$url, $label]): ?><a class="<?php echo $current_page === $url ? 'is-active' : ''; ?>" href="<?php echo $url; ?>" <?php echo $current_page === $url ? 'aria-current="page"' : ''; ?>><?php echo $label; ?></a><?php endforeach; ?>
            <a href="home.php">Public Site</a>
        </nav>
        <button class="pascco-menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <div class="pascco-global-account">
            <span>Administrator</span>
            <a href="logout.php" class="Btn" aria-label="Logout">
                <div class="sign">
                    <svg viewBox="0 0 512 512">
                        <path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"></path>
                    </svg>
                </div>
                <span class="text">Logout</span>
            </a>
        </div>
    </header>
    <?php
}

function pascco_public_header(string $action_label, string $action_url): void
{
    $current_page = basename($_SERVER['PHP_SELF']);
    $public_links = [
    ['home.php', 'Home'],
    ['savings.php', 'Savings'],
    ['credit.php', 'Credit'],
    ['requirements.php', 'Requirements'],
    ['about.php', 'About'],
];
    ?>
    <header class="pascco-global-header">
        <a class="pascco-global-brand" href="home.php"><img src="LOGO.png" alt="PASCCO logo"><strong>PASCCO</strong></a>
        <nav class="pascco-global-nav" aria-label="Public navigation">
            <?php foreach ($public_links as [$url, $label]): ?><a class="<?php echo $current_page === $url ? 'is-active' : ''; ?>" href="<?php echo $url; ?>" <?php echo $current_page === $url ? 'aria-current="page"' : ''; ?>><?php echo $label; ?></a><?php endforeach; ?>
        </nav>
        <button class="pascco-menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <div class="pascco-global-account"><a class="pascco-header-action" href="<?php echo htmlspecialchars($action_url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($action_label, ENT_QUOTES, 'UTF-8'); ?></a></div>
    </header>
    <?php
}

function pascco_global_styles(): void
{
    ?>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        html, body { overflow-x: hidden; }
        .pascco-global-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: nowrap; background: linear-gradient(90deg, #071d49, #1456a0); color: white; min-height: 78px; padding: 12px clamp(18px, 5vw, 72px); position: relative; gap: 15px; }
        .pascco-global-brand { align-items: center; color: white; display: flex; gap: 10px; text-decoration: none; flex-shrink: 0; }
        .pascco-global-brand img { height: 48px; object-fit: contain; width: 48px; }
        .pascco-global-brand strong { font-family: Impact, 'Arial Black', sans-serif; font-size: 1.3rem; letter-spacing: .06em; }
        
        .pascco-global-nav { display: flex; flex-wrap: wrap; gap: 3px; justify-content: center; flex: 1; margin: 0 15px; }
        .pascco-global-nav a { border-bottom: 2px solid transparent; color: white; padding: 9px 10px; text-decoration: none; white-space: nowrap; }
        .pascco-global-nav a:hover, .pascco-global-nav a.is-active { border-bottom-color: #e7b84b; color: #ffe7a1; }
        
        .pascco-global-account { display: flex; align-items: center; justify-content: flex-end; gap: 16px; margin-left: auto; white-space: nowrap; flex-shrink: 0; }
        .pascco-header-action, .pascco-header-action:visited { display: inline-block; border: 1px solid #e7b84b; border-radius: 4px; color: #ffe7a1; padding: 9px 10px; text-decoration: none; white-space: nowrap; }
        .pascco-header-action:hover { background: #e7b84b; color: #071d49; }
        .pascco-header-action:focus-visible { outline: 2px solid #ffe7a1; outline-offset: 3px; }
        .pascco-account-name { max-width: 150px; overflow: hidden; text-overflow: ellipsis; color: white; font-family: Georgia, 'Times New Roman', serif; font-weight: 500; display: inline-block; }
        
        .pascco-global-account .Btn {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            width: 42px !important;
            height: 42px !important;
            border: none !important;
            border-radius: 50% !important;
            cursor: pointer !important;
            position: relative !important;
            overflow: hidden !important;
            transition-duration: 0.3s !important;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.199) !important;
            background-color: #e7b84b !important;
            text-decoration: none !important;
            box-sizing: border-box !important;
            flex-shrink: 0 !important;
        }

        .pascco-global-account .Btn .sign {
            width: 100% !important;
            transition-duration: 0.3s !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .pascco-global-account .Btn .sign svg {
            width: 18px !important;
            height: 18px !important;
        }

        .pascco-global-account .Btn .sign svg path {
            fill: #071d49 !important;
        }

        .pascco-global-account .Btn .text {
            position: absolute !important;
            right: 0% !important;
            width: 0% !important;
            opacity: 0 !important;
            color: #071d49 !important;
            font-size: 1.1em !important;
            font-weight: 600 !important;
            font-family: Georgia, 'Times New Roman', serif !important;
            transition-duration: 0.3s !important;
            white-space: nowrap !important;
        }

        .pascco-global-account .Btn:hover {
            width: 120px !important;
            border-radius: 40px !important;
            transition-duration: 0.3s !important;
        }

        .pascco-global-account .Btn:hover .sign {
            width: 30% !important;
            transition-duration: 0.3s !important;
            padding-left: 15px !important;
        }

        .pascco-global-account .Btn:hover .text {
            opacity: 1 !important;
            width: 70% !important;
            transition-duration: 0.3s !important;
            padding-right: 10px !important;
        }

        .pascco-global-account .Btn:active {
            transform: translate(2px, 2px) !important;
        }

        .pascco-menu-toggle { background: transparent; border: 1px solid rgba(255,255,255,.35); border-radius: 8px; cursor: pointer; display: none; height: 42px; padding: 8px 10px; width: 42px; }
        .pascco-menu-toggle span { background: white; border-radius: 2px; display: block; height: 2px; margin: 5px 0; width: 100%; }
        
        button, 
        input[type="submit"], 
        input[type="button"], 
        .btn-action,
        a.btn,
        .account-card a {
            font-family: Georgia, 'Times New Roman', serif !important;
            transition: all 0.2s ease !important;
        }

        button:hover, 
        input[type="submit"]:hover, 
        input[type="button"]:hover, 
        .btn-action:hover,
        a.btn:hover,
        .account-card a:hover {
            color: #e7b84b !important;
            filter: brightness(1.1) !important;
        }

        input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]),
        select,
        textarea {
            width: 100%;
            padding: 12px 20px;
            background: #ffffff;
            border: 1px solid #b8c8bd;
            border-radius: 50px;
            color: #182a45;
            font-size: 14px;
            font-family: Georgia, 'Times New Roman', serif;
            outline: none;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg fill='%23182a45' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 45px;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #1456a0;
            box-shadow: 0 0 5px rgba(20, 86, 160, 0.3);
        }

        .pascco-global-footer { background: #071d49; color: #edf3ff; display: flex; flex-wrap: wrap; gap: 28px; justify-content: space-between; margin-top: 24px; padding: 34px clamp(22px, 6vw, 80px); }
        .pascco-global-footer a { color: #ffe7a1; text-decoration: none; }
        .pascco-global-footer a:hover { text-decoration: underline; }
        .pascco-global-footer img { height: 54px; width: 54px; object-fit: contain; }
        .pascco-global-copyright { border-top: 1px solid rgba(255,255,255,.2); flex-basis: 100%; padding-top: 18px; }
        
        @media (max-width: 980px) { 
            .pascco-global-header { align-items: flex-start; flex-wrap: wrap; } 
            .pascco-global-nav { flex: 1 1 100%; justify-content: flex-start; order: 3; overflow-x: auto; padding-bottom: 2px; margin: 0; } 
            .pascco-global-nav a { flex: 0 0 auto; } 
            .pascco-global-account { margin-left: auto; } 
        }
        @media (max-width: 560px) {
            .pascco-global-header {
                align-items: center;
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                padding: 12px 16px;
            }
            .pascco-menu-toggle { display: inline-block; order: 2; }
            .pascco-global-brand { margin-right: 0; order: 1; }
            .pascco-global-brand strong { font-size: 1.05rem; }
            .pascco-global-brand img { height: 40px; width: 40px; }
            .pascco-global-account { font-size: .82rem; margin-left: auto; order: 3; }
            .pascco-account-name { display: none; }
            .pascco-global-nav {
                display: none;
                gap: 4px;
                grid-template-columns: 1fr;
                order: 4;
                overflow: visible;
                width: 100%;
            }
            .pascco-global-nav.is-open { display: grid; }
            .pascco-global-nav a { border: 1px solid rgba(255,255,255,.18); padding: 10px 8px; text-align: center; }
        }
        @media (max-width: 420px) {
            .pascco-global-account { justify-content: flex-end; width: auto; }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggles = document.querySelectorAll('.pascco-menu-toggle');
            toggles.forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    const header = this.closest('.pascco-global-header');
                    const nav = header ? header.querySelector('.pascco-global-nav') : null;
                    if (!nav) {
                        return;
                    }
                    const isOpen = nav.classList.toggle('is-open');
                    this.setAttribute('aria-expanded', String(isOpen));
                });
            });

            document.querySelectorAll('.pascco-global-nav a').forEach(function (link) {
                link.addEventListener('click', function () {
                    const header = this.closest('.pascco-global-header');
                    const nav = header ? header.querySelector('.pascco-global-nav') : null;
                    const toggle = header ? header.querySelector('.pascco-menu-toggle') : null;
                    if (nav) {
                        nav.classList.remove('is-open');
                    }
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        });
    </script>
    <?php
}

function pascco_global_footer(): void
{
    ?>
    <footer class="pascco-global-footer">
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
        
        <div class="pascco-global-copyright">&copy; <?php echo date('Y'); ?> PASCCO. All rights reserved.</div>
    </footer>
    <script src="sidebar.js?v=1002"></script>
    <script src="animated_button.js?v=2"></script>
    <?php
}
