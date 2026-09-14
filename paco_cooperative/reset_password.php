<?php
require_once 'db.php';
require_once 'pascco_shell.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$message = '';
$valid_token = null;

if ($token !== '') {
    $stmt = $pdo->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $valid_token = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['password_confirmation'] ?? '';
    if (!$valid_token) {
        $error = 'This reset link is invalid or has expired.';
    } elseif (strlen($password) < 8 || $password !== $confirmation) {
        $error = 'Passwords must match and contain at least 8 characters.';
    } else {
        try {
            $pdo->beginTransaction();
            $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update->execute([password_hash($password, PASSWORD_DEFAULT), (int) $valid_token['user_id']]);
            $consume = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?');
            $consume->execute([(int) $valid_token['id']]);
            $pdo->commit();
            $message = 'Your password has been reset. You can now sign in.';
            $valid_token = null;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Your password could not be reset. Please request a new link.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><?php pascco_global_styles(); ?><title>Set New Password | PASCCO</title><style>body{background:#f4f7fb;color:#182a45;font-family:Georgia,'Times New Roman',serif;margin:0}.card{background:white;border-top:4px solid #1456a0;margin:60px auto;max-width:480px;padding:28px 24px}label{display:block;font-weight:bold;margin:14px 0 6px}input{border:1px solid #b8c8d9;font:inherit;padding:11px;width:100%}button{background:#1456a0;border:0;color:white;font:inherit;font-weight:bold;margin-top:18px;padding:12px;width:100%}.notice,.error{margin-bottom:18px;padding:12px}.notice{background:#fff4cf}.error{background:#f8d7da}</style></head>
<body>
<?php pascco_public_header('Login', 'login.php'); ?>
<main class="card"><h1>Set New Password</h1><?php if ($message): ?><div class="notice" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><p><a href="login.php">Continue to login</a></p><?php elseif ($error): ?><div class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><p><a href="forgot_password.php">Request another reset link</a></p><?php else: ?><form method="POST"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>"><label for="password">New Password</label><input id="password" type="password" name="password" minlength="8" autocomplete="new-password" required><label for="confirmation">Confirm New Password</label><input id="confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required><button type="submit">Reset Password</button></form><?php endif; ?></main>
<?php pascco_global_footer(); ?>
</body>
</html>
