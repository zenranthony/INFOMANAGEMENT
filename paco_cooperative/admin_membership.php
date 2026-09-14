<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pascco_shell.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$selected_application_id = (int) ($_GET['application_id'] ?? 0);
$status_filter = $_GET['status'] ?? 'all';
$allowed_statuses = ['all', 'applied', 'scheduled', 'certificate_uploaded', 'under_review', 'approved', 'rejected'];
if (!in_array($status_filter, $allowed_statuses, true)) {
    $status_filter = 'all';
}

$where = $status_filter === 'all' ? '' : 'WHERE status = :status';
$stmt = $pdo->prepare('SELECT * FROM membership_applications ' . $where . ' ORDER BY created_at DESC');
if ($status_filter !== 'all') {
    $stmt->execute([':status' => $status_filter]);
} else {
    $stmt->execute();
}
$applications = $stmt->fetchAll();

$selected_application = null;
if ($selected_application_id > 0) {
    $detail = $pdo->prepare('SELECT * FROM membership_applications WHERE id = ? LIMIT 1');
    $detail->execute([$selected_application_id]);
    $selected_application = $detail->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $application_id = (int) ($_POST['application_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');
    $admin_notes = trim($_POST['admin_notes'] ?? '');

    if ($application_id <= 0 || !in_array($new_status, ['applied', 'scheduled', 'certificate_uploaded', 'under_review', 'approved', 'rejected'], true)) {
        $error = 'Invalid membership request.';
    } else {
        try {
            $update = $pdo->prepare('UPDATE membership_applications SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?');
            $update->execute([$new_status, $admin_notes, $application_id]);
            record_audit($pdo, (int) $_SESSION['user_id'], 'membership_review', 'Updated registration application status to ' . $new_status, 'success');
            $message = 'Membership application updated successfully.';
            $selected_application_id = $application_id;
            $detail = $pdo->prepare('SELECT * FROM membership_applications WHERE id = ? LIMIT 1');
            $detail->execute([$application_id]);
            $selected_application = $detail->fetch();
            $stmt = $pdo->prepare('SELECT * FROM membership_applications ' . ($status_filter === 'all' ? '' : 'WHERE status = :status') . ' ORDER BY created_at DESC');
            if ($status_filter !== 'all') {
                $stmt->execute([':status' => $status_filter]);
            } else {
                $stmt->execute();
            }
            $applications = $stmt->fetchAll();
        } catch (Throwable $exception) {
            $error = 'Unable to update the membership application.';
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
    <title>Membership Review | PASCCO Admin</title>
    <style>
        body { background: #f4f7fb; color: #182a45; font-family: Georgia, 'Times New Roman', serif; margin: 0; }
        .admin-page { margin: 0 auto; max-width: 1200px; padding: 42px 22px 70px; }
        .heading { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; }
        .eyebrow { color: #1456a0; font-size: .8rem; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; }
        h1 { color: #071d49; margin: 4px 0 0; }
        .card { background: white; border: 1px solid #d8e0ee; border-top: 4px solid #1456a0; box-shadow: 0 4px 16px rgba(7,29,73,.08); padding: 24px; margin-bottom: 22px; }
        .filters { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .filters select, .filters input, .card input, .card select, .card textarea { border: 1px solid #b8c8d9; font: inherit; padding: 10px 12px; }
        .filters select { min-width: 180px; }
        .filters a, .filters button { text-decoration: none; }
        .filters button { background: #1456a0; border: 0; color: white; cursor: pointer; font-weight: bold; padding: 10px 16px; }
        .application-list { display: grid; gap: 12px; }
        .application-item { background: #e6edf8; border-left: 4px solid #1456a0; padding: 14px; }
        .application-item a { color: #1456a0; font-weight: bold; text-decoration: none; }
        .badge { display: inline-block; font-size: .78rem; font-weight: bold; margin-top: 6px; padding: 4px 8px; }
        .badge.applied { background: #e7b84b; }
        .badge.scheduled { background: #dfeeff; }
        .badge.certificate_uploaded { background: #d6f5df; }
        .badge.under_review { background: #e2d6ff; }
        .badge.approved { background: #d8f0d8; }
        .badge.rejected { background: #f8d7da; }
        .detail-grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .detail-box { background: #eff5ff; border: 1px solid #d8e0ee; padding: 12px; }
        .detail-box small { color: #5d6b7e; display: block; }
        .detail-box strong { color: #071d49; display: block; margin-top: 4px; }
        .message { margin-bottom: 18px; padding: 12px; }
        .message.success { background: #fff4cf; color: #071d49; }
        .message.error { background: #f8d7da; color: #721c24; }
        form textarea { min-height: 110px; width: 100%; }
        form select { width: 100%; }
        .review-btn { background: #1456a0; border: 0; color: white; cursor: pointer; font: inherit; font-weight: bold; padding: 12px 18px; width: 100%; }
        @media (max-width: 700px) { .heading { align-items: flex-start; flex-direction: column; } .filters { width: 100%; } .filters select, .filters button { width: 100%; } }
    </style>
</head>
<body>
<?php pascco_admin_header(); ?>
<main class="admin-page">
    <div class="heading">
        <div>
            <div class="eyebrow">Membership</div>
            <h1>Membership Review</h1>
        </div>
        <a href="admin_dashboard.php">Back to Dashboard</a>
    </div>

    <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <section class="card">
        <div class="filters">
            <label for="status-filter">Status:</label>
            <select id="status-filter" onchange="window.location='admin_membership.php?status='+this.value;">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                <?php foreach (['applied','scheduled','certificate_uploaded','under_review','approved','rejected'] as $status): ?>
                    <option value="<?php echo $status; ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $status)); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </section>

    <section class="card">
        <h2>Applications</h2>
        <div class="application-list">
            <?php if ($applications): ?>
                <?php foreach ($applications as $application): ?>
                    <div class="application-item">
                        <strong><?php echo htmlspecialchars($application['member_name']); ?></strong><br>
                        <?php echo htmlspecialchars($application['email']); ?><br>
                        <?php echo htmlspecialchars($application['phone']); ?><br>
                        <a href="admin_membership.php?status=<?php echo urlencode($status_filter); ?>&application_id=<?php echo (int) $application['id']; ?>">Review application</a>
                        <div class="badge <?php echo htmlspecialchars($application['status']); ?>"><?php echo strtoupper(str_replace('_', ' ', htmlspecialchars($application['status'], ENT_QUOTES, 'UTF-8'))); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No membership applications found.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($selected_application): ?>
        <section class="card">
            <h2>Application Details</h2>
            <div class="detail-grid">
                <div class="detail-box"><small>Applicant</small><strong><?php echo htmlspecialchars($selected_application['member_name']); ?></strong></div>
                <div class="detail-box"><small>Email</small><strong><?php echo htmlspecialchars($selected_application['email']); ?></strong></div>
                <div class="detail-box"><small>Phone</small><strong><?php echo htmlspecialchars($selected_application['phone']); ?></strong></div>
                <div class="detail-box"><small>Preferred Saturday</small><strong><?php echo htmlspecialchars($selected_application['preferred_schedule_date'] ?: 'Not set'); ?></strong></div>
                <div class="detail-box"><small>PMES Schedule</small><strong><?php echo htmlspecialchars($selected_application['pmes_schedule'] ?: 'Not assigned'); ?></strong></div>
                <div class="detail-box"><small>Status</small><strong><?php echo strtoupper(str_replace('_', ' ', $selected_application['status'])); ?></strong></div>
            </div>

            <?php if ($selected_application['notes']): ?>
                <div class="detail-box" style="margin-top:16px;"><small>Applicant Notes</small><strong><?php echo nl2br(htmlspecialchars($selected_application['notes'], ENT_QUOTES, 'UTF-8')); ?></strong></div>
            <?php endif; ?>

            <?php if ($selected_application['certificate_path']): ?>
                <div style="margin-top:16px;">
                    <a href="<?php echo htmlspecialchars($selected_application['certificate_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Open certificate</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin_membership.php?status=<?php echo urlencode($status_filter); ?>&application_id=<?php echo (int) $selected_application['id']; ?>" style="margin-top:20px;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="application_id" value="<?php echo (int) $selected_application['id']; ?>">
                <div style="margin-bottom: 12px;">
                    <label for="status">Update Status</label>
                    <select id="status" name="status">
                        <?php foreach (['applied','scheduled','certificate_uploaded','under_review','approved','rejected'] as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo $selected_application['status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $status)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom: 12px;">
                    <label for="admin-notes">Admin Notes</label>
                    <textarea id="admin-notes" name="admin_notes" placeholder="Add review comments"><?php echo htmlspecialchars($selected_application['admin_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <button class="review-btn" type="submit">Save Review</button>
            </form>
        </section>
    <?php endif; ?>
</main>
<?php pascco_global_footer(); ?>
</body>
</html>
