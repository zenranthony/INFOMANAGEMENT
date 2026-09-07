<?php
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied: You must be logged in as an Admin to view this page. <a href='login.php'>Login here</a>");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf(); // Verify CSRF Token

    $loan_id   = intval($_POST['loan_id']);
    $member_id = intval($_POST['member_id']);
    $amount    = floatval($_POST['amount']);
    $action    = $_POST['action'];

    if ($action === 'approve') {
        $update = $pdo->prepare("UPDATE loans SET status = 'approved' WHERE id = ?");
        $update->execute([$loan_id]);

        $acc_stmt = $pdo->prepare("SELECT id FROM accounts WHERE member_id = ?");
        $acc_stmt->execute([$member_id]);
        $acc = $acc_stmt->fetch();

        if ($acc) {
            $ref_number = 'DISB-' . date('YmdHis') . '-' . rand(100, 999);
            $tx = $pdo->prepare("
                INSERT INTO transactions (account_id, transaction_type, amount, recipient_account_id, description, status, reference_number, transaction_date, created_at) 
                VALUES (?, 'deposit', ?, NULL, 'Loan Disbursement', 'completed', ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
            ");
            $tx->execute([$acc['id'], $amount, $ref_number]);
        }

        $message = "Loan APPROVED! Funds have been disbursed to the member's account.";

    } elseif ($action === 'reject') {
        $update = $pdo->prepare("UPDATE loans SET status = 'rejected' WHERE id = ?");
        $update->execute([$loan_id]);

        $message = "Loan application REJECTED.";
    }
}

$stmt = $pdo->prepare("
    SELECT 
        l.id AS loan_id, 
        l.loan_number, 
        l.loan_amount, 
        l.application_date, 
        m.id AS member_id,
        m.first_name, 
        m.last_name, 
        m.member_number 
    FROM loans l
    JOIN members m ON l.member_id = m.id
    WHERE l.status = 'pending'
    ORDER BY l.id DESC
");
$stmt->execute();
$pending_loans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Loan Approvals</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f9; }
        .navbar { background: #343a40; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: #ffdd57; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; max-width: 900px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .msg { padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .btn-approve { background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-reject { background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .actions { display: flex; gap: 8px; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>Paco Co-op Admin Portal</h2>
    <div>
        Logged in as: <strong>ADMIN</strong> | 
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <h3>Pending Loan Applications</h3>

        <?php if ($message): ?><div class="msg"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <?php if (count($pending_loans) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Loan #</th>
                        <th>Member Name</th>
                        <th>Member ID</th>
                        <th>Amount</th>
                        <th>Date Applied</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_loans as $loan): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($loan['loan_number'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($loan['member_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>₱<?php echo number_format($loan['loan_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($loan['application_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form method="POST" action="admin_loans.php" class="actions">
                                    <!-- Hidden Security Token -->
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                                    <input type="hidden" name="loan_id" value="<?php echo $loan['loan_id']; ?>">
                                    <input type="hidden" name="member_id" value="<?php echo $loan['member_id']; ?>">
                                    <input type="hidden" name="amount" value="<?php echo $loan['loan_amount']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn-approve">Approve</button>
                                    <button type="submit" name="action" value="reject" class="btn-reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No pending loan applications found.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>