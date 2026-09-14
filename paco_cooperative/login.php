<?php
require_once 'db.php';
require_once 'pascco_shell.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $ipAllowed = pascco_take_attempt($pdo, 'password-ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 60);
    $stmt = $pdo->prepare('SELECT id, username, password, role, status FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    $accountIdentity = $user ? 'id:' . $user['id'] : mb_strtolower($username, 'UTF-8');
    $accountAllowed = $ipAllowed && pascco_take_attempt($pdo, 'password-account', $accountIdentity, 10);
    if (!$ipAllowed || !$accountAllowed) {
        http_response_code(429);
        header('Retry-After: 900');
        $error = 'Too many login attempts. Please wait 15 minutes before trying again.';
    } else {
        // A real hash is checked for unknown accounts too, reducing timing differences.
        $valid = password_verify($password, $user['password'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if ($valid && $user && $user['status'] === 'active' && in_array($user['role'], ['admin', 'member'], true)) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['otp_user_id'] = (int) $user['id'];
            $_SESSION['otp_started_at'] = time();
            $_SESSION['otp_password_version'] = hash('sha256', $user['password']);
            record_audit($pdo, (int) $user['id'], 'password_verified', 'Password verified; authenticator verification required.', 'success');
            header('Location: otp_verify.php'); exit;
        }
        $error = 'Invalid username or password.';
        record_audit($pdo, null, 'login', 'Failed login attempt.', 'failed');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?>
    <title>Login | PASCCO</title>
    <style>
        :root { --deep-blue: #071d49; --blue: #1456a0; --gold: #e7b84b; --ink: #182a45; }
        * { box-sizing: border-box; }
        body { background: var(--deep-blue) url('paco2.png') center / cover fixed; color: white; font-family: Georgia, 'Times New Roman', serif; margin: 0; min-height: 100vh; }
        body::before { background: rgba(7, 29, 73, .78); content: ''; inset: 0; position: fixed; z-index: -1; }
        .site-header { align-items: center; background: linear-gradient(90deg, var(--deep-blue), var(--blue)); display: flex; gap: 24px; min-height: 84px; padding: 12px clamp(18px, 5vw, 70px); }
        .brand { align-items: center; color: white; display: flex; gap: 10px; margin-right: auto; text-decoration: none; }
        .brand img { height: 54px; object-fit: contain; width: 54px; }
        .brand strong { font-family: Impact, 'Arial Black', sans-serif; font-size: clamp(1.7rem, 4vw, 2.5rem); letter-spacing: .05em; }
        .site-nav { display: flex; flex-wrap: wrap; gap: 18px; }
        .site-nav a, .header-action { color: white; font-size: .82rem; font-weight: bold; letter-spacing: .06em; text-decoration: none; }
        .site-nav a:hover, .header-action:hover { color: var(--gold); }
        .header-action { border: 1px solid var(--gold); padding: 9px 12px; }
        .hero { align-items: center; display: grid; gap: clamp(30px, 7vw, 100px); grid-template-columns: minmax(0, 1fr) minmax(320px, 460px); margin: 0 auto; max-width: 1120px; min-height: calc(100vh - 84px); padding: 55px 22px 80px; }
        .hero-copy { text-align: center; }
        .hero-copy img { filter: drop-shadow(0 10px 15px rgba(0,0,0,.4)); max-width: 150px; width: 35%; }
        .hero-copy h1 { font-size: clamp(2.3rem, 5vw, 4.2rem); line-height: 1.05; margin: 25px 0 0; text-shadow: 3px 4px 0 rgba(0,0,0,.35); }
        .hero-copy p { color: #ffe7a1; letter-spacing: .16em; }
        .login-card { backdrop-filter: blur(14px); background: rgba(0,0,0,.43); border: 1px solid rgba(255,255,255,.65); border-radius: 22px; box-shadow: 0 18px 50px rgba(0,0,0,.3); padding: clamp(26px, 5vw, 44px); }
        .login-card h2 { font-size: 2rem; margin: 0 0 24px; text-align: center; }
        .field { margin-bottom: 22px; }
        .field label { display: block; font-size: .9rem; font-weight: bold; margin-bottom: 8px; }
        .field input { background: rgba(255,255,255,.08); border: 0; border-bottom: 2px solid rgba(255,255,255,.75); color: white; font: inherit; outline: 0; padding: 12px 4px; width: 100%; }
        .field input:focus { border-bottom-color: var(--gold); }
        .form-row { align-items: center; display: flex; font-size: .85rem; justify-content: space-between; margin: 4px 0 22px; }
        .form-row a, .register-link a { color: var(--gold); font-weight: bold; }
        .form-row input { accent-color: var(--gold); }
        .submit { background: linear-gradient(90deg, var(--blue), var(--deep-blue), var(--blue)); border: 1px solid rgba(231,184,75,.7); border-radius: 30px; color: white; cursor: pointer; font: inherit; font-weight: bold; padding: 14px; width: 100%; }
        .submit:hover { filter: brightness(1.15); }
        .error { background: rgba(255, 220, 220, .95); border-left: 4px solid #e05a5a; color: #8e1e1e; margin-bottom: 20px; padding: 12px; }
        .register-link { font-size: .9rem; margin: 25px 0 0; text-align: center; }
        @media (max-width: 760px) { .site-nav { display: none; } .hero { grid-template-columns: 1fr; min-height: auto; padding-top: 50px; } .hero-copy h1 { font-size: 2.6rem; } .login-card { max-width: 480px; width: 100%; justify-self: center; } }
    </style>
</head>
<body>
    <?php pascco_public_header('Sign Up', 'register.php'); ?>
    <main class="hero">
        <section class="hero-copy">
            <img src="LOGO.png" alt="Paco Savings and Credit Cooperative logo">
            <h1>PACO SAVINGS<br>&amp; CREDIT<br>COOPERATIVE</h1>
            <p>HELPING PEOPLE HELP THEMSELVES</p>
        </section>
        <section class="login-card" aria-labelledby="login-title">
            <h2 id="login-title">Member Login</h2>
            <?php if ($error): ?><div class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="field"><label for="username">Username</label><input id="username" type="text" name="username" autocomplete="username" required></div>
                <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" required></div>
                <div class="form-row"><span></span><a href="forgot_password.php">Forgot password?</a></div>
                <button class="submit" type="submit">Sign In</button>
            </form>
            <p class="register-link">Not yet a member? <a href="register.php">Create an account</a></p>
        </section>
    </main>
    <?php pascco_global_footer(); ?>
</body>
</html>
