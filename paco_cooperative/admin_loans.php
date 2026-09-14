<?php
require_once 'db.php';
require_once 'pascco_shell.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied: You must be logged in as an Admin to view this page. <a href='login.php'>Login here</a>");
}

$message = '';
$query = trim($_GET['q'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf(); // Verify CSRF Token

    $loan_id   = intval($_POST['loan_id']);
    $action    = $_POST['action'];

    $loan_check = $pdo->prepare("SELECT member_id, loan_amount, identity_status FROM loans WHERE id = ? AND status = 'pending'");
    $loan_check->execute([$loan_id]);
    $loan = $loan_check->fetch();

    if (!$loan) {
        $message = "This loan is no longer pending or could not be found.";
    } elseif ($action === 'verify_identity' || $action === 'reject_identity') {
        $identity_status = $action === 'verify_identity' ? 'verified' : 'rejected';
        $identity_notes = trim($_POST['identity_notes'] ?? '');
        if ($identity_status === 'rejected' && $identity_notes === '') {
            $message = 'Add a note explaining why the identity documents were rejected.';
        } else {
            $identity_update = $pdo->prepare('UPDATE loans SET identity_status = ?, identity_verified_by = ?, identity_verified_at = NOW(), identity_notes = ? WHERE id = ? AND status = \'pending\'');
            $identity_update->execute([$identity_status, (int) $_SESSION['user_id'], $identity_notes, $loan_id]);
            record_audit($pdo, (int) $_SESSION['user_id'], 'identity_review', ucfirst($identity_status) . ' identity for loan ' . $loan_id, 'success');
            $message = $identity_status === 'verified' ? 'Identity verified. The loan can now be approved.' : 'Identity rejected. The member must submit corrected documents.';
        }
    } elseif ($action === 'approve') {
        if ($loan['identity_status'] !== 'verified') {
            $message = 'Verify the member identity documents before approving this loan.';
        } else {
        $member_id = (int) $loan['member_id'];
        $amount = (float) $loan['loan_amount'];
        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare("UPDATE loans SET status = 'approved' WHERE id = ? AND status = 'pending' AND identity_status = 'verified'");
            $update->execute([$loan_id]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('This loan was already processed by another administrator.');
            }

            $acc_stmt = $pdo->prepare("SELECT id FROM accounts WHERE member_id = ? AND status = 'active'");
            $acc_stmt->execute([$member_id]);
            $acc = $acc_stmt->fetch();

            if (!$acc) {
                throw new RuntimeException('The member has no active savings account.');
            }

            $ref_number = 'OR-' . date('YmdHis') . '-' . random_int(100000, 999999);
            $tx = $pdo->prepare("
                INSERT INTO transactions (account_id, transaction_type, amount, recipient_account_id, description, status, reference_number, transaction_date, created_at)
                VALUES (?, 'deposit', ?, NULL, 'Loan Disbursement', 'completed', ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
            ");
            $tx->execute([$acc['id'], $amount, $ref_number]);
            $balance_update = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ? AND status = 'active'");
            $balance_update->execute([$amount, (int) $acc['id']]);
            $pdo->commit();
            record_audit($pdo, (int) $_SESSION['user_id'], 'loan_approval', 'Approved and disbursed loan ' . $loan_id, 'success');
            $message = "Loan approved and funds disbursed to the member's account.";
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = $exception->getMessage();
        }
        }

    } elseif ($action === 'reject') {
        $update = $pdo->prepare("UPDATE loans SET status = 'rejected' WHERE id = ? AND status = 'pending'");
        $update->execute([$loan_id]);
        record_audit($pdo, (int) $_SESSION['user_id'], 'loan_rejection', 'Rejected loan ' . $loan_id, 'success');

        $message = "Loan application rejected.";
    }
}

$loan_query = "
    SELECT 
        l.id AS loan_id, 
        l.loan_number, 
        l.loan_product,
        l.loan_amount,
        l.loan_term,
        l.loan_purpose,
        l.contact_number,
        l.birth_date,
        l.civil_status,
        l.address,
        l.occupation,
        l.employer,
        l.monthly_income,
        l.id_type,
        l.id_number,
        l.id_document_path,
        l.proof_income_path,
        l.identity_status,
        l.identity_notes,
        l.application_date, 
        m.id AS member_id,
        m.first_name, 
        m.last_name, 
        m.member_number 
    FROM loans l
    JOIN members m ON l.member_id = m.id
    WHERE l.status = 'pending'";
$loan_parameters = [];
if ($query !== '') {
    $loan_query .= " AND (l.loan_number LIKE ? OR l.loan_product LIKE ? OR m.first_name LIKE ? OR m.last_name LIKE ? OR m.member_number LIKE ?)";
    $search = '%' . $query . '%';
    $loan_parameters = [$search, $search, $search, $search, $search];
}
$loan_query .= ' ORDER BY l.id DESC';
$stmt = $pdo->prepare($loan_query);
$stmt->execute($loan_parameters);
$pending_loans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?>
    <title>Admin - Loan Approvals</title>
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
        .page-heading { align-items: end; display: flex; justify-content: space-between; margin-bottom: 25px; }
        .eyebrow { color: var(--blue); font-size: .8rem; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; }
        .page-heading h1 { color: var(--deep-blue); font-size: clamp(2rem, 4vw, 3.2rem); margin: 8px 0 0; }
        .back-link { border: 1px solid var(--blue); color: var(--blue); font-weight: bold; padding: 11px 14px; text-decoration: none; }
        .card { background: white; border: 1px solid var(--line); border-top: 4px solid var(--blue); padding: 25px; border-radius: 6px; box-shadow: 0 4px 14px rgba(7,29,73,.08); }
        .approval-toolbar { align-items: end; display: flex; gap: 18px; justify-content: space-between; margin-bottom: 22px; }
        .approval-toolbar h2 { margin: 0; }
        .search-form { display: flex; gap: 8px; max-width: 480px; width: 100%; }
        .search-form input { border: 1px solid #b8c8d9; border-radius: 3px; flex: 1; font: inherit; min-width: 0; padding: 11px; }
        .search-form button { background: var(--deep-blue); border: 0; color: white; cursor: pointer; font: inherit; font-weight: bold; padding: 11px 16px; }
        .msg { padding: 13px; background: #fff4cf; color: #071d49; border-left: 4px solid var(--gold); margin-bottom: 15px; text-align: center; font-weight: bold; }
        .empty-state { color: #5d6b7e; padding: 26px 0 8px; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 14px 12px; text-align: left; border-bottom: 1px solid var(--line); }
        th { background: #e6edf8; color: #071d49; }
        .member-name { color: var(--deep-blue); font-weight: bold; }
        .amount { color: var(--blue); font-weight: bold; white-space: nowrap; }
        .btn-approve { background: var(--gold); color: var(--deep-blue); border: none; padding: 9px 13px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-reject { background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .actions { display: flex; gap: 8px; }
        .approval-list { display: grid; gap: 14px; }
        .approval-item { background: #f8fafd; border: 1px solid var(--line); border-left: 5px solid var(--gold); padding: 18px; }
        .approval-item header { align-items: start; display: flex; gap: 18px; justify-content: space-between; }
        .approval-item h3 { color: var(--deep-blue); margin: 0; }
        .approval-meta { color: #5d6b7e; font-size: .9rem; margin: 4px 0 0; }
        .approval-details { display: grid; gap: 10px 18px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin: 16px 0; }
        .approval-details div { border-top: 1px solid var(--line); padding-top: 9px; }
        .approval-details small { color: #5d6b7e; display: block; }
        .approval-details strong { color: var(--deep-blue); display: block; margin-top: 3px; }
        .approval-purpose { background: white; border-left: 3px solid var(--blue); line-height: 1.5; margin: 0 0 16px; padding: 10px 12px; }
        .identity-panel { background: #fffdf6; border: 1px solid #ead89d; margin: 16px 0; padding: 14px; }
        .identity-panel h4 { color: var(--deep-blue); margin: 0 0 10px; }
        .identity-grid { display: grid; gap: 8px 16px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .identity-grid small { color: #5d6b7e; display: block; }
        .identity-grid strong { color: var(--deep-blue); display: block; margin-top: 2px; }
        .document-links { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        .document-links a { color: var(--blue); font-weight: bold; }
        .identity-status { font-size: .8rem; font-weight: bold; text-transform: uppercase; }
        .identity-status.verified { color: #1b6e3a; }
        .identity-status.rejected { color: #a22929; }
        .identity-form { align-items: end; display: flex; gap: 8px; margin-top: 12px; }
        .identity-form textarea { border: 1px solid #b8c8d9; font: inherit; min-height: 42px; padding: 8px; resize: vertical; width: 100%; }
        .btn-verify { background: #237a45; color: white; }
        .approval-actions { display: flex; gap: 8px; }
        .table-wrap { overflow-x: auto; }
        @media (max-width: 800px) { .navbar { align-items: flex-start; flex-wrap: wrap; } .admin-nav { justify-content: flex-start; order: 3; width: 100%; } .page-heading, .approval-toolbar { align-items: flex-start; flex-direction: column; gap: 18px; } .search-form { max-width: none; } .approval-details, .identity-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .identity-form { align-items: stretch; flex-direction: column; } }
        @media (max-width: 500px) { .approval-item header { flex-direction: column; } .approval-actions { flex-direction: column; } .approval-actions button { width: 100%; } }
    </style>
</head>
<body>

<?php pascco_admin_header(); ?>

<div class="container">
    <section class="page-heading">
        <div><div class="eyebrow">Admin operations</div><h1>Loan Approvals</h1></div>
        <a class="back-link" href="admin_dashboard.php">Back to Dashboard</a>
    </section>
    <div class="card">
        <div class="approval-toolbar"><div><h2>Pending Loan Applications</h2><p><?php echo count($pending_loans); ?> application<?php echo count($pending_loans) === 1 ? '' : 's'; ?> waiting for review.</p></div><form class="search-form" method="GET" action="admin_loans.php"><input name="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search member, loan, or product"><button type="submit">Search</button></form></div>

        <?php if ($message): ?><div class="msg"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <?php if (count($pending_loans) > 0): ?>
            <div class="approval-list">
                <?php foreach ($pending_loans as $loan): ?>
                    <article class="approval-item">
                        <header><div><h3><?php echo htmlspecialchars($loan['loan_product'], ENT_QUOTES, 'UTF-8'); ?></h3><p class="approval-meta"><?php echo htmlspecialchars($loan['loan_number'], ENT_QUOTES, 'UTF-8'); ?> · Applied <?php echo htmlspecialchars($loan['application_date'], ENT_QUOTES, 'UTF-8'); ?></p><p><a href="loan_application_print.php?loan_id=<?php echo (int) $loan['loan_id']; ?>" target="_blank" rel="noopener">View / Print Application Form</a></p></div><div class="amount">₱<?php echo number_format($loan['loan_amount'], 2); ?></div></header>
                        <div class="approval-details"><div><small>Member</small><strong><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name'], ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Member ID</small><strong><?php echo htmlspecialchars($loan['member_number'], ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Term</small><strong><?php echo htmlspecialchars($loan['loan_term'], ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Contact</small><strong><?php echo htmlspecialchars($loan['contact_number'], ENT_QUOTES, 'UTF-8'); ?></strong></div></div>
                        <section class="identity-panel"><h4>Identity and eligibility review <span class="identity-status <?php echo htmlspecialchars($loan['identity_status'], ENT_QUOTES, 'UTF-8'); ?>">(<?php echo htmlspecialchars($loan['identity_status'], ENT_QUOTES, 'UTF-8'); ?>)</span></h4><div class="identity-grid"><div><small>Birth date</small><strong><?php echo htmlspecialchars($loan['birth_date'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Civil status</small><strong><?php echo htmlspecialchars($loan['civil_status'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Occupation</small><strong><?php echo htmlspecialchars($loan['occupation'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Monthly income</small><strong>PHP <?php echo number_format((float) ($loan['monthly_income'] ?? 0), 2); ?></strong></div><div><small>Employer / business</small><strong><?php echo htmlspecialchars($loan['employer'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>ID type</small><strong><?php echo htmlspecialchars($loan['id_type'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>ID number</small><strong><?php echo htmlspecialchars($loan['id_number'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong></div><div><small>Address</small><strong><?php echo htmlspecialchars($loan['address'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong></div></div><div class="document-links"><?php if ($loan['id_document_path']): ?><a href="loan_document.php?loan_id=<?php echo (int) $loan['loan_id']; ?>&type=id" target="_blank" rel="noopener">View Government ID</a><?php endif; ?><?php if ($loan['proof_income_path']): ?><a href="loan_document.php?loan_id=<?php echo (int) $loan['loan_id']; ?>&type=income" target="_blank" rel="noopener">View Proof of Income</a><?php endif; ?></div><form method="POST" class="identity-form"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="loan_id" value="<?php echo (int) $loan['loan_id']; ?>"><textarea name="identity_notes" placeholder="Verification note, required when rejecting"><?php echo htmlspecialchars($loan['identity_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea><button type="submit" name="action" value="verify_identity" class="btn-verify">Verify Identity</button><button type="submit" name="action" value="reject_identity" class="btn-reject">Reject Identity</button></form></section>
                        <p class="approval-purpose"><strong>Purpose:</strong> <?php echo htmlspecialchars($loan['loan_purpose'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <form method="POST" action="admin_loans.php" class="approval-actions"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="loan_id" value="<?php echo (int) $loan['loan_id']; ?>"><button type="submit" name="action" value="approve" class="btn-approve" <?php echo $loan['identity_status'] !== 'verified' ? 'disabled title="Verify identity first"' : ''; ?>>Approve &amp; Disburse</button><button type="submit" name="action" value="reject" class="btn-reject" onclick="return confirm('Reject this loan application?');">Reject Application</button></form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">All loan applications are up to date. No pending requests found.</p>
        <?php endif; ?>
    </div>
</div>

<?php pascco_global_footer(); ?>

</body>
</html>