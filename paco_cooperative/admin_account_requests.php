<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pascco_shell.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$status_filter = $_GET['status'] ?? 'pending';
$allowed_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = 'pending';
}

$requests_stmt = $pdo->prepare('SELECT ar.id, ar.member_id, ar.account_type, ar.status, ar.requested_at, ar.admin_notes, m.first_name, m.last_name, m.member_number FROM account_requests ar JOIN members m ON m.id = ar.member_id WHERE ar.status = ? ORDER BY ar.requested_at DESC');
$requests_stmt->execute([$status_filter]);
$requests = $requests_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $request_id = (int) ($_POST['request_id'] ?? 0);
    $decision = trim($_POST['decision'] ?? '');
    $admin_notes = trim($_POST['admin_notes'] ?? '');

    if ($request_id <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
        $error = 'Invalid account request.';
    } else {
        try {
            $request = $pdo->prepare('SELECT ar.member_id, ar.account_type, m.user_id FROM account_requests ar JOIN members m ON m.id = ar.member_id WHERE ar.id = ? LIMIT 1');
            $request->execute([$request_id]);
            $record = $request->fetch();

            if (!$record) {
                throw new RuntimeException('Account request not found.');
            }

            $pdo->beginTransaction();

            $update = $pdo->prepare('UPDATE account_requests SET status = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?');
            $update->execute([$decision, $admin_notes, (int) $_SESSION['user_id'], $request_id]);

            if ($decision === 'approved') {
                $account_number = 'ACC-' . str_pad((string) ((int) $pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn() + 10000001), 8, '0', STR_PAD_LEFT);
                $insert_account = $pdo->prepare('INSERT INTO accounts (member_id, account_number, account_type, balance, status) VALUES (?, ?, ?, 0, "active")');
                $insert_account->execute([(int) $record['member_id'], $account_number, $record['account_type']]);
            }

            $pdo->commit();
            record_audit($pdo, (int) $_SESSION['user_id'], 'account_request_review', 'Reviewed account request #' . $request_id . ' -> ' . $decision, 'success');
            $message = 'Account request updated successfully.';
            $requests_stmt->execute([$status_filter]);
            $requests = $requests_stmt->fetchAll();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?>
    <title>Account Requests | PASCCO Admin</title>
    <style>
        body { background: #f4f7fb; color: #182a45; font-family: Georgia, 'Times New Roman', serif; margin: 0; }
        .page { max-width: 1100px; margin: 0 auto; padding: 42px 22px 70px; }
        .heading { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; }
        .eyebrow { color: #1456a0; font-size: .8rem; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; }
        h1 { color: #071d49; margin: 6px 0 0; }
        .card { background: white; border: 1px solid #d8e0ee; border-top: 4px solid #1456a0; box-shadow: 0 4px 14px rgba(7,29,73,.08); padding: 24px; margin-bottom: 22px; }
        .filters { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        select, textarea, input { border: 1px solid #b8c8d9; font: inherit; padding: 10px 12px; }
        .request-list { display: grid; gap: 12px; }
        .request-item { background: #e6edf8; border-left: 4px solid #1456a0; padding: 14px; }
        .request-item strong { color: #071d49; }
        .status { display: inline-block; margin-top: 8px; padding: 4px 8px; font-size: .78rem; font-weight: bold; }
        .status.pending { background: #e7b84b; }
        .status.approved { background: #d8f0d8; }
        .status.rejected { background: #f8d7da; }
        .review-form { margin-top: 18px; }
        textarea { min-height: 100px; width: 100%; }
        button { background: #1456a0; border: 0; color: white; cursor: pointer; font: inherit; font-weight: bold; padding: 12px 18px; }
        .msg { margin-bottom: 18px; padding: 12px; }
        .msg.success { background: #fff4cf; color: #071d49; }
        .msg.error { background: #f8d7da; color: #721c24; }
        @media (max-width: 700px) { .heading { align-items: flex-start; flex-direction: column; } .filters { width: 100%; } .filters select, .filters button { width: 100%; } }
    </style>
</head>
<body>
<?php pascco_admin_header(); ?>
<main class="page">
    <div class="heading">
        <div>
            <div class="eyebrow">Operations</div>
            <h1>Account Requests</h1>
        </div>
        <a href="admin_dashboard.php">Back to Dashboard</a>
    </div>

    <?php if ($message): ?><div class="msg success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <section class="card">
        <div class="filters">
            <label for="status-filter">Filter:</label>
            <select id="status-filter" onchange="window.location='admin_account_requests.php?status='+this.value;">
                <?php foreach ($allowed_statuses as $status): ?>
                    <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>><?php echo ucfirst(htmlspecialchars($status, ENT_QUOTES, 'UTF-8')); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </section>

    <section class="card">
        <h2>Pending requests</h2>
        <?php if ($requests): ?>
            <div class="request-list">
                <?php foreach ($requests as $request): ?>
                    <div class="request-item">
                        <strong><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></strong><br>
                        <?php echo htmlspecialchars($request['member_number']); ?><br>
                        Account type: <?php echo htmlspecialchars($request['account_type']); ?><br>
                        Requested: <?php echo htmlspecialchars($request['requested_at']); ?>
                        <div class="status <?php echo htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo strtoupper(htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8')); ?></div>

                        <form method="POST" action="admin_account_requests.php?status=<?php echo urlencode($status_filter); ?>" class="review-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                            <label for="decision-<?php echo (int) $request['id']; ?>">Decision</label>
                            <select id="decision-<?php echo (int) $request['id']; ?>" name="decision" required>
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                            <label for="admin-notes-<?php echo (int) $request['id']; ?>">Admin notes</label>
                            <textarea id="admin-notes-<?php echo (int) $request['id']; ?>" name="admin_notes" placeholder="Add notes for the member"><?php echo htmlspecialchars($request['admin_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <button type="submit" style="margin-top:12px;">Save Decision</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No account requests found in this status.</p>
        <?php endif; ?>
    </section>
</main>
<?php pascco_global_footer(); ?>
</body>
</html>
