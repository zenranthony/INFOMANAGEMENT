<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pascco_shell.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'member') {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$account_stmt = $pdo->prepare("
    SELECT a.id AS account_id, a.account_number, a.account_type, a.balance, a.status
    FROM members m
    JOIN accounts a ON a.member_id = m.id
    WHERE m.user_id = ? AND a.status = 'active'
    ORDER BY a.id
");
$account_stmt->execute([$user_id]);
$accounts = $account_stmt->fetchAll();

$selected_account_id = (int) ($_GET['account_id'] ?? ($accounts[0]['account_id'] ?? 0));
$selected_account = null;
foreach ($accounts as $account) {
    if ((int) $account['account_id'] === $selected_account_id) {
        $selected_account = $account;
        break;
    }
}

$from_date = trim($_GET['from'] ?? '');
$to_date = trim($_GET['to'] ?? '');
$transaction_type = trim($_GET['type'] ?? '');
$transactions = [];
if ($selected_account) {
    $conditions = ['account_id = ?'];
    $parameters = [(int) $selected_account['account_id']];
    if ($from_date !== '' && DateTime::createFromFormat('Y-m-d', $from_date)) {
        $conditions[] = 'FROM_UNIXTIME(created_at) >= ?';
        $parameters[] = $from_date . ' 00:00:00';
    }
    if ($to_date !== '' && DateTime::createFromFormat('Y-m-d', $to_date)) {
        $conditions[] = 'FROM_UNIXTIME(created_at) <= ?';
        $parameters[] = $to_date . ' 23:59:59';
    }
    if (in_array($transaction_type, ['deposit', 'withdrawal', 'transfer'], true)) {
        $conditions[] = 'transaction_type = ?';
        $parameters[] = $transaction_type;
    }
    $history_stmt = $pdo->prepare('SELECT transaction_type, amount, description, reference_number, created_at FROM transactions WHERE ' . implode(' AND ', $conditions) . ' ORDER BY id DESC LIMIT 500');
    $history_stmt->execute($parameters);
    $transactions = $history_stmt->fetchAll();

    if (($_GET['download'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pascco-statement-' . $selected_account['account_number'] . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Reference', 'Type', 'Amount', 'Description', 'Date']);
        foreach ($transactions as $transaction) {
            fputcsv($output, [$transaction['reference_number'], $transaction['transaction_type'], $transaction['amount'], $transaction['description'], date('Y-m-d H:i', (int) $transaction['created_at'])]);
        }
        fclose($output);
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?>
    <title>My Savings | PASCCO</title>
    <style>
        body { background: #f4f7fb; color: #182a45; font-family: Georgia, 'Times New Roman', serif; margin: 0; }
        .savings-page { margin: 0 auto; max-width: 1120px; padding: 42px 22px 70px; }
        .heading { align-items: end; display: flex; justify-content: space-between; margin-bottom: 24px; }
        .heading h1 { color: #071d49; margin: 8px 0 0; }
        .eyebrow { color: #1456a0; font-size: .8rem; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; }
        .accounts { display: grid; gap: 18px; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); margin-bottom: 26px; }
        .account-card { background: white; border: 1px solid #d8e0ee; border-top: 4px solid #1456a0; padding: 20px; }
        .account-card.selected { border-top-color: #e7b84b; }
        .account-card h2 { color: #071d49; font-size: 1.2rem; margin: 0 0 10px; }
        .account-card strong { color: #1456a0; display: block; font-size: 1.7rem; margin: 10px 0 16px; }
        .account-card a { color: #1456a0; font-weight: bold; }
        .history { background: white; border: 1px solid #d8e0ee; border-top: 4px solid #1456a0; padding: 24px; }
        .history h2 { color: #071d49; margin-top: 0; }
        .table-wrap { overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #d8e0ee; padding: 12px 10px; text-align: left; }
        th { background: #e6edf8; color: #071d49; }
        .deposit { color: #1456a0; font-weight: bold; }
        .withdrawal, .transfer { color: #9b3d3d; font-weight: bold; }
        .empty { color: #5d6b7e; }
        
        /* Standard Blue Button Styling matching site theme */
        .btn-action, 
        .history button[type="submit"] {
            background: linear-gradient(90deg, #1456a0, #071d49) !important;
            border: 1px solid rgba(231,184,75,.7) !important;
            border-radius: 8px !important;
            color: white !important;
            cursor: pointer !important;
            font-family: Georgia, 'Times New Roman', serif !important;
            font-weight: bold !important;
            padding: 8px 16px !important;
            text-decoration: none !important;
            display: inline-block !important;
            transition: all 0.2s ease !important;
        }
        .btn-action:hover, 
        .history button[type="submit"]:hover {
            color: #e7b84b !important;
            filter: brightness(1.15) !important;
        }

        /* Filter Form Layout */
        .history form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 22px;
            align-items: flex-end;
        }
        .history form label {
            display: flex;
            flex-direction: column;
            font-size: 0.85rem;
            font-weight: bold;
            color: #071d49;
            flex: 1 1 150px;
        }
        .history form label input,
        .history form label select {
            margin-top: 5px;
        }
        @media (max-width: 650px) { .heading { align-items: flex-start; flex-direction: column; gap: 12px; } }
    </style>
    <link rel="stylesheet" href="member-panels.css?v=2">
</head>
<body>
<?php pascco_member_header('My Savings'); ?>
<main class="savings-page member-content">
    <section class="heading">
        <div><div class="eyebrow">Member accounts</div><h1>My Savings</h1></div>
        <a href="dashboard.php">Back to Dashboard</a>
    </section>

    <?php if ($accounts): ?>
        <section class="accounts" aria-label="Savings accounts">
            <?php foreach ($accounts as $account): ?>
                <article class="account-card <?php echo (int) $account['account_id'] === $selected_account_id ? 'selected' : ''; ?>">
                    <h2><?php echo htmlspecialchars($account['account_type'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <small><?php echo htmlspecialchars($account['account_number'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <strong>PHP <?php echo number_format($account['balance'], 2); ?></strong>
                    <a href="member_savings.php?account_id=<?php echo (int) $account['account_id']; ?>">View history</a>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="history">
            <div style="align-items:center;display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between">
                <h2><?php echo htmlspecialchars($selected_account['account_type'], ENT_QUOTES, 'UTF-8'); ?> History</h2>
                <span style="display:flex;gap:10px;align-items:center;">
                    <a href="statement.php?account_id=<?php echo (int) $selected_account['account_id']; ?>&from=<?php echo urlencode($from_date); ?>&to=<?php echo urlencode($to_date); ?>&type=<?php echo urlencode($transaction_type); ?>" target="_blank" rel="noopener" class="btn-action">Print / Save PDF</a> 
                    <a href="member_savings.php?account_id=<?php echo (int) $selected_account['account_id']; ?>&from=<?php echo urlencode($from_date); ?>&to=<?php echo urlencode($to_date); ?>&type=<?php echo urlencode($transaction_type); ?>&download=csv" class="btn-action">Download CSV</a>
                </span>
            </div>
            <form method="GET">
                <input type="hidden" name="account_id" value="<?php echo (int) $selected_account['account_id']; ?>">
                <label>From <input type="date" name="from" value="<?php echo htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label>To <input type="date" name="to" value="<?php echo htmlspecialchars($to_date, ENT_QUOTES, 'UTF-8'); ?>"></label>
                <label>Type 
                    <select name="type">
                        <option value="">All</option>
                        <option value="deposit" <?php echo $transaction_type === 'deposit' ? 'selected' : ''; ?>>Deposits</option>
                        <option value="withdrawal" <?php echo $transaction_type === 'withdrawal' ? 'selected' : ''; ?>>Withdrawals</option>
                        <option value="transfer" <?php echo $transaction_type === 'transfer' ? 'selected' : ''; ?>>Transfers</option>
                    </select>
                </label>
                <button type="submit">Filter</button>
            </form>
            <?php if ($transactions): ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Reference</th><th>Type</th><th>Amount</th><th>Description</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['reference_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="<?php echo htmlspecialchars($transaction['transaction_type'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo strtoupper(htmlspecialchars($transaction['transaction_type'], ENT_QUOTES, 'UTF-8')); ?></td>
                                <td>PHP <?php echo number_format($transaction['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo date('M d, Y H:i', (int) $transaction['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?><p class="empty">No transactions found for this savings account.</p><?php endif; ?>
        </section>
    <?php else: ?>
        <section class="history"><p class="empty">No active savings accounts found.</p></section>
    <?php endif; ?>
</main>
<?php pascco_global_footer(); ?>
<script src="animated-button.js"></script>
</body>
</html>