<?php
require_once 'db.php';
require_once 'pascco_shell.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$query = trim($_GET['q'] ?? '');
$members = [];
$selected_member = null;
$accounts = [];
$loans = [];
$transactions = [];

if ($query !== '') {
    $search = '%' . $query . '%';
    $member_stmt = $pdo->prepare("SELECT DISTINCT m.id, m.user_id, m.member_number, m.first_name, m.last_name, m.email, m.phone, m.created_at, u.username
        FROM members m
        JOIN users u ON u.id = m.user_id
        LEFT JOIN accounts a ON a.member_id = m.id
        LEFT JOIN transactions t ON t.account_id = a.id
        WHERE m.member_number LIKE ? OR m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ? OR u.username LIKE ? OR a.account_number LIKE ? OR t.reference_number LIKE ?
        ORDER BY m.last_name, m.first_name LIMIT 30");
    $member_stmt->execute([$search, $search, $search, $search, $search, $search, $search]);
    $members = $member_stmt->fetchAll();

    $selected_id = (int) ($_GET['member_id'] ?? 0);
    if ($selected_id > 0) {
        $detail_stmt = $pdo->prepare("SELECT m.id, m.member_number, m.first_name, m.last_name, m.email, m.phone, m.created_at, u.username
            FROM members m JOIN users u ON u.id = m.user_id WHERE m.id = ?");
        $detail_stmt->execute([$selected_id]);
        $selected_member = $detail_stmt->fetch();

        if ($selected_member) {
            $account_stmt = $pdo->prepare("SELECT id, account_number, account_type, balance, status, created_at FROM accounts WHERE member_id = ? ORDER BY id DESC");
            $account_stmt->execute([$selected_id]);
            $accounts = $account_stmt->fetchAll();

            $loan_stmt = $pdo->prepare("SELECT loan_number, loan_amount, remaining_balance, status, application_date, created_at FROM loans WHERE member_id = ? ORDER BY id DESC");
            $loan_stmt->execute([$selected_id]);
            $loans = $loan_stmt->fetchAll();

            $transaction_stmt = $pdo->prepare("SELECT t.transaction_type, t.amount, t.description, t.reference_number, t.status, t.transaction_date, a.account_number
                FROM transactions t JOIN accounts a ON a.id = t.account_id WHERE a.member_id = ? ORDER BY t.id DESC LIMIT 50");
            $transaction_stmt->execute([$selected_id]);
            $transactions = $transaction_stmt->fetchAll();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?>
    <title>Member Search | PASCCO Admin</title>
    <style>
        body { background: #f4f7fb; color: #182a45; font-family: Georgia, 'Times New Roman', serif; margin: 0; }
        .admin-page { margin: 0 auto; max-width: 1180px; padding: 42px 22px 70px; }
        .heading { align-items: end; display: flex; justify-content: space-between; margin-bottom: 24px; }
        .heading h1 { color: #071d49; font-size: clamp(2rem, 4vw, 3.2rem); margin: 8px 0 0; }
        .eyebrow { color: #1456a0; font-size: .8rem; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; }
        .search-card, .card { background: white; border: 1px solid #d8e0ee; border-top: 4px solid #1456a0; box-shadow: 0 4px 14px rgba(7,29,73,.08); margin-bottom: 22px; padding: 24px; }
        .search-form { display: flex; gap: 10px; }
        .search-form input { border: 1px solid #b8c8d9; border-radius: 3px; flex: 1; font: inherit; padding: 12px; }
        .search-form button { background: #e7b84b; border: 0; color: #071d49; cursor: pointer; font: inherit; font-weight: bold; padding: 12px 20px; }
        .results { display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); }
        .result { background: #e6edf8; border-left: 4px solid #1456a0; padding: 15px; }
        .result h3 { color: #071d49; margin: 0 0 5px; }
        .result p { margin: 4px 0; }
        .result a { color: #1456a0; font-weight: bold; }
        .profile { display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .profile-item { background: #e6edf8; padding: 13px; }
        .profile-item small { color: #5d6b7e; display: block; }
        .profile-item strong { color: #071d49; display: block; margin-top: 4px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #d8e0ee; padding: 11px 9px; text-align: left; }
        th { background: #e6edf8; color: #071d49; }
        .table-wrap { overflow-x: auto; }
        .badge { background: #cfe0f5; color: #071d49; padding: 4px 8px; font-size: .78rem; font-weight: bold; }
        .badge.pending { background: #e7b84b; }
        .badge.rejected { background: #f8d7da; color: #721c24; }
        .empty { color: #5d6b7e; padding: 12px 0; }
        @media (max-width: 650px) { .heading { align-items: flex-start; flex-direction: column; } .search-form { flex-direction: column; } }
    </style>
</head>
<body>
<?php pascco_admin_header(); ?>
<main class="admin-page">
    <section class="heading"><div><div class="eyebrow">Member records</div><h1>Search Members</h1></div><a href="admin_dashboard.php">Back to Dashboard</a></section>
    <section class="search-card"><h2>Find a member</h2><form class="search-form" method="GET" action="admin_members.php"><input name="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Name, member number, email, username, account, or OR number" required><button type="submit">Search</button></form></section>

    <?php if ($query !== '' && !$selected_member): ?>
        <section class="card"><h2>Search Results</h2><?php if ($members): ?><div class="results"><?php foreach ($members as $member): ?><article class="result"><h3><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></h3><p><?php echo htmlspecialchars($member['member_number']); ?></p><p><?php echo htmlspecialchars($member['email']); ?></p><a href="admin_members.php?q=<?php echo urlencode($query); ?>&member_id=<?php echo $member['id']; ?>">View financial history</a></article><?php endforeach; ?></div><?php else: ?><p class="empty">No members matched your search.</p><?php endif; ?></section>
    <?php endif; ?>

    <?php if ($selected_member): ?>
        <section class="card"><h2><?php echo htmlspecialchars($selected_member['first_name'] . ' ' . $selected_member['last_name']); ?></h2><div class="profile"><div class="profile-item"><small>Member Number</small><strong><?php echo htmlspecialchars($selected_member['member_number']); ?></strong></div><div class="profile-item"><small>Email</small><strong><?php echo htmlspecialchars($selected_member['email']); ?></strong></div><div class="profile-item"><small>Phone</small><strong><?php echo htmlspecialchars($selected_member['phone']); ?></strong></div><div class="profile-item"><small>Username</small><strong><?php echo htmlspecialchars($selected_member['username']); ?></strong></div></div></section>
        <section class="card"><h2>Savings Accounts</h2><?php if ($accounts): ?><div class="table-wrap"><table><thead><tr><th>Account</th><th>Type</th><th>Balance</th><th>Status</th></tr></thead><tbody><?php foreach ($accounts as $account): ?><tr><td><?php echo htmlspecialchars($account['account_number']); ?></td><td><?php echo htmlspecialchars($account['account_type']); ?></td><td><strong>₱<?php echo number_format($account['balance'], 2); ?></strong></td><td><?php echo htmlspecialchars($account['status']); ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="empty">No savings accounts found.</p><?php endif; ?></section>
        <section class="card"><h2>Loan History</h2><?php if ($loans): ?><div class="table-wrap"><table><thead><tr><th>Loan</th><th>Original Amount</th><th>Remaining</th><th>Status</th><th>Applied</th></tr></thead><tbody><?php foreach ($loans as $loan): ?><tr><td><?php echo htmlspecialchars($loan['loan_number']); ?></td><td>₱<?php echo number_format($loan['loan_amount'], 2); ?></td><td>₱<?php echo number_format($loan['remaining_balance'], 2); ?></td><td><span class="badge <?php echo htmlspecialchars($loan['status']); ?>"><?php echo strtoupper(htmlspecialchars($loan['status'])); ?></span></td><td><?php echo htmlspecialchars($loan['application_date']); ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="empty">No loan history found.</p><?php endif; ?></section>
        <section class="card"><h2>Transaction History</h2><?php if ($transactions): ?><div class="table-wrap"><table><thead><tr><th>Date</th><th>Account</th><th>Type</th><th>Amount</th><th>Description</th><th>OR Number</th></tr></thead><tbody><?php foreach ($transactions as $transaction): ?><tr><td><?php echo date('M j, Y H:i', (int) $transaction['transaction_date']); ?></td><td><?php echo htmlspecialchars($transaction['account_number']); ?></td><td><?php echo strtoupper(htmlspecialchars($transaction['transaction_type'])); ?></td><td>₱<?php echo number_format($transaction['amount'], 2); ?></td><td><?php echo htmlspecialchars($transaction['description']); ?></td><td><small><?php echo htmlspecialchars($transaction['reference_number']); ?></small></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="empty">No transaction history found.</p><?php endif; ?></section>
    <?php endif; ?>
</main>
<?php pascco_global_footer(); ?>
</body>
</html>
