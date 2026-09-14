<?php

require_once 'db.php';
require_once 'pascco_shell.php';

// Restrict access to Admin role only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied: Admin authorization required. <a href='login.php'>Login here</a>");
}

// 1. Fetch Total Cooperative Savings
$savings_stmt = $pdo->query("SELECT SUM(balance) AS total_savings FROM accounts");
$total_savings = $savings_stmt->fetch()['total_savings'] ?? 0;

// 2. Fetch Total Outstanding Loans
$loans_stmt = $pdo->query("SELECT SUM(remaining_balance) AS total_loans FROM loans WHERE status = 'approved'");
$total_loans = $loans_stmt->fetch()['total_loans'] ?? 0;

// 3. Fetch Total Active Members
$members_stmt = $pdo->query("SELECT COUNT(*) AS total_members FROM members");
$total_members = $members_stmt->fetch()['total_members'] ?? 0;

// 4. Fetch Count of Pending Loan Applications
$pending_stmt = $pdo->query("SELECT COUNT(*) AS pending_count FROM loans WHERE status = 'pending'");
$pending_count = $pending_stmt->fetch()['pending_count'] ?? 0;
$pending_deposits = 0;
try {
    $pending_deposits = (int) $pdo->query("SELECT COUNT(*) FROM deposit_requests WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $exception) {
}

$pending_account_requests = 0;
try {
    $pending_account_requests = (int) $pdo->query("SELECT COUNT(*) FROM account_requests WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $exception) {
}

// 5. Fetch loan status summary for operational monitoring.
$loan_status_stmt = $pdo->query("SELECT status, COUNT(*) AS total FROM loans GROUP BY status");
$loan_status = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'paid' => 0];
foreach ($loan_status_stmt->fetchAll() as $row) {
    $loan_status[$row['status']] = (int) $row['total'];
}

// 6. Fetch recent applications and newly registered members.
$recent_loans_stmt = $pdo->query("
    SELECT l.id AS loan_id, l.loan_number, l.loan_amount, l.status, l.application_date,
           m.first_name, m.last_name, m.member_number
    FROM loans l
    JOIN members m ON m.id = l.member_id
    ORDER BY l.id DESC LIMIT 6
");
$recent_loans = $recent_loans_stmt->fetchAll();

$recent_members_stmt = $pdo->query("
    SELECT member_number, first_name, last_name, email, created_at
    FROM members
    ORDER BY id DESC LIMIT 6
");
$recent_members = $recent_members_stmt->fetchAll();

$transaction_count = (int) $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();

// 7. Fetch Recent Audit Logs
$audit_stmt = $pdo->query("
    SELECT a.action, a.description, a.ip_address, a.created_at, u.username 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.id DESC LIMIT 10
");
$audit_logs = $audit_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?>
    <title>Admin Dashboard - Paco Cooperative</title>
    <style>
        :root { --deep-blue: #071d49; --blue: #1456a0; --gold: #e7b84b; --line: #d8e0ee; }
        * { box-sizing: border-box; }
        body { font-family: Georgia, 'Times New Roman', serif; margin: 0; background: #f4f7fb; color: #182a45; }
        .navbar { align-items: center; background: linear-gradient(90deg, var(--deep-blue), var(--blue)); color: white; display: flex; gap: 24px; justify-content: space-between; padding: 12px clamp(18px, 5vw, 72px); }
        .brand { align-items: center; color: white; display: flex; gap: 10px; margin-right: auto; text-decoration: none; }
        .brand img { height: 48px; width: 48px; }
        .brand strong { font-family: Impact, 'Arial Black', sans-serif; font-size: 1.35rem; letter-spacing: .06em; }
        .admin-nav { align-items: center; display: flex; flex-wrap: wrap; gap: 8px; }
        .navbar a { color: white; padding: 8px 10px; text-decoration: none; }
        .navbar a:hover { color: var(--gold); }
        .navbar .logout { border: 1px solid var(--gold); color: #ffe7a1; }
        .container { margin: 0 auto; max-width: 1180px; padding: 42px 22px 70px; }
        .eyebrow { color: var(--blue); font-size: .8rem; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; }
        .page-heading { align-items: end; display: flex; justify-content: space-between; margin-bottom: 25px; }
        .page-heading h1 { color: var(--deep-blue); font-size: clamp(2rem, 4vw, 3.2rem); margin: 8px 0 0; }
        .quick-actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .quick-actions a { background: var(--gold); color: var(--deep-blue); font-weight: bold; padding: 12px 15px; text-decoration: none; }
        .quick-actions a.secondary { background: var(--blue); color: white; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 4px 14px rgba(7,29,73,.1); border-left: 5px solid var(--blue); }
        .stat-card.gold { border-left-color: var(--gold); }
        .stat-card h4 { margin: 0 0 10px 0; color: #5d6b7e; font-size: 14px; text-transform: uppercase; }
        .stat-card h2 { margin: 0; font-size: 28px; }
        .card { background: white; border: 1px solid var(--line); border-top: 4px solid var(--blue); padding: 25px; border-radius: 6px; box-shadow: 0 4px 14px rgba(7,29,73,.08); margin-bottom: 20px; }
        .dashboard-grid { display: grid; gap: 20px; grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr); }
        .status-list { display: grid; gap: 10px; grid-template-columns: repeat(2, 1fr); }
        .status-item { background: #e6edf8; border-left: 4px solid var(--blue); padding: 13px; }
        .status-item.gold { border-left-color: var(--gold); }
        .status-item strong { color: var(--deep-blue); display: block; font-size: 1.4rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 11px 10px; text-align: left; border-bottom: 1px solid var(--line); }
        th { background: #e6edf8; color: var(--deep-blue); }
        .badge { background: #e6edf8; color: var(--deep-blue); padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .badge.pending { background: var(--gold); }
        .badge.approved, .badge.paid { background: #cfe0f5; }
        .badge.rejected { background: #f8d7da; color: #721c24; }
        .table-wrap { overflow-x: auto; }
        @media (max-width: 800px) { .page-heading { align-items: flex-start; flex-direction: column; gap: 18px; } .dashboard-grid { grid-template-columns: 1fr; } .navbar { align-items: flex-start; flex-wrap: wrap; } .admin-nav { justify-content: flex-start; order: 3; width: 100%; } }
    </style>
</head>
<body>

<?php pascco_admin_header(); ?>

<div class="container">
    <section class="page-heading">
        <div><div class="eyebrow">Operations center</div><h1>Admin Dashboard</h1></div>
        <div class="quick-actions"><a href="admin_loans.php">Review Loans</a><a class="secondary" href="admin_members.php">Search Members</a><a class="secondary" href="admin_announcements.php">Manage Announcements</a></div>
    </section>

    <!-- Financial & System Overview Cards -->
    <div class="stats-grid">
        <div class="stat-card gold">
            <h4>Total Member Savings</h4>
            <h2>₱<?php echo number_format($total_savings, 2); ?></h2>
        </div>
        <div class="stat-card gold">
            <h4>Outstanding Loans</h4>
            <h2>₱<?php echo number_format($total_loans, 2); ?></h2>
        </div>
        <div class="stat-card">
            <h4>Total Registered Members</h4>
            <h2><?php echo $total_members; ?></h2>
        </div>
        <div class="stat-card">
            <h4>Pending Loan Requests</h4>
            <h2><?php echo $pending_count; ?></h2>
        </div>
        <div class="stat-card gold">
            <h4>Pending Deposit Reviews</h4>
            <h2><?php echo $pending_deposits; ?></h2>
        </div>
        <div class="stat-card gold">
            <h4>Pending Account Requests</h4>
            <h2><?php echo $pending_account_requests; ?></h2>
        </div>
        <div class="stat-card">
            <h4>Total Transactions</h4>
            <h2><?php echo number_format($transaction_count); ?></h2>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <h3>Recent Loan Applications</h3>
            <div class="table-wrap">
                <?php if ($recent_loans): ?>
                    <table>
                        <thead><tr><th>Loan</th><th>Member</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($recent_loans as $loan): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($loan['loan_number']); ?></strong><br><small><?php echo htmlspecialchars($loan['application_date']); ?></small><br><a href="loan_application_print.php?loan_id=<?php echo (int) $loan['loan_id']; ?>" target="_blank" rel="noopener">View / Print Form</a></td>
                                <td><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']); ?><br><small><?php echo htmlspecialchars($loan['member_number']); ?></small></td>
                                <td>₱<?php echo number_format($loan['loan_amount'], 2); ?></td>
                                <td><span class="badge <?php echo htmlspecialchars($loan['status']); ?>"><?php echo strtoupper(htmlspecialchars($loan['status'])); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?><p>No loan applications found.</p><?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h3>Loan Status</h3>
            <div class="status-list">
                <div class="status-item gold"><small>Pending</small><strong><?php echo $loan_status['pending']; ?></strong></div>
                <div class="status-item"><small>Approved</small><strong><?php echo $loan_status['approved']; ?></strong></div>
                <div class="status-item"><small>Paid</small><strong><?php echo $loan_status['paid']; ?></strong></div>
                <div class="status-item"><small>Rejected</small><strong><?php echo $loan_status['rejected']; ?></strong></div>
            </div>
            <p><a href="admin_loans.php">Open loan approvals</a></p>
        </div>
    </div>

    <div class="card">
        <h3>Recently Registered Members</h3>
        <div class="table-wrap">
            <?php if ($recent_members): ?>
                <table><thead><tr><th>Member</th><th>Email</th><th>Registered</th></tr></thead><tbody>
                <?php foreach ($recent_members as $member): ?>
                    <tr><td><strong><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></strong><br><small><?php echo htmlspecialchars($member['member_number']); ?></small></td><td><?php echo htmlspecialchars($member['email']); ?></td><td><?php echo htmlspecialchars($member['created_at']); ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php else: ?><p>No members found.</p><?php endif; ?>
        </div>
    </div>

    <!-- Audit Trail / Activity Log -->
    <div class="card">
        <h3>System Audit Logs</h3>
        <?php if (count($audit_logs) > 0): ?>
            <div class="table-wrap"><table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audit_logs as $log): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($log['action']); ?></code></td>
                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                            <td><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                            <td><small><?php echo $log['created_at']; ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?>
            <p>No audit log records found.</p>
        <?php endif; ?>
    </div>

</div>

<?php pascco_global_footer(); ?>

</body>
</html>