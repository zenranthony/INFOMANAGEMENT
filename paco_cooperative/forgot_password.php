<?php
require_once 'db.php';
require_once 'pascco_shell.php';

$notice = 'If that email is registered, password reset instructions will be sent shortly.';
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $insert = $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))');
            $insert->execute([(int) $user['id'], hash('sha256', $token)]);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $reset_url = 'https://localhost/paco_cooperative/reset_password.php?token=' . urlencode($token);
            $sent = mail($email, 'PASCCO password reset', "Use this link within 30 minutes to reset your password:\n\n" . $reset_url);
            if (!$sent) {
                $delete = $pdo->prepare('DELETE FROM password_reset_tokens WHERE token_hash = ?');
                $delete->execute([hash('sha256', $token)]);
                $error = 'Password recovery is temporarily unavailable. Please contact PASCCO support.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><?php pascco_global_styles(); ?><title>Forgot Password | PASCCO</title><style>body{background:#f4f7fb;color:#182a45;font-family:Georgia,'Times New Roman',serif;margin:0}.card{background:white;border-top:4px solid #1456a0;margin:60px auto;max-width:480px;padding:28px 24px}label{display:block;font-weight:bold;margin:14px 0 6px}input{border:1px solid #b8c8d9;font:inherit;padding:11px;width:100%}button{background:#1456a0;border:0;color:white;font:inherit;font-weight:bold;margin-top:18px;padding:12px;width:100%}.notice,.error{margin-bottom:18px;padding:12px}.notice{background:#fff4cf}.error{background:#f8d7da}</style></head>
<body>
<?php pascco_public_header('Login', 'login.php'); ?>
<main class="card"><h1>Reset Your Password</h1><p>Enter the email address used for your PASCCO account.</p><?php if ($notice): ?><div class="notice" role="status"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><?php if ($error): ?><div class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><form method="POST"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"><label for="email">Email Address</label><input id="email" type="email" name="email" autocomplete="email" required><button type="submit">Send Reset Instructions</button></form></main>
<?php pascco_global_footer(); ?>
</body>
</html>
