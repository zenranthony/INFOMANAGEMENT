<?php
require_once 'db.php';
require_once 'pascco_shell.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'member') {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$message = '';
$error = '';

$stmt = $pdo->prepare('SELECT u.username, m.first_name, m.last_name, m.email, m.phone, m.member_number FROM users u JOIN members m ON m.user_id = u.id WHERE u.id = ?');
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    exit('Member profile not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($first_name === '' || $last_name === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid name, email address, and phone number.';
        } else {
            try {
                $pdo->beginTransaction();
                $user_update = $pdo->prepare('UPDATE users SET username = ? WHERE id = ?');
                $user_update->execute([$email, $user_id]);
                $member_update = $pdo->prepare('UPDATE members SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?');
                $member_update->execute([$first_name, $last_name, $email, $phone, $user_id]);
                $pdo->commit();
                $_SESSION['username'] = $email;
                $message = 'Profile updated successfully.';
                $stmt->execute([$user_id]);
                $profile = $stmt->fetch();
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = (int) ($exception->errorInfo[1] ?? 0) === 1062
                    ? 'That email address is already in use.'
                    : 'Your profile could not be updated.';
            }
        }
    } elseif ($action === 'password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $password_stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $password_stmt->execute([$user_id]);
        $user = $password_stmt->fetch();

        if (!$user || !password_verify($current_password, $user['password'])) {
            $error = 'Your current password is incorrect.';
        } elseif (strlen($new_password) < 8 || $new_password !== $confirm_password) {
            $error = 'New passwords must match and contain at least 8 characters.';
        } else {
            $update = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
            $message = 'Password changed successfully.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?>
    <title>My Profile | PASCCO</title>
    <style>
        body { background: #f4f7fb; color: #182a45; font-family: Georgia, 'Times New Roman', serif; margin: 0; }
        .profile-page { margin: 0 auto; max-width: 900px; padding: 42px 22px 70px; }
        .heading { margin-bottom: 24px; }
        .heading h1 { color: #071d49; margin: 8px 0 0; }
        .columns { display: grid; gap: 22px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .card { background: white; border: 1px solid #d8e0ee; border-top: 4px solid #1456a0; padding: 24px; }
        label { color: #071d49; display: block; font-weight: bold; margin: 14px 0 6px; }
        input { border: 1px solid #b8c8d9; border-radius: 3px; font: inherit; padding: 11px; width: 100%; }
        button { background: #1456a0; border: 0; color: white; cursor: pointer; font: inherit; font-weight: bold; margin-top: 18px; padding: 12px 16px; width: 100%; }
        .notice, .error { margin-bottom: 18px; padding: 12px; }
        .notice { background: #fff4cf; border-left: 4px solid #e7b84b; color: #071d49; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .readonly { color: #5d6b7e; }
        @media (max-width: 700px) { .columns { grid-template-columns: 1fr; } }
    </style>
    <link rel="stylesheet" href="member-panels.css?v=2">
</head>
<body>
<?php pascco_member_header('My Profile'); ?>
<main class="profile-page member-content">
    <section class="heading"><div class="eyebrow">Account settings</div><h1>My Profile</h1></section>
    <?php if ($message): ?><div class="notice" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <div class="columns">
        <section class="card"><h2>Personal Information</h2><p class="readonly">Member number: <?php echo htmlspecialchars($profile['member_number'], ENT_QUOTES, 'UTF-8'); ?></p><form method="POST"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="profile"><label for="first-name">First Name</label><input id="first-name" name="first_name" value="<?php echo htmlspecialchars($profile['first_name'], ENT_QUOTES, 'UTF-8'); ?>" required><label for="last-name">Last Name</label><input id="last-name" name="last_name" value="<?php echo htmlspecialchars($profile['last_name'], ENT_QUOTES, 'UTF-8'); ?>" required><label for="email">Email Address</label><input id="email" type="email" name="email" value="<?php echo htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8'); ?>" required><label for="phone">Phone Number</label><input id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone'], ENT_QUOTES, 'UTF-8'); ?>" required><button type="submit">Save Profile</button></form></section>
        <section class="card"><h2>Change Password</h2><form method="POST"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="password"><label for="current-password">Current Password</label><input id="current-password" type="password" name="current_password" autocomplete="current-password" required><label for="new-password">New Password</label><input id="new-password" type="password" name="new_password" minlength="8" autocomplete="new-password" required><label for="confirm-password">Confirm New Password</label><input id="confirm-password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required><button type="submit">Change Password</button></form></section>
    </div>
</main>
<?php pascco_global_footer(); ?>
</body>
</html>
