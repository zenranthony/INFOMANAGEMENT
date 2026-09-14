<?php
require_once 'db.php';
require_once 'pascco_shell.php';
if (empty($_SESSION['otp_user_id']) || time() - ($_SESSION['otp_started_at'] ?? 0) > 600) {
    unset($_SESSION['otp_user_id'], $_SESSION['enrollment_secret']);
    header('Location: login.php'); exit;
}
$userId = (int) $_SESSION['otp_user_id'];
$stmt = $pdo->prepare('SELECT id, username, password, role, status FROM users WHERE id = ?');
$stmt->execute([$userId]); $user = $stmt->fetch();
if (!$user || $user['status'] !== 'active' || !hash_equals($_SESSION['otp_password_version'] ?? '', hash('sha256', $user['password']))) {
    $_SESSION = []; header('Location: login.php'); exit;
}
$stmt = $pdo->prepare('SELECT secret, last_step FROM user_mfa WHERE user_id = ?');
$stmt->execute([$userId]); $mfa = $stmt->fetch();
$enrolling = !$mfa;
if ($enrolling && empty($_SESSION['enrollment_secret'])) { $_SESSION['enrollment_secret'] = pascco_base32_encode(random_bytes(20)); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $allowed = pascco_take_attempt($pdo, 'authenticator-user', (string) $userId, 5);
    $ipAllowed = pascco_take_attempt($pdo, 'authenticator-ip', $_SERVER['REMOTE_ADDR'] ?? 'unknown', 30);
    if (!$allowed || !$ipAllowed) {
        http_response_code(429); header('Retry-After: 900');
        $error = 'Too many verification attempts. Wait 15 minutes, then sign in again.';
    } else {
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT password, status, role FROM users WHERE id = ? FOR UPDATE');
            $lock->execute([$userId]); $lockedUser = $lock->fetch();
            if (!$lockedUser || $lockedUser['status'] !== 'active' || !hash_equals($_SESSION['otp_password_version'], hash('sha256', $lockedUser['password']))) { throw new RuntimeException('Account changed.'); }
            $stmt = $pdo->prepare('SELECT secret, last_step FROM user_mfa WHERE user_id = ? FOR UPDATE');
            $stmt->execute([$userId]); $current = $stmt->fetch();
            $key = base64_decode($pascco_config['encryption_key'], true);
            $secret = $current ? pascco_decrypt_secret($current['secret'], $key) : $_SESSION['enrollment_secret'];
            $step = pascco_totp_step($secret, trim((string) ($_POST['otp'] ?? '')), $current ? (int) $current['last_step'] : -1);
            if ($step === null) {
                $pdo->rollBack();
                $error = 'Invalid or already used code. Check your phone clock and enter a new code.';
                record_audit($pdo, $userId, 'otp_failed', 'Authenticator verification failed.', 'failed');
            } else {
                $encrypted = $current ? $current['secret'] : pascco_encrypt_secret($secret, $key);
                if ($current) {
                    $stmt = $pdo->prepare('UPDATE user_mfa SET last_step = ? WHERE user_id = ?'); $stmt->execute([$step, $userId]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO user_mfa (user_id, secret, last_step) VALUES (?, ?, ?)'); $stmt->execute([$userId, $encrypted, $step]);
                }
                $pdo->commit();
                $_SESSION = []; session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $lockedUser['role'];
                $_SESSION['mfa_verified'] = true;
                $_SESSION['mfa_version'] = hash('sha256', $encrypted);
                $_SESSION['credential_version'] = hash('sha256', $lockedUser['password']);
                $_SESSION['authenticated_at'] = $_SESSION['last_activity'] = time();
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                record_audit($pdo, $userId, 'login', 'Successful login with authenticator verification.', 'success');
                header('Location: ' . ($lockedUser['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php')); exit;
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('PASCCO authenticator verification failed internally.');
            $error = 'Verification could not be completed. Please sign in again.';
        }
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Authenticator verification | PASCCO</title>
<?php pascco_global_styles(); ?>
<link rel="stylesheet" href="member-panels.css?v=2">
<style>body{margin:0;background:#f4f7fb;color:#071d49}.verification{max-width:560px;margin:48px auto;padding:0 18px}.verification .card{padding:28px}.verification label{display:block;margin:18px 0 8px}.verification button{margin-top:18px;padding:12px 20px;background:#1456a0;color:white;border:1px solid #e7b84b}.setup-key{display:block;overflow-wrap:anywhere;padding:14px;background:#fff4cf;font-size:17px;letter-spacing:2px}.error{color:#9b2424}</style>
</head><body>
<?php pascco_public_header('Back to login', 'login.php'); ?>
<main class="verification member-content"><section class="card">
<h1><?php echo $enrolling ? 'Set up your authenticator' : 'Verify your login'; ?></h1>
<?php if ($enrolling): ?>
<p>In your authenticator app, add an account using a setup key. Choose a <strong>time-based</strong> code and name the account <strong>PASCCO</strong>.</p>
<p>Account: <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></p>
<code class="setup-key"><?php echo htmlspecialchars($_SESSION['enrollment_secret'], ENT_QUOTES, 'UTF-8'); ?></code>
<p>Keep this setup key private. Enter the six-digit code from your app to finish enrollment. This key will not appear during future logins.</p>
<?php else: ?><p>Enter the current six-digit code from your authenticator app.</p><?php endif; ?>
<?php if ($error): ?><p class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
<label for="otp">Authenticator code</label><input id="otp" name="otp" type="text" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" required autofocus>
<button type="submit">Verify and continue</button></form>
<p>If you lose your authenticator, contact the local system administrator for identity verification and recovery.</p>
</section></main></body></html>
