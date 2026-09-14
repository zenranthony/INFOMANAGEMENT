<?php
require_once 'db.php';
require_once 'pascco_shell.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'member') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error   = '';
$description_options = [
    'Payment for shared expenses',
    'Family Support',
    'Bills Payment',
    'Loan Payment',
    'Savings Transfer',
    'Other',
];

$account_stmt = $pdo->prepare("
    SELECT a.id AS account_id, a.account_number, a.account_type, a.balance 
    FROM members m 
    JOIN accounts a ON m.id = a.member_id 
    WHERE m.user_id = ? AND a.status = 'active'
    ORDER BY a.id
");
$account_stmt->execute([$user_id]);
$accounts = $account_stmt->fetchAll();
$recipient_stmt = $pdo->prepare("SELECT a.account_number, a.account_type, m.first_name, m.last_name FROM accounts a JOIN members m ON m.id = a.member_id WHERE m.user_id <> ? AND a.status = 'active' ORDER BY m.last_name, m.first_name, a.account_type, a.id");
$recipient_stmt->execute([$user_id]);
$recipient_accounts = $recipient_stmt->fetchAll();
$selected_account_id = (int) ($_POST['account_id'] ?? $_GET['account_id'] ?? ($accounts[0]['account_id'] ?? 0));
$selected_recipient_account = trim($_POST['recipient_account'] ?? '');
$sender = null;
foreach ($accounts as $available_account) {
    if ((int) $available_account['account_id'] === $selected_account_id) {
        $sender = $available_account;
        break;
    }
}

if (!$sender && $accounts) {
    $sender = $accounts[0];
    $selected_account_id = (int) $sender['account_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sender) {
    verify_csrf(); // Verify CSRF Token

    $recipient_acc_num = trim($_POST['recipient_account']);
    $amount            = floatval($_POST['amount']);
    $description       = trim($_POST['description'] ?? '');
    $custom_description = trim($_POST['custom_description'] ?? '');
    $description_text = $description === 'Other' ? $custom_description : $description;
    $posted_account_id = (int) ($_POST['account_id'] ?? 0);

    if ($posted_account_id !== (int) $sender['account_id']) {
        $error = "Please select a valid savings account.";
    } elseif (!in_array($description, $description_options, true)) {
        $error = "Please select a transfer description.";
    } elseif ($description === 'Other' && $custom_description === '') {
        $error = "Please enter a description for the other transfer.";
    }

    $rec_stmt = $pdo->prepare("SELECT a.id AS account_id, m.first_name, m.last_name FROM accounts a JOIN members m ON a.member_id = m.id WHERE a.account_number = ? AND m.user_id <> ? AND a.status = 'active'");
    $rec_stmt->execute([$recipient_acc_num, $user_id]);
    $recipient = $rec_stmt->fetch();

    if ($error !== '') {
        // Validation error already assigned above.
    } elseif ($amount <= 0) {
        $error = "Please enter a valid transfer amount.";
    } elseif ($amount > $sender['balance']) {
        $error = "Insufficient balance to complete this transfer!";
    } elseif (!$recipient) {
        $error = "Recipient account number not found.";
    } elseif ((int) $recipient['account_id'] === (int) $sender['account_id']) {
        $error = "You cannot transfer money to your own account.";
    } else {
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT balance FROM accounts WHERE id = ? AND status = \'active\' FOR UPDATE');
            $lock->execute([(int) $sender['account_id']]);
            $locked_sender = $lock->fetch();
            if (!$locked_sender || $amount > (float) $locked_sender['balance']) {
                throw new RuntimeException('The available balance changed. Please review it and try again.');
            }

            $recipient_lock = $pdo->prepare("SELECT id FROM accounts WHERE id = ? AND status = 'active' FOR UPDATE");
            $recipient_lock->execute([(int) $recipient['account_id']]);
            if (!$recipient_lock->fetch()) {
                throw new RuntimeException('The recipient account is no longer available.');
            }

            $ref_number = 'OR-' . date('YmdHis') . '-' . random_int(100000, 999999);
            $full_desc  = "Transfer to " . $recipient['first_name'] . " " . $recipient['last_name'] . " (" . $description_text . ")";
            $insert = $pdo->prepare("INSERT INTO transactions (account_id, transaction_type, amount, recipient_account_id, description, status, reference_number, transaction_date, created_at) VALUES (?, 'transfer', ?, ?, ?, 'completed', ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
            $insert->execute([$sender['account_id'], $amount, $recipient['account_id'], $full_desc, $ref_number]);
            $incoming = $pdo->prepare("INSERT INTO transactions (account_id, transaction_type, amount, recipient_account_id, description, status, reference_number, transaction_date, created_at) VALUES (?, 'deposit', ?, ?, ?, 'completed', ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
            $incoming->execute([$recipient['account_id'], $amount, $sender['account_id'], 'Transfer from ' . $sender['account_number'], $ref_number . '-IN']);
            $debit = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ? AND status = 'active'");
            $debit->execute([$amount, (int) $sender['account_id']]);
            $credit = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND status = 'active'");
            $credit->execute([$amount, (int) $recipient['account_id']]);
            $pdo->commit();
            record_audit($pdo, $user_id, 'fund_transfer', 'Completed transfer ' . $ref_number, 'success');
            $message = "Transfer of ₱" . number_format($amount, 2) . " to " . $recipient['first_name'] . " " . $recipient['last_name'] . " successful!";

            $account_stmt->execute([$user_id]);
            $accounts = $account_stmt->fetchAll();
            foreach ($accounts as $available_account) {
                if ((int) $available_account['account_id'] === $selected_account_id) {
                    $sender = $available_account;
                    break;
                }
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php pascco_global_styles(); ?>
    <title>Fund Transfer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f7fb; color: #182a45; }
        .navbar { background: linear-gradient(90deg, #071d49, #1456a0); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: #ffdd57; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; max-width: 500px; margin: 0 auto; }
        .card { background: white; border-top: 4px solid #1456a0; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(7,29,73,.1); }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .input-group input, .input-group select { width: 100%; padding: 10px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #1456a0; color: white; border: 1px solid #e7b84b; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .msg.success { background: #fff4cf; color: #071d49; }
        .msg.danger { background: #f8d7da; color: #721c24; }
        .balance-badge { background: #e6edf8; border-left: 4px solid #e7b84b; padding: 10px; border-radius: 4px; text-align: center; margin-bottom: 15px; }
    </style>
    <link rel="stylesheet" href="member-panels.css?v=2">
</head>
<body>

<?php pascco_member_header('Transfer Funds'); ?>

<div class="container member-content">
    <div class="card">
        <h3>Transfer Funds</h3>

        <?php if ($sender): ?>
            <div class="balance-badge">
                Available Balance: <strong>₱<?php echo number_format($sender['balance'], 2); ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($message): ?><div class="msg success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <form method="POST" action="transfer.php">
            <!-- Hidden Security Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="input-group">
                <label for="account-id">Send From</label>
                <select id="account-id" name="account_id" required>
                    <?php foreach ($accounts as $available_account): ?>
                        <option value="<?php echo (int) $available_account['account_id']; ?>" <?php echo (int) $available_account['account_id'] === $selected_account_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($available_account['account_type'] . ' - ' . $available_account['account_number'] . ' (Balance: PHP ' . number_format($available_account['balance'], 2) . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label for="recipient-account">Send To</label>
                <select id="recipient-account" name="recipient_account" required <?php echo $recipient_accounts ? '' : 'disabled'; ?>>
                    <option value="">Choose another member</option>
                    <?php foreach ($recipient_accounts as $recipient_account): ?>
                        <?php $recipient_label = $recipient_account['first_name'] . ' ' . $recipient_account['last_name'] . ' - ' . $recipient_account['account_type'] . ' (' . $recipient_account['account_number'] . ')'; ?>
                        <option value="<?php echo htmlspecialchars($recipient_account['account_number'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected_recipient_account === $recipient_account['account_number'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($recipient_label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$recipient_accounts): ?><small>No other active member accounts are available.</small><?php endif; ?>
            </div>
            <div class="input-group">
                <label>Amount (₱)</label>
                <input type="number" step="0.01" name="amount" placeholder="e.g. 500.00" required>
            </div>
            <div class="input-group">
                <label>Note / Description</label>
                <select name="description" required>
                    <option value="">Select description</option>
                    <?php foreach ($description_options as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="custom_description" placeholder="If Other, enter description">
            </div>
            <button type="submit">Send Money</button>
        </form>
    </div>
</div>

<?php pascco_global_footer(); ?>

</body>
</html>