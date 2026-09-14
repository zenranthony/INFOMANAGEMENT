<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pascco_shell.php';
require_once __DIR__ . '/pascco_products.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'member') {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$message = '';
$error = '';
$account_type = trim($_POST['account_type'] ?? '');
$account_options = pascco_savings_product_names();

$member_stmt = $pdo->prepare('SELECT id, first_name, last_name FROM members WHERE user_id = ? LIMIT 1');
$member_stmt->execute([$user_id]);
$member = $member_stmt->fetch();

$existing_stmt = $pdo->prepare('SELECT a.account_type FROM accounts a JOIN members m ON m.id = a.member_id WHERE m.user_id = ? AND a.status = "active" ORDER BY a.id');
$existing_stmt->execute([$user_id]);
$existing_accounts = $existing_stmt->fetchAll(PDO::FETCH_COLUMN, 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!$member) {
        $error = 'Member profile not found.';
    } elseif ($account_type === '') {
        $error = 'Please select a savings account type.';
    } elseif (!in_array($account_type, $account_options, true)) {
        $error = 'Invalid account type selected.';
    } elseif (in_array($account_type, $existing_accounts, true)) {
        $error = 'You already have an active account of this type.';
    } else {
        try {
            $insert = $pdo->prepare('INSERT INTO account_requests (member_id, account_type, status) VALUES (?, ?, "pending")');
            $insert->execute([(int) $member['id'], $account_type]);
            record_audit($pdo, $user_id, 'account_request', 'Requested a new savings account: ' . $account_type, 'success');
            $message = 'Your request to open a new savings account has been submitted for admin approval.';
            $account_type = '';
        } catch (Throwable $exception) {
            $error = 'Unable to submit your account request. Please try again later.';
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
    <title>Open Savings Account | PASCCO</title>
    <style>
        body { background: #f4f7fb; color: #182a45; font-family: Georgia, 'Times New Roman', serif; margin: 0; }
        .page { max-width: 900px; margin: 0 auto; padding: 42px 22px 70px; }
        .card { background: white; border-top: 5px solid #1456a0; box-shadow: 0 6px 20px rgba(7,29,73,.08); padding: clamp(22px, 5vw, 38px); }
        .eyebrow { color: #1456a0; font-size: .8rem; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; }
        .grid { display: grid; gap: 22px; grid-template-columns: minmax(0, 1.2fr) minmax(260px, .8fr); }
        .guide { background: #071d49; color: white; padding: 26px; }
        .guide h2 { color: white; }
        .guide ol { line-height: 1.7; margin: 0; padding-left: 20px; }
        label { display: block; color: #071d49; font-weight: bold; margin-bottom: 7px; }
        select, input { border: 1px solid #b8c8d9; border-radius: 4px; font: inherit; font-size: 16px; padding: 12px; width: 100%; }
        button { background: #1456a0; color: white; border: 0; cursor: pointer; font: inherit; font-weight: bold; margin-top: 18px; padding: 14px; width: 100%; }
        .msg { margin-bottom: 18px; padding: 12px; }
        .msg.success { background: #fff4cf; color: #071d49; }
        .msg.error { background: #f8d7da; color: #721c24; }
        .status-box { background: #e6edf8; border-left: 4px solid #e7b84b; margin-top: 16px; padding: 14px; }
        @media (max-width: 720px) { .page { padding: 28px 14px 52px; } .grid { grid-template-columns: 1fr; } .card, .guide { padding: 20px 16px; } }
    </style>
    <link rel="stylesheet" href="member-panels.css?v=2">
</head>
<body>
<?php pascco_member_header('Open Savings Account'); ?>
<main class="page member-content">
    <div class="eyebrow">Member service</div>
    <h1>Open a New Savings Account</h1>
    <div class="grid">
        <section class="card">
            <?php if ($message): ?><div class="msg success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="msg error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

            <form method="POST" action="open_account.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <label for="account-type">Savings Account Type</label>
                <select id="account-type" name="account_type" required>
                    <option value="">Select an account type</option>
                    <?php foreach ($account_options as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $account_type === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Submit Account Request</button>
            </form>

            <div class="status-box">
                <strong>Note:</strong> New account requests must be approved by the admin before the account becomes active.
            </div>
        </section>

        <aside class="guide">
            <h2>Account Opening Steps</h2>
            <ol>
                <li>Select the savings account you want.</li>
                <li>Submit your account request.</li>
                <li>Wait for admin approval.</li>
                <li>Once approved, the account becomes active and available for transactions.</li>
            </ol>
        </aside>
    </div>
</main>
<?php pascco_global_footer(); ?>
</body>
</html>
