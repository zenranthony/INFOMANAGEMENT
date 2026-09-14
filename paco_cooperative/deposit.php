<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pascco_shell.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'member') {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$message = '';
$error = '';
$submitted_request_id = 0;
$payment_methods = ['GCash', 'Maya', 'ShopeePay', 'GrabPay', 'Other E-Wallet'];
$upload_directory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'deposit_receipts';
if (!is_dir($upload_directory)) {
    mkdir($upload_directory, 0700, true);
}

$account_stmt = $pdo->prepare("SELECT a.id AS account_id, a.account_number, a.account_type, a.balance FROM members m JOIN accounts a ON a.member_id = m.id WHERE m.user_id = ? AND a.status = 'active' ORDER BY a.id");
$account_stmt->execute([$user_id]);
$accounts = $account_stmt->fetchAll();
$request_stmt = $pdo->prepare("SELECT d.id, d.amount, d.payment_method, d.payment_reference, d.status, d.admin_notes, d.created_at, a.account_number FROM deposit_requests d JOIN accounts a ON a.id = d.account_id JOIN members m ON m.id = d.member_id WHERE m.user_id = ? ORDER BY d.id DESC LIMIT 5");
$request_stmt->execute([$user_id]);
$deposit_requests = $request_stmt->fetchAll();
$selected_account_id = (int) ($_POST['account_id'] ?? ($accounts[0]['account_id'] ?? 0));
$amount = trim($_POST['amount'] ?? '');
$description = trim($_POST['description'] ?? '');
$custom_description = trim($_POST['custom_description'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');
$payment_reference = trim($_POST['payment_reference'] ?? '');
$description_options = ['Counter Deposit', 'Monthly Savings', 'Salary Savings', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $selected_account = null;
    foreach ($accounts as $account) {
        if ((int) $account['account_id'] === $selected_account_id) {
            $selected_account = $account;
            break;
        }
    }
    $deposit_description = $description === 'Other' ? $custom_description : $description;
    $receipt_path = null;
    if (!$selected_account) {
        $error = 'Please select one of your active savings accounts.';
    } elseif (!is_numeric($amount) || (float) $amount <= 0) {
        $error = 'Please enter a deposit amount greater than zero.';
    } elseif (!in_array($payment_method, $payment_methods, true)) {
        $error = 'Please select the e-wallet used for the deposit.';
    } elseif ($payment_reference === '') {
        $error = 'Please enter the e-wallet reference number.';
    } elseif (!in_array($description, $description_options, true) || ($description === 'Other' && $custom_description === '')) {
        $error = 'Please select or enter a valid deposit description.';
    } elseif (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please upload the e-wallet receipt.';
    } else {
        try {
            $receipt = $_FILES['receipt'];
            $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            $mime_type = (new finfo(FILEINFO_MIME_TYPE))->file($receipt['tmp_name']);
            if ($receipt['error'] !== UPLOAD_ERR_OK || !isset($allowed_types[$mime_type]) || $receipt['size'] > 5 * 1024 * 1024) {
                throw new RuntimeException('Receipt must be a JPG or PNG image up to 5 MB.');
            }
            $receipt_name = bin2hex(random_bytes(24)) . '.' . $allowed_types[$mime_type];
            if (!move_uploaded_file($receipt['tmp_name'], $upload_directory . DIRECTORY_SEPARATOR . $receipt_name)) {
                throw new RuntimeException('The receipt could not be saved.');
            }
            $receipt_path = 'uploads/deposit_receipts/' . $receipt_name;
            $member_stmt = $pdo->prepare('SELECT m.id FROM members m JOIN accounts a ON a.member_id = m.id WHERE m.user_id = ? AND a.id = ? AND a.status = \'active\'');
            $member_stmt->execute([$user_id, $selected_account_id]);
            $member = $member_stmt->fetch();
            if (!$member) {
                throw new RuntimeException('The selected savings account is not available.');
            }
            $insert = $pdo->prepare("INSERT INTO deposit_requests (account_id, member_id, amount, payment_method, payment_reference, description, receipt_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([$selected_account_id, $member['id'], (float) $amount, $payment_method, $payment_reference, $deposit_description, $receipt_path]);
            $submitted_request_id = (int) $pdo->lastInsertId();
            record_audit($pdo, $user_id, 'deposit_request', 'Submitted deposit request ' . $submitted_request_id, 'success');
            $message = 'Deposit submitted for admin review. Your balance will update after approval.';
            $amount = '';
            $description = '';
            $custom_description = '';
            $payment_method = '';
            $payment_reference = '';
            $account_stmt->execute([$user_id]);
            $accounts = $account_stmt->fetchAll();
        } catch (Throwable $exception) {
            if ($receipt_path) {
                $absolute_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $receipt_path);
                if (is_file($absolute_path)) {
                    unlink($absolute_path);
                }
            }
            $error = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?><title>Deposit Money | PASCCO</title>
    <style>
        * { box-sizing: border-box; }
        body { background: #f4f7fb; color: #182a45; font-family: Georgia, 'Times New Roman', serif; margin: 0; }
        .deposit-page { margin: 0 auto; max-width: 900px; padding: 44px 22px 70px; }
        .deposit-layout { display: grid; gap: 22px; grid-template-columns: minmax(0, 1fr) 280px; }
        .card { background: white; border-top: 5px solid #1456a0; box-shadow: 0 6px 20px rgba(7,29,73,.1); padding: clamp(24px, 5vw, 42px); }
        .guide { background: #071d49; color: white; padding: 26px; }
        .guide h2 { color: white; }.guide li { line-height: 1.6; margin-bottom: 12px; }
        label { color: #071d49; display: block; font-weight: bold; margin: 16px 0 7px; }
        input, select { border: 1px solid #b8c8d9; border-radius: 3px; font: inherit; font-size: 16px; padding: 12px; width: 100%; }
        button { background: #1456a0; border: 0; color: white; cursor: pointer; font: inherit; font-weight: bold; margin-top: 22px; padding: 14px; width: 100%; }
        .notice, .error { margin-bottom: 18px; padding: 12px; }.notice { background: #fff4cf; color: #071d49; }.error { background: #f8d7da; color: #721c24; }
        .account-balance { background: #e6edf8; border-left: 4px solid #e7b84b; margin-top: 8px; padding: 12px; }
        @media (max-width: 720px) { .deposit-page { padding: 28px 14px 52px; } .deposit-layout { grid-template-columns: 1fr; } .card { padding: 20px 16px; } .guide { padding: 18px 16px; } }
    </style>
    <link rel="stylesheet" href="member-panels.css?v=2">
</head>
<body>
<?php pascco_member_header('Deposit Money'); ?>
<main class="deposit-page member-content">
    <div class="eyebrow">Member service</div><h1>Deposit Money</h1>
    <div class="deposit-layout">
        <section class="card">
            <?php if ($message): ?><div class="notice" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?><?php if ($submitted_request_id): ?> Request #<?php echo $submitted_request_id; ?><?php endif; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="POST" action="deposit.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <label for="account-id">Deposit To</label>
                <select id="account-id" name="account_id" required><?php foreach ($accounts as $account): ?><option value="<?php echo (int) $account['account_id']; ?>" <?php echo (int) $account['account_id'] === $selected_account_id ? 'selected' : ''; ?>><?php echo htmlspecialchars($account['account_type'] . ' - ' . $account['account_number'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select>
                <?php foreach ($accounts as $account): ?><?php if ((int) $account['account_id'] === $selected_account_id): ?><div class="account-balance">Current balance: <strong>PHP <?php echo number_format($account['balance'], 2); ?></strong></div><?php endif; ?><?php endforeach; ?>
                <label for="amount">Deposit Amount (PHP)</label><input id="amount" type="number" name="amount" min="0.01" step="0.01" value="<?php echo htmlspecialchars($amount, ENT_QUOTES, 'UTF-8'); ?>" required>
                <label for="payment-method">E-Wallet Used</label><select id="payment-method" name="payment_method" required><option value="">Select e-wallet</option><?php foreach ($payment_methods as $method): ?><option value="<?php echo htmlspecialchars($method, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $payment_method === $method ? 'selected' : ''; ?>><?php echo htmlspecialchars($method, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select>
                <label for="payment-reference">E-Wallet Reference Number</label><input id="payment-reference" type="text" name="payment_reference" value="<?php echo htmlspecialchars($payment_reference, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter the reference number from the transfer or receipt" required>
                <label for="receipt">Upload Receipt</label><input id="receipt" type="file" name="receipt" accept="image/jpeg,image/png" required><small>JPG or PNG up to 5 MB.</small>
                <label for="description">Deposit Purpose</label><select id="description" name="description" required><option value="">Select purpose</option><?php foreach ($description_options as $option): ?><option value="<?php echo $option; ?>" <?php echo $description === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?></select>
                <label for="custom-description">Additional Details</label><input id="custom-description" name="custom_description" value="<?php echo htmlspecialchars($custom_description, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Required when purpose is Other">
                <button type="submit">Record Deposit</button>
            </form>
        </section>
        <aside class="guide"><h2>Deposit Steps</h2><ol><li>Select the savings account.</li><li>Send the amount using GCash or another e-wallet.</li><li>Enter the reference number manually and upload the receipt.</li><li>Wait for admin approval before the balance updates.</li></ol></aside>
    </div>
    <?php if ($deposit_requests): ?>
        <section class="card" style="margin-top:22px"><h2>Recent Deposit Requests</h2><div style="overflow-x:auto"><table style="border-collapse:collapse;width:100%"><thead><tr><th>Request</th><th>Account</th><th>Amount</th><th>Method</th><th>Status</th><th>Submitted</th></tr></thead><tbody><?php foreach ($deposit_requests as $request): ?><tr><td>#<?php echo (int) $request['id']; ?><br><small><?php echo htmlspecialchars($request['payment_reference'], ENT_QUOTES, 'UTF-8'); ?></small></td><td><?php echo htmlspecialchars($request['account_number'], ENT_QUOTES, 'UTF-8'); ?></td><td>PHP <?php echo number_format($request['amount'], 2); ?></td><td><?php echo htmlspecialchars($request['payment_method'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo strtoupper(htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8')); ?><?php if ($request['admin_notes']): ?><br><small><?php echo htmlspecialchars($request['admin_notes'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?></td><td><?php echo htmlspecialchars($request['created_at'], ENT_QUOTES, 'UTF-8'); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
    <?php endif; ?>
</main>
<?php pascco_global_footer(); ?>
</body>
</html>
