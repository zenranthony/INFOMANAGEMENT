<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error   = '';

// Fetch Member and Account Details
$stmt = $pdo->prepare("
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
    WHERE m.user_id = ?
");
$stmt->execute([$user_id]);
$account = $stmt->fetch();

// Fetch Active Approved Loans
$active_loans = [];
if ($account) {
    $loan_stmt = $pdo->prepare("SELECT * FROM loans WHERE member_id = ? AND status = 'approved' AND remaining_balance > 0");
    $loan_stmt->execute([$account['member_id']]);
    $active_loans = $loan_stmt->fetchAll();
}

// Handle Quick Transactions (Deposit/Withdrawal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'transact') {
    $type   = $_POST['transaction_type'];
    $amount = floatval($_POST['amount']);
    $desc   = trim($_POST['description']);

    if ($amount <= 0) {
        $error = "Please enter a valid amount greater than zero.";
    } elseif ($type === 'withdrawal' && $amount > $account['balance']) {
        $error = "Insufficient balance for this withdrawal!";
    } else {
        $ref_number = 'TXN-' . date('YmdHis') . '-' . rand(100, 999);
        $insert = $pdo->prepare("
            INSERT INTO transactions (account_id, transaction_type, amount, recipient_account_id, description, status, reference_number, transaction_date, created_at) 
            VALUES (?, ?, ?, NULL, ?, 'completed', ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
        ");
        $insert->execute([$account['account_id'], $type, $amount, $desc, $ref_number]);
        $message = "Transaction successful! " . ucfirst($type) . " of ₱" . number_format($amount, 2) . " processed.";

        $stmt->execute([$user_id]);
        $account = $stmt->fetch();
    }
}

// Handle Loan Repayment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'repay_loan') {
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
        // 1. Deduct funds from savings (Withdrawal transaction trigger updates account balance)
        $ref_number = 'REPAY-' . date('YmdHis') . '-' . rand(100, 999);
        $tx = $pdo->prepare("
            INSERT INTO transactions (account_id, transaction_type, amount, recipient_account_id, description, status, reference_number, transaction_date, created_at) 
            VALUES (?, 'withdrawal', ?, NULL, 'Loan Payment', 'completed', ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
        ");
        $tx->execute([$account['account_id'], $amount, $ref_number]);

        // 2. Reduce loan remaining balance
        $new_rem = $selected_loan['remaining_balance'] - $amount;
        $new_status = ($new_rem == 0) ? 'paid' : 'approved';

        $up_loan = $pdo->prepare("UPDATE loans SET remaining_balance = ?, status = ? WHERE id = ?");
        $up_loan->execute([$new_rem, $new_status, $loan_id]);

        $message = "Loan payment of ₱" . number_format($amount, 2) . " successful!";

        // Refresh data
        $stmt->execute([$user_id]);
        $account = $stmt->fetch();
        $loan_stmt->execute([$account['member_id']]);
        $active_loans = $loan_stmt->fetchAll();
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
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f9; color: #333; }
        .navbar { background: #0056b3; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: #ffdd57; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; max-width: 850px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .balance-box { background: #28a745; color: white; padding: 20px; border-radius: 6px; text-align: center; }
        .balance-box h2 { margin: 0; font-size: 36px; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 15px; font-weight: bold; text-align: center; }
        .msg.success { background: #d4edda; color: #155724; }
        .msg.danger { background: #f8d7da; color: #721c24; }
        .form-grid { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .form-grid input, .form-grid select, .form-grid button { padding: 10px; font-size: 14px; }
        .form-grid input { flex: 1; }
        .form-grid button { background: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .badge-deposit { color: green; font-weight: bold; }
        .badge-withdrawal { color: red; font-weight: bold; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>Paco Cooperative Portal</h2>
    <div>
        <a href="transfer.php" style="margin-right: 15px;">Transfer Funds</a>
        <a href="apply_loan.php" style="margin-right: 15px;">Apply for Loan</a>
        Logged in as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> | 
        <a href="logout.php">Logout</a>
    </div>
</div>
</div>

<div class="container">

    <?php if ($message): ?><div class="msg success"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg danger"><?php echo $error; ?></div><?php endif; ?>

    <?php if ($account): ?>
        <div class="card balance-box">
            <small>Available Balance</small>
            <h2>₱<?php echo number_format($account['balance'], 2); ?></h2>
        </div>

        <!-- Active Loans & Repayment Form -->
        <?php if (count($active_loans) > 0): ?>
            <div class="card" style="border-left: 5px solid #ffc107;">
                <h3>Active Loans</h3>
                <?php foreach ($active_loans as $ln): ?>
                    <p><strong>Loan #:</strong> <?php echo htmlspecialchars($ln['loan_number']); ?> | 
                       <strong>Original Amount:</strong> ₱<?php echo number_format($ln['loan_amount'], 2); ?> | 
                       <span style="color: #d9534f;"><strong>Remaining Balance:</strong> ₱<?php echo number_format($ln['remaining_balance'], 2); ?></span>
                    </p>
                    
                    <form method="POST" action="dashboard.php" class="form-grid">
                        <input type="hidden" name="action" value="repay_loan">
                        <input type="hidden" name="loan_id" value="<?php echo $ln['id']; ?>">
                        <input type="number" step="0.01" name="repay_amount" placeholder="Repayment Amount (₱)" required>
                        <button type="submit" style="background: #28a745;">Make Repayment</button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Quick Transaction Form -->
        <div class="card">
            <h3>Quick Transaction</h3>
            <form method="POST" action="dashboard.php" class="form-grid">
                <input type="hidden" name="action" value="transact">
                <select name="transaction_type" required>
                    <option value="deposit">Deposit</option>
                    <option value="withdrawal">Withdrawal</option>
                </select>
                <input type="number" step="0.01" name="amount" placeholder="Amount (₱)" required>
                <input type="text" name="description" placeholder="Description (e.g. Counter Deposit)" required>
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
                            <th>Reference #</th>
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

</body>
</html>