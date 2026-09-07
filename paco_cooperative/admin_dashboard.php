<?php
session_start();
require_once 'db.php';

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

// 5. Fetch Recent Audit Logs
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
    <title>Admin Dashboard - Paco Cooperative</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f9; color: #333; }
        .navbar { background: #343a40; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: #ffdd57; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .container { padding: 30px; max-width: 1000px; margin: 0 auto; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-left: 5px solid #0056b3; }
        .stat-card.green { border-left-color: #28a745; }
        .stat-card.orange { border-left-color: #fd7e14; }
        .stat-card.purple { border-left-color: #6f42c1; }
        .stat-card h4 { margin: 0 0 10px 0; color: #666; font-size: 14px; text-transform: uppercase; }
        .stat-card h2 { margin: 0; font-size: 28px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .badge-pending { background: #ffc107; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>Paco Co-op Admin Hub</h2>
    <div>
        <a href="admin_loans.php">
            Loan Approvals 
            <?php if ($pending_count > 0): ?>
                <span class="badge-pending"><?php echo $pending_count; ?> PENDING</span>
            <?php endif; ?>
        </a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">

    <!-- Financial & System Overview Cards -->
    <div class="stats-grid">
        <div class="stat-card green">
            <h4>Total Member Savings</h4>
            <h2>₱<?php echo number_format($total_savings, 2); ?></h2>
        </div>
        <div class="stat-card orange">
            <h4>Outstanding Loans</h4>
            <h2>₱<?php echo number_format($total_loans, 2); ?></h2>
        </div>
        <div class="stat-card">
            <h4>Total Registered Members</h4>
            <h2><?php echo $total_members; ?></h2>
        </div>
        <div class="stat-card purple">
            <h4>Pending Loan Requests</h4>
            <h2><?php echo $pending_count; ?></h2>
        </div>
    </div>

    <!-- Audit Trail / Activity Log -->
    <div class="card">
        <h3>System Audit Logs</h3>
        <?php if (count($audit_logs) > 0): ?>
            <table>
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
            </table>
        <?php else: ?>
            <p>No audit log records found.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>