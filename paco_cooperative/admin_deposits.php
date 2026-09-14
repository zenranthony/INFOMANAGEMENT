<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pascco_shell.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}


$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $request_id = (int) ($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['admin_notes'] ?? '');
    $request_stmt = $pdo->prepare("SELECT id, account_id, amount, status FROM deposit_requests WHERE id = ? AND status = 'pending'");
    $request_stmt->execute([$request_id]);
    $request = $request_stmt->fetch();

    if (!$request) {
        $message = 'This deposit request was already reviewed or could not be found.';
    } elseif ($action === 'reject' && $notes === '') {
        $message = 'Add a note explaining why the deposit was rejected.';
    } elseif ($action === 'reject') {
        $update = $pdo->prepare("UPDATE deposit_requests SET status = 'rejected', admin_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND status = 'pending'");
        $update->execute([$notes, (int) $_SESSION['user_id'], $request_id]);
        record_audit($pdo, (int) $_SESSION['user_id'], 'deposit_rejection', 'Rejected deposit request ' . $request_id, 'success');
        $message = 'Deposit request rejected.';
    } elseif ($action === 'approve') {
        try {
            $pdo->beginTransaction();
            $lock_request = $pdo->prepare("SELECT account_id, amount FROM deposit_requests WHERE id = ? AND status = 'pending' FOR UPDATE");
            $lock_request->execute([$request_id]);
            $locked_request = $lock_request->fetch();
            if (!$locked_request) {
                throw new RuntimeException('This deposit request was already reviewed.');
            }
            $lock_account = $pdo->prepare("SELECT id FROM accounts WHERE id = ? AND status = 'active' FOR UPDATE");
            $lock_account->execute([(int) $locked_request['account_id']]);
            if (!$lock_account->fetch()) {
                throw new RuntimeException('The member savings account is no longer active.');
            }
            $reference = 'DEP-' . date('YmdHis') . '-' . random_int(100000, 999999);
            $insert = $pdo->prepare("INSERT INTO transactions (account_id, transaction_type, amount, recipient_account_id, description, status, reference_number, transaction_date, created_at) VALUES (?, 'deposit', ?, NULL, 'E-wallet deposit approved', 'completed', ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
            $insert->execute([(int) $locked_request['account_id'], (float) $locked_request['amount'], $reference]);
            $balance_update = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND status = 'active'");
            $balance_update->execute([(float) $locked_request['amount'], (int) $locked_request['account_id']]);
            $request_update = $pdo->prepare("UPDATE deposit_requests SET status = 'approved', admin_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ? AND status = 'pending'");
            $request_update->execute([$notes, (int) $_SESSION['user_id'], $request_id]);
            $pdo->commit();
            record_audit($pdo, (int) $_SESSION['user_id'], 'deposit_approval', 'Approved deposit request ' . $request_id, 'success');
            $message = 'Deposit approved and member balance updated.';
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = $exception->getMessage();
        }
    }
}

$requests = $pdo->query("SELECT d.*, a.account_number, m.first_name, m.last_name, m.member_number FROM deposit_requests d JOIN accounts a ON a.id = d.account_id JOIN members m ON m.id = d.member_id ORDER BY d.status = 'pending' DESC, d.created_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?><title>Deposit Reviews | PASCCO</title>
    <style>
        body { background:#f4f7fb; color:#182a45; font-family:Georgia, 'Times New Roman', serif; margin:0; }
        .admin-page { margin:0 auto; max-width:1180px; padding:42px 22px 70px; }
        .heading { align-items:end; display:flex; justify-content:space-between; margin-bottom:24px; }
        .heading h1 { color:#071d49; margin:8px 0 0; }
        .eyebrow { color:#1456a0; font-size:.8rem; font-weight:bold; letter-spacing:.16em; text-transform:uppercase; }
        .card { background:white; border:1px solid #d8e0ee; border-top:4px solid #1456a0; margin-bottom:16px; padding:22px; }
        .request-header { align-items:start; display:flex; gap:20px; justify-content:space-between; }
        .request-header h2 { color:#071d49; margin:0 0 5px; }
        .request-meta { color:#5d6b7e; margin:0; }
        .amount { color:#1456a0; font-size:1.25rem; font-weight:bold; white-space:nowrap; }
        .details { display:grid; gap:10px 18px; grid-template-columns:repeat(4, minmax(0, 1fr)); margin:18px 0; }
        .details div { border-top:1px solid #d8e0ee; padding-top:8px; }
        .details small { color:#5d6b7e; display:block; }.details strong { color:#071d49; display:block; margin-top:3px; }
        .receipt-link { color:#1456a0; font-weight:bold; }
        .review-form { align-items:end; display:flex; gap:10px; }
        .review-form textarea { border:1px solid #b8c8d9; font:inherit; min-height:42px; padding:9px; resize:vertical; width:100%; }
        button { border:0; color:white; cursor:pointer; font:inherit; font-weight:bold; padding:10px 14px; }
        .approve { background:#237a45; }.reject { background:#b52b35; }
        .status { font-size:.8rem; font-weight:bold; text-transform:uppercase; }.pending { color:#a16b00; }.approved { color:#237a45; }.rejected { color:#b52b35; }
        .notice { background:#fff4cf; border-left:4px solid #e7b84b; margin-bottom:18px; padding:12px; }
        .empty { color:#5d6b7e; }
        @media(max-width:760px){.heading,.request-header,.review-form{align-items:stretch; flex-direction:column}.details{grid-template-columns:repeat(2,minmax(0,1fr))}.amount{white-space:normal}}
    </style>
</head>
<body>
<?php pascco_admin_header(); ?>
<main class="admin-page">
    <section class="heading"><div><div class="eyebrow">Member deposits</div><h1>Deposit Reviews</h1></div><a href="admin_dashboard.php">Back to Dashboard</a></section>
    <?php if ($message): ?><div class="notice" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($requests): ?>
        <?php foreach ($requests as $request): ?>
            <article class="card">
                <div class="request-header"><div><h2><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name'], ENT_QUOTES, 'UTF-8'); ?></h2><p class="request-meta">Request #<?php echo (int) $request['id']; ?> · <?php echo htmlspecialchars($request['member_number'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($request['created_at'], ENT_QUOTES, 'UTF-8'); ?></p></div><div class="amount">PHP <?php echo number_format($request['amount'], 2); ?><br><span class="status <?php echo htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8'); ?></span></div></div>
                <div class="details"><div><small>Account</small><strong><?php echo htmlspecialchars($request['account_number'], ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Payment method</small><strong><?php echo htmlspecialchars($request['payment_method'], ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>E-wallet reference</small><strong><?php echo htmlspecialchars($request['payment_reference'], ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Purpose</small><strong><?php echo htmlspecialchars($request['description'], ENT_QUOTES, 'UTF-8'); ?></strong></div></div>
                <p><a class="receipt-link" href="deposit_receipt.php?request_id=<?php echo (int) $request['id']; ?>" target="_blank" rel="noopener">View receipt</a></p>
                <?php if ($request['status'] === 'pending'): ?><form method="POST" class="review-form"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>"><textarea name="admin_notes" placeholder="Optional approval note or required rejection reason"></textarea><button class="approve" type="submit" name="action" value="approve">Approve Deposit</button><button class="reject" type="submit" name="action" value="reject">Reject Deposit</button></form><?php elseif ($request['admin_notes']): ?><p><strong>Admin note:</strong> <?php echo htmlspecialchars($request['admin_notes'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php else: ?><section class="card"><p class="empty">No deposit requests yet.</p></section><?php endif; ?>
</main>
<?php pascco_global_footer(); ?>
</body>
</html>
