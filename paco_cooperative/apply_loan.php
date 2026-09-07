<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error   = '';

$stmt = $pdo->prepare("SELECT id FROM members WHERE user_id = ?");
$stmt->execute([$user_id]);
$member = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $member) {
    verify_csrf(); // Verify CSRF token

    $amount = floatval($_POST['loan_amount']);

    if ($amount <= 0) {
        $error = "Please enter a valid loan amount.";
    } else {
        $loan_number = 'LN-' . date('Y') . '-' . rand(1000, 9999);

        $insert = $pdo->prepare("
            INSERT INTO loans (member_id, loan_number, loan_amount, remaining_balance, status, application_date) 
            VALUES (?, ?, ?, ?, 'pending', CURDATE())
        ");
        $insert->execute([$member['id'], $loan_number, $amount, $amount]);

        $message = "Loan application submitted successfully! Status: PENDING";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for Loan</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f9; }
        .navbar { background: #0056b3; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: #ffdd57; text-decoration: none; font-weight: bold; }
        .container { padding: 30px; max-width: 500px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .input-group input { width: 100%; padding: 10px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .msg { padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
        .msg.success { background: #d4edda; color: #155724; }
        .msg.danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>Paco Co-op Loans</h2>
    <a href="dashboard.php">Back to Dashboard</a>
</div>

<div class="container">
    <div class="card">
        <h3>Loan Application</h3>

        <?php if ($message): ?><div class="msg success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <form method="POST" action="apply_loan.php">
            <!-- Hidden Security Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="input-group">
                <label>Desired Loan Amount (₱)</label>
                <input type="number" step="0.01" name="loan_amount" placeholder="e.g. 10000" required>
            </div>
            <button type="submit">Submit Application</button>
        </form>
    </div>
</div>

</body>
</html>