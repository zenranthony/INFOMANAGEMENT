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

// Load every active savings account owned by the logged-in member.
$account_stmt = $pdo->prepare("
    SELECT 
        m.id AS member_id,
        m.first_name, 
        m.last_name, 
        m.member_number, 
        a.id AS account_id,
        a.account_number, 
        a.account_type, 
        a.balance 
    FROM members m
    JOIN accounts a ON m.id = a.member_id
    WHERE m.user_id = ? AND a.status = 'active'
    ORDER BY a.id
");
$account_stmt->execute([$user_id]);
$accounts = $account_stmt->fetchAll();

$selected_account_id = (int) ($_POST['account_id'] ?? $_GET['account_id'] ?? ($accounts[0]['account_id'] ?? 0));
$account = null;
foreach ($accounts as $available_account) {
    if ((int) $available_account['account_id'] === $selected_account_id) {
        $account = $available_account;
        break;
    }
}

if (!$account && $accounts) {
    $account = $accounts[0];
    $selected_account_id = (int) $account['account_id'];
}

// Fetch Active Approved Loans
$active_loans = [];
if ($account) {
    $loan_stmt = $pdo->prepare("SELECT * FROM loans WHERE member_id = ? AND status = 'approved' AND remaining_balance > 0");
    $loan_stmt->execute([$account['member_id']]);
    $active_loans = $loan_stmt->fetchAll();
}

// Handle Quick Transactions (Deposit/Withdrawal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'transact') {
    verify_csrf();
    $type   = $_POST['transaction_type'];
    $amount = floatval($_POST['amount']);
    $description_options = [
        'Counter Deposit',
        'Counter Withdrawal',
        'Monthly Savings',
        'Salary Savings',
        'Bills Payment',
        'Loan Payment',
        'Other',
    ];
    $description = trim($_POST['description'] ?? '');
    $custom_description = trim($_POST['custom_description'] ?? '');
    $desc = $description === 'Other' ? $custom_description : $description;
    $posted_account_id = (int) ($_POST['account_id'] ?? 0);

    if (!in_array($type, ['deposit', 'withdrawal'], true)) {
        $error = "Invalid transaction type.";
    } elseif (!in_array($description, $description_options, true)) {
        $error = "Please select a transaction description.";
    } elseif ($description === 'Other' && $custom_description === '') {
        $error = "Please enter a description for the other transaction.";
    } elseif (!$account || $posted_account_id !== (int) $account['account_id']) {
        $error = "Please select a valid savings account.";
    } elseif ($amount <= 0) {
        $error = "Please enter a valid amount greater than zero.";
    } elseif ($type === 'withdrawal' && $amount > $account['balance']) {
        $error = "Insufficient balance for this withdrawal!";
    } else {
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT balance FROM accounts WHERE id = ? AND status = \'active\' FOR UPDATE');
            $lock->execute([(int) $account['account_id']]);
            $locked_account = $lock->fetch();
            if (!$locked_account || ($type === 'withdrawal' && $amount > (float) $locked_account['balance'])) {
                throw new RuntimeException('The available balance changed. Please review it and try again.');
            }

            $ref_number = 'OR-' . date('YmdHis') . '-' . random_int(100000, 999999);
            $insert = $pdo->prepare("INSERT INTO transactions (account_id, transaction_type, amount, recipient_account_id, description, status, reference_number, transaction_date, created_at) VALUES (?, ?, ?, NULL, ?, 'completed', ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
            $insert->execute([$account['account_id'], $type, $amount, $desc, $ref_number]);
            $balance_delta = $type === 'deposit' ? $amount : -$amount;
            $balance_update = $pdo->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ? AND status = \'active\'');
            $balance_update->execute([$balance_delta, (int) $account['account_id']]);
            $pdo->commit();
            $message = "Transaction successful! " . ucfirst($type) . " of ₱" . number_format($amount, 2) . " processed.";

            $account_stmt->execute([$user_id]);
            $accounts = $account_stmt->fetchAll();
            foreach ($accounts as $available_account) {
                if ((int) $available_account['account_id'] === $selected_account_id) {
                    $account = $available_account;
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

// Handle Loan Repayment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'repay_loan') {
    verify_csrf();
    $loan_id = intval($_POST['loan_id']);
    $amount  = floatval($_POST['repay_amount']);

    // Find selected loan
    $l_check = $pdo->prepare("SELECT * FROM loans WHERE id = ? AND member_id = ?");
    $l_check->execute([$loan_id, $account['member_id']]);
    $selected_loan = $l_check->fetch();

    if (!$selected_loan) {
        $error = "Invalid loan selected.";
    } elseif ($amount <= 0) {
        $error = "Please enter a valid repayment amount.";
    } elseif ($amount > $account['balance']) {
        $error = "Insufficient savings balance to cover this repayment!";
    } elseif ($amount > $selected_loan['remaining_balance']) {
        $error = "Payment amount exceeds the remaining loan balance of ₱" . number_format($selected_loan['remaining_balance'], 2);
    } else {
        try {
            $pdo->beginTransaction();
            $lock_account = $pdo->prepare('SELECT balance FROM accounts WHERE id = ? AND status = \'active\' FOR UPDATE');
            $lock_account->execute([(int) $account['account_id']]);
            $locked_account = $lock_account->fetch();
            $lock_loan = $pdo->prepare("SELECT remaining_balance FROM loans WHERE id = ? AND member_id = ? AND status = 'approved' FOR UPDATE");
            $lock_loan->execute([$loan_id, $account['member_id']]);
            $locked_loan = $lock_loan->fetch();
            if (!$locked_account || !$locked_loan || $amount > (float) $locked_account['balance'] || $amount > (float) $locked_loan['remaining_balance']) {
                throw new RuntimeException('The balance or loan amount changed. Please review the payment and try again.');
            }

            $ref_number = 'OR-' . date('YmdHis') . '-' . random_int(100000, 999999);
            $tx = $pdo->prepare("INSERT INTO transactions (account_id, transaction_type, amount, recipient_account_id, description, status, reference_number, transaction_date, created_at) VALUES (?, 'withdrawal', ?, NULL, 'Loan Payment', 'completed', ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
            $tx->execute([$account['account_id'], $amount, $ref_number]);
            $balance_update = $pdo->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ? AND status = \'active\'');
            $balance_update->execute([$amount, (int) $account['account_id']]);
            $new_rem = (float) $locked_loan['remaining_balance'] - $amount;
            $new_status = $new_rem == 0.0 ? 'paid' : 'approved';
            $up_loan = $pdo->prepare('UPDATE loans SET remaining_balance = ?, status = ? WHERE id = ?');
            $up_loan->execute([$new_rem, $new_status, $loan_id]);
            $pdo->commit();
            $message = "Loan payment of ₱" . number_format($amount, 2) . " successful!";

        // Refresh data
        $account_stmt->execute([$user_id]);
        $accounts = $account_stmt->fetchAll();
        foreach ($accounts as $available_account) {
            if ((int) $available_account['account_id'] === $selected_account_id) {
                $account = $available_account;
                break;
            }
        }
            $loan_stmt->execute([$account['member_id']]);
            $active_loans = $loan_stmt->fetchAll();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        }
    }
}

// Fetch Recent Transactions
$transactions = [];
if ($account) {
    $tx_stmt = $pdo->prepare("
        SELECT transaction_type, amount, description, reference_number, created_at 
        FROM transactions 
        WHERE account_id = ? 
        ORDER BY id DESC LIMIT 5
    ");
    $tx_stmt->execute([$account['account_id']]);
    $transactions = $tx_stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Dashboard</title>
    <?php pascco_global_styles(); ?>
    <style>
        :root { --deep-blue: #071d49; --blue: #1456a0; --gold: #e7b84b; --cream: #f4f7fb; --ink: #182a45; --line: #d8e0ee; }
        * { box-sizing: border-box; }
        body { font-family: Georgia, 'Times New Roman', serif; margin: 0; background: var(--cream); color: var(--ink); }
        .navbar { align-items: center; background: linear-gradient(90deg, var(--deep-blue), var(--blue)); color: white; display: flex; gap: 24px; justify-content: space-between; padding: 12px clamp(18px, 5vw, 72px); }
        .brand { align-items: center; color: white; display: flex; gap: 10px; margin-right: auto; text-decoration: none; }
        .brand img { height: 48px; object-fit: contain; width: 48px; }
        .brand strong { font-family: Impact, 'Arial Black', sans-serif; font-size: 1.35rem; letter-spacing: .06em; }
        .nav-links { align-items: center; display: flex; flex-wrap: wrap; gap: 8px; }
        .navbar a { color: white; padding: 8px 10px; text-decoration: none; }
        .nav-links a:hover, .logout:hover { color: var(--gold); }
        .logout { border: 1px solid var(--gold); color: #ffe7a1 !important; }
        .container { margin: 0 auto; max-width: 1120px; padding: 42px 22px 70px; }
        .welcome { align-items: end; display: flex; justify-content: space-between; margin-bottom: 24px; }
        .eyebrow { color: var(--blue); font-size: .8rem; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; }
        .welcome h1 { color: var(--deep-blue); font-size: clamp(2rem, 5vw, 3.4rem); margin: 8px 0 0; }
        .member-number { color: #5d6b7e; font-size: .9rem; }
        .quick-actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .quick-actions a { background: var(--gold); color: var(--deep-blue); font-weight: bold; padding: 12px 15px; text-decoration: none; }
        .quick-actions a.secondary { background: var(--blue); color: white; }
        .card { background: white; border: 1px solid var(--line); border-top: 4px solid var(--blue); padding: 25px; border-radius: 6px; box-shadow: 0 4px 14px rgba(7,29,73,.08); margin-bottom: 20px; }
        .balance-box h2 { margin: 8px 0 0; font-size: clamp(2rem, 5vw, 3rem); }
        .account-overview { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 20px; }
        .account-summary { background: white; border: 1px solid var(--line); border-left: 4px solid var(--gold); padding: 18px; }
        .account-summary h3 { color: var(--deep-blue); font-size: 1rem; margin: 0 0 8px; }
        .account-summary strong { color: var(--blue); display: block; font-size: 1.35rem; margin: 8px 0; }
        .account-summary small { color: #5d6b7e; }
        .account-summary a { color: var(--blue); font-weight: bold; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 15px; font-weight: bold; text-align: center; }
        .msg.success { background: #fff4cf; color: #071d49; }
        .msg.danger { background: #f8d7da; color: #721c24; }
        .form-grid { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .form-grid input, .form-grid select, .form-grid button { padding: 10px; font-size: 14px; }
        .form-grid input { flex: 1; }
        .form-grid button { background: #1456a0; color: white; border: 1px solid #e7b84b; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid var(--line); }
        th { background: #e6edf8; color: #071d49; }
        .badge-deposit { color: #1456a0; font-weight: bold; }
        .badge-withdrawal { color: #9b3d3d; font-weight: bold; }
        @media (max-width: 760px) {
            .navbar { align-items: flex-start; flex-wrap: wrap; }
            .nav-links { justify-content: flex-start; order: 3; width: 100%; }
            .welcome { align-items: flex-start; flex-direction: column; gap: 18px; }
            .quick-actions { width: 100%; }
            .quick-actions a { flex: 1; text-align: center; }
            .card { overflow-x: auto; }
        }
    </style>
    <link rel="stylesheet" href="member-panels.css?v=2">
</head>
<body>

<?php pascco_member_header('Member Home'); ?>


<div class="container member-content">

    <?php if ($account): ?>
        <section class="welcome">
            <div><div class="eyebrow">Member home</div><h1>Welcome, <?php echo htmlspecialchars($account['first_name']); ?>.</h1><div class="member-number">Member No. <?php echo htmlspecialchars($account['member_number']); ?></div></div>
            <div class="quick-actions"><a href="apply_loan.php">Apply for Loan</a><a class="secondary" href="deposit.php">Deposit Money</a><a class="secondary" href="transfer.php">Send Money</a></div>
        </section>
    <?php endif; ?>

    <?php if ($message): ?><div class="msg success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <?php if ($account): ?>
        <div class="card balance-box">
            <small>Available Balance</small>
            <h2>₱<?php echo number_format($account['balance'], 2); ?></h2>
        </div>

        <section class="account-overview" aria-label="All savings account balances">
            <?php foreach ($accounts as $available_account): ?>
                <article class="account-summary">
                    <h3><?php echo htmlspecialchars($available_account['account_type'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <small><?php echo htmlspecialchars($available_account['account_number'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <strong>₱<?php echo number_format($available_account['balance'], 2); ?></strong>
                    <a href="member_savings.php?account_id=<?php echo (int) $available_account['account_id']; ?>">View history</a>
                </article>
            <?php endforeach; ?>
        </section>

        <!-- Active Loans & Repayment Form -->
        <?php if (count($active_loans) > 0): ?>
            <div class="card loan-repayments">
                <h3>Active Loans</h3>
                <?php foreach ($active_loans as $ln): ?>
                    <p><strong>Loan #:</strong> <?php echo htmlspecialchars($ln['loan_number']); ?> | 
                       <strong>Original Amount:</strong> ₱<?php echo number_format($ln['loan_amount'], 2); ?> | 
                       <span style="color: #071d49;"><strong>Remaining Balance:</strong> ₱<?php echo number_format($ln['remaining_balance'], 2); ?></span>
                    </p>
                    
                    <form method="POST" action="dashboard.php" class="form-grid">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="repay_loan">
                        <input type="hidden" name="loan_id" value="<?php echo $ln['id']; ?>">
                        <input type="number" step="0.01" name="repay_amount" placeholder="Repayment Amount (₱)" required>
                        <button type="submit" style="background: #1456a0; border: 1px solid #e7b84b;">Make Repayment</button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Quick Transaction Form -->
        <div class="card">
            <h3>Quick Transaction</h3>
            <form method="POST" action="dashboard.php" class="form-grid">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="transact">
                <select name="account_id" required aria-label="Savings account">
                    <?php foreach ($accounts as $available_account): ?>
                        <option value="<?php echo (int) $available_account['account_id']; ?>" <?php echo (int) $available_account['account_id'] === $selected_account_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($available_account['account_type'] . ' - ' . $available_account['account_number'] . ' (Balance: PHP ' . number_format($available_account['balance'], 2) . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="transaction_type" required>
                    <option value="deposit">Deposit</option>
                    <option value="withdrawal">Withdrawal</option>
                </select>
                <input type="number" step="0.01" name="amount" placeholder="Amount (₱)" required>
                <select name="description" required aria-label="Transaction description">
                    <option value="">Select description</option>
                    <option value="Counter Deposit">Counter Deposit</option>
                    <option value="Counter Withdrawal">Counter Withdrawal</option>
                    <option value="Monthly Savings">Monthly Savings</option>
                    <option value="Salary Savings">Salary Savings</option>
                    <option value="Bills Payment">Bills Payment</option>
                    <option value="Loan Payment">Loan Payment</option>
                    <option value="Other">Other</option>
                </select>
                <input type="text" name="custom_description" placeholder="If Other, enter description">
                <button type="submit">Submit Transaction</button>
            </form>
        </div>

        <!-- Transaction History -->
        <div class="card">
            <h3>Recent Transactions</h3>
            <?php if (count($transactions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>OR Number</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td><small><?php echo htmlspecialchars($tx['reference_number']); ?></small></td>
                                <td class="badge-<?php echo $tx['transaction_type']; ?>">
                                    <?php echo strtoupper($tx['transaction_type']); ?>
                                </td>
                                <td>₱<?php echo number_format($tx['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($tx['description']); ?></td>
                                <td><?php echo date('M d, Y H:i', $tx['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No transactions found.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Welcome, Admin!</h3>
            <p>You are logged in as administrator. Go to <a href="admin_loans.php">Admin Loans Page</a> to manage applications.</p>
        </div>
    <?php endif; ?>

</div>

<?php pascco_global_footer(); ?>

</body>
</html>