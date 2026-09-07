<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error   = '';

$stmt = $pdo->prepare("
    SELECT a.id AS account_id, a.account_number, a.balance 
    FROM members m 
    JOIN accounts a ON m.id = a.member_id 
    WHERE m.user_id = ?
");
$stmt->execute([$user_id]);
$sender = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sender) {
    verify_csrf(); // Verify CSRF Token

    $recipient_acc_num = trim($_POST['recipient_account']);
    $amount            = floatval($_POST['amount']);
    $description       = trim($_POST['description']);

    $rec_stmt = $pdo->prepare("
        SELECT a.id AS account_id, m.first_name, m.last_name 
        FROM accounts a 
        JOIN members m ON a.member_id = m.id 
        WHERE a.account_number = ?
    ");
    $rec_stmt->execute([$recipient_acc_num]);
    $recipient = $rec_stmt->fetch();

    if ($amount <= 0) {
        $error = "Please enter a valid transfer amount.";
    } elseif ($amount > $sender['balance']) {
        $error = "Insufficient balance to complete this transfer!";
    } elseif (!$recipient) {
        $error = "Recipient account number not found.";
    } elseif ($recipient['account_id'] === $sender['account_id']) {
        $error = "You cannot transfer money to your own account.";
    } else {
        $ref_number = 'TRF-' . date('YmdHis') . '-' . rand(100, 999);
        $full_desc  = "Transfer to " . $recipient['first_name'] . " " . $recipient['last_name'] . " (" . $description . ")";

        $insert = $pdo->prepare("
            INSERT INTO transactions (account_id, transaction_type, amount, recipient_account_id, description, status, reference_number, transaction_date, created_at) 
            VALUES (?, 'transfer', ?, ?, ?, 'completed', ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
        ");
        $insert->execute([$sender['account_id'], $amount, $recipient['account_id'], $full_desc, $ref_number]);

        $message = "Transfer of ₱" . number_format($amount, 2) . " to " . $recipient['first_name'] . " " . $recipient['last_name'] . " successful!";

        $stmt->execute([$user_id]);
        $sender = $stmt->fetch();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fund Transfer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f9; }
        .navbar { background: #0056b3; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: #ffdd57; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; max-width: 500px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .input-group input { width: 100%; padding: 10px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #0056b3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .msg.success { background: #d4edda; color: #155724; }
        .msg.danger { background: #f8d7da; color: #721c24; }
        .balance-badge { background: #e9ecef; padding: 10px; border-radius: 4px; text-align: center; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>Paco Co-op Transfer</h2>
    <a href="dashboard.php">Back to Dashboard</a>
</div>

<div class="container">
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
                <label>Recipient Account Number</label>
                <input type="text" name="recipient_account" placeholder="e.g. ACC-10001235" required>
            </div>
            <div class="input-group">
                <label>Amount (₱)</label>
                <input type="number" step="0.01" name="amount" placeholder="e.g. 500.00" required>
            </div>
            <div class="input-group">
                <label>Note / Description</label>
                <input type="text" name="description" placeholder="e.g. Payment for shared groceries" required>
            </div>
            <button type="submit">Send Money</button>
        </form>
    </div>
</div>

</body>
</html>