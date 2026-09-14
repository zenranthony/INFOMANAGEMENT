<?php
require_once 'db.php';
require_once 'pascco_shell.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'member') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error   = '';
$loan_product = trim($_POST['loan_product'] ?? '');
$loan_term = trim($_POST['loan_term'] ?? '6 months');
$loan_purpose = trim($_POST['loan_purpose'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$birth_date = trim($_POST['birth_date'] ?? '');
$civil_status = trim($_POST['civil_status'] ?? '');
$address = trim($_POST['address'] ?? '');
$occupation = trim($_POST['occupation'] ?? '');
$employer = trim($_POST['employer'] ?? '');
$monthly_income = trim($_POST['monthly_income'] ?? '');
$id_type = trim($_POST['id_type'] ?? '');
$id_number = trim($_POST['id_number'] ?? '');
$submitted_loan_id = 0;
$allowed_loan_products = [
    'Business Loan', 'Special Promo Loan', 'Livelihood Loan', 'Quick Loan - Productive',
    'Collateralized Loan', 'Calamity Loan', 'Educational / Tuition Loan', 'Medical / Dental Loan',
    'Quick Loan - Providential', 'Minor Home Improvement Loan', 'Petty Cash Loan', 'Multipurpose Loan',
    'Easyoperability Gadgets Loan', 'Easyoperability BHK Loan', 'Easyoperability Optical Loan',
    'Health & Wellness Loan', 'Memorial Plan', 'Personal Accident Insurance', 'Manila North Green Park',
    'Special Loan',
];

$stmt = $pdo->prepare("SELECT id FROM members WHERE user_id = ?");
$stmt->execute([$user_id]);
$member = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $member) {
    verify_csrf(); // Verify CSRF token

    $amount = floatval($_POST['loan_amount'] ?? 0);
    $loan_product = trim($_POST['loan_product'] ?? '');
    $loan_term = trim($_POST['loan_term'] ?? '');
    $loan_purpose = trim($_POST['loan_purpose'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $civil_status = trim($_POST['civil_status'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $employer = trim($_POST['employer'] ?? '');
    $monthly_income = trim($_POST['monthly_income'] ?? '');
    $id_type = trim($_POST['id_type'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');

    $allowed_id_types = ['Philippine Passport', 'Driver License', 'UMID', 'National ID', 'PRC ID', 'Voter ID', 'Other Government ID'];
    $allowed_civil_statuses = ['Single', 'Married', 'Widowed', 'Separated'];
    $upload_directory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'loan_documents';
    if (!is_dir($upload_directory)) {
        mkdir($upload_directory, 0700, true);
    }

    $save_upload = static function (string $field, string $label) use ($upload_directory): ?string {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException($label . ' is required.');
        }
        $file = $_FILES[$field];
        $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
        $mime_type = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ($file['error'] !== UPLOAD_ERR_OK || !isset($allowed_types[$mime_type]) || $file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException($label . ' must be a JPG, PNG, or PDF file up to 5 MB.');
        }
        $file_name = bin2hex(random_bytes(24)) . '.' . $allowed_types[$mime_type];
        if (!move_uploaded_file($file['tmp_name'], $upload_directory . DIRECTORY_SEPARATOR . $file_name)) {
            throw new RuntimeException('The ' . strtolower($label) . ' could not be saved.');
        }
        return 'uploads/loan_documents/' . $file_name;
    };

    if (!in_array($loan_product, $allowed_loan_products, true)) {
        $error = "Please select a loan product.";
    } elseif ($amount <= 0) {
        $error = "Please enter a valid loan amount.";
    } elseif (!in_array($loan_term, ['6 months', '12 months', '24 months', '36 months'], true)) {
        $error = "Please select a valid loan term.";
    } elseif ($loan_purpose === '' || $contact_number === '' || $birth_date === '' || $civil_status === '' || $address === '' || $occupation === '' || $monthly_income === '' || $id_type === '' || $id_number === '') {
        $error = "Please complete all personal, employment, and identity fields.";
    } elseif (!in_array($civil_status, $allowed_civil_statuses, true) || !in_array($id_type, $allowed_id_types, true)) {
        $error = "Please select valid identity details.";
    } elseif (!is_numeric($monthly_income) || (float) $monthly_income < 0) {
        $error = "Please enter a valid monthly income.";
    } else {
        try {
            $id_document_path = $save_upload('id_document', 'Government ID');
            $proof_income_path = $save_upload('proof_income', 'Proof of income');
            $loan_number = 'LN-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));
            $insert = $pdo->prepare("INSERT INTO loans (member_id, loan_number, loan_product, loan_amount, remaining_balance, status, application_date, loan_term, loan_purpose, contact_number, birth_date, civil_status, address, occupation, employer, monthly_income, id_type, id_number, id_document_path, proof_income_path, identity_status) VALUES (?, ?, ?, ?, ?, 'pending', CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $insert->execute([$member['id'], $loan_number, $loan_product, $amount, $amount, $loan_term, $loan_purpose, $contact_number, $birth_date, $civil_status, $address, $occupation, $employer, (float) $monthly_income, $id_type, $id_number, $id_document_path, $proof_income_path]);
            $submitted_loan_id = (int) $pdo->lastInsertId();
            record_audit($pdo, $user_id, 'loan_application', 'Submitted loan application ' . $loan_number, 'success');
            $message = "Loan application submitted successfully! Your identity documents are now pending verification.";
        } catch (Throwable $exception) {
            foreach ([$id_document_path ?? null, $proof_income_path ?? null] as $uploaded_path) {
                if ($uploaded_path) {
                    $absolute_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $uploaded_path);
                    if (is_file($absolute_path)) {
                        unlink($absolute_path);
                    }
                }
            }
            $error = $exception->getMessage();
        }
    }
}

$history_stmt = $pdo->prepare("SELECT id, loan_number, loan_product, loan_amount, remaining_balance, status, application_date, loan_term, loan_purpose, identity_status FROM loans WHERE member_id = ? ORDER BY id DESC");
$history_stmt->execute([$member['id'] ?? 0]);
$loan_history = $history_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php pascco_global_styles(); ?>
    <style>
        :root { --blue: #1456a0; --deep-blue: #071d49; --gold: #e7b84b; --cream: #f4f7fb; }
        * { box-sizing: border-box; }
        body { font-family: Georgia, 'Times New Roman', serif; margin: 0; background: var(--cream); color: #182a45; }
        .navbar { align-items: center; background: linear-gradient(90deg, var(--deep-blue), var(--blue)); color: white; display: flex; justify-content: space-between; padding: 15px clamp(20px, 6vw, 80px); }
        .navbar a { border: 1px solid var(--gold); color: #ffe7a1; padding: 9px 13px; text-decoration: none; }
        .container { margin: 0 auto; max-width: 1180px; padding: 42px 22px 70px; }
        @media (min-width: 981px) { body .container { margin-left: 290px !important; max-width: calc(100% - 310px) !important; } }
        .loan-layout { align-items: start; display: grid; gap: 22px; grid-template-columns: minmax(0, 1.45fr) minmax(240px, .55fr); }
        .card { background: white; border-top: 6px solid var(--blue); box-shadow: 0 10px 28px rgba(7,29,73,.12); padding: clamp(24px, 5vw, 46px); }
        .application-card { min-width: 0; }
        .loan-guide { background: var(--deep-blue); color: white; padding: 26px; position: static; }
        .loan-guide h2 { color: white; font-size: 1.25rem; }
        .loan-guide ol { line-height: 1.7; margin: 0; padding-left: 22px; }
        .loan-guide li + li { margin-top: 12px; }
        .loan-guide .button { border: 1px solid var(--gold); color: #ffe7a1; display: inline-block; margin-top: 14px; padding: 10px 12px; text-decoration: none; }
        .field-note { color: #5d6b7e; display: block; font-size: .84rem; margin-top: 6px; }
        .form-actions { border-top: 1px solid #d8e0ee; margin-top: 8px; padding-top: 20px; }
        .loan-history { grid-column: 1 / -1; }
        .loan-history table { border-collapse: collapse; width: 100%; }
        .loan-history th, .loan-history td { border-bottom: 1px solid #d8e0ee; padding: 12px 10px; text-align: left; }
        .loan-history th { background: #e6edf8; color: var(--deep-blue); }
        .intro { color: #557167; line-height: 1.6; }
        .form-grid { display: grid; gap: 20px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .input-group { margin-bottom: 18px; }
        .input-group.full { grid-column: 1 / -1; }
        .input-group label { color: var(--deep-blue); display: block; font-weight: bold; margin-bottom: 7px; }
        .input-group input, .input-group select, .input-group textarea { border: 1px solid #b8c8bd; border-radius: 3px; font: inherit; font-size: 16px; padding: 12px; width: 100%; }
        .input-group textarea { min-height: 100px; resize: vertical; }
        .section-heading { border-bottom: 1px solid #d8e0ee; color: var(--blue); grid-column: 1 / -1; margin: 8px 0 -4px; padding-bottom: 8px; }
        .input-group input[type="file"] { background: #f8fafd; padding: 10px; }
        .file-note { color: #5d6b7e; display: block; font-size: .8rem; margin-top: 5px; }
        button { background: var(--blue); border: 0; color: white; cursor: pointer; font: inherit; font-weight: bold; padding: 14px 20px; width: 100%; }
        button:hover { background: var(--deep-blue); }
        .msg { border-radius: 3px; margin-bottom: 18px; padding: 13px; text-align: center; }
        .msg.success { background: #fff4cf; color: #071d49; }
        .msg.danger { background: #f8d7da; color: #721c24; }
        @media (max-width: 760px) { .container { padding: 28px 14px 52px; } .card { padding: 20px 16px; } .loan-layout { grid-template-columns: 1fr; } .loan-guide { position: static; } .loan-history { grid-column: auto; } }
        @media (max-width: 650px) { .form-grid { grid-template-columns: 1fr; } .input-group.full { grid-column: auto; } }
        @media (max-width: 420px) { .loan-guide { padding: 18px 16px; } .loan-guide ol { padding-left: 18px; } .msg { font-size: .95rem; } button { padding: 14px 16px; } }
    </style>
    <link rel="stylesheet" href="member-panels.css?v=2">
</head>
<body>

<?php pascco_member_header('Loan Application'); ?>

<div class="container member-content">
    <div class="loan-layout">
    <section class="card application-card">
        <div style="color:#1456a0;font-size:.82rem;font-weight:bold;letter-spacing:.15em;text-transform:uppercase">Member service</div>
        <h1>Loan Application</h1>
        <p class="intro">Choose the loan product that fits your need. Your application will be reviewed by the PASCCO loan committee.</p>

        <?php if ($message): ?><div class="msg success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?><?php if ($submitted_loan_id > 0): ?> <a href="loan_application_print.php?loan_id=<?php echo $submitted_loan_id; ?>" target="_blank" rel="noopener">Open application form</a><?php endif; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <form method="POST" action="apply_loan.php" enctype="multipart/form-data">
            <!-- Hidden Security Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-grid">
                <h2 class="section-heading">Loan Request</h2>
                <div class="input-group">
                    <label for="loan-product">Loan Product</label>
                    <select id="loan-product" name="loan_product" required>
                        <option value="">Select a product</option>
                        <option value="Business Loan" <?php echo $loan_product === 'Business Loan' ? 'selected' : ''; ?>>Business Loan</option>
                        <option value="Special Promo Loan" <?php echo $loan_product === 'Special Promo Loan' ? 'selected' : ''; ?>>Special Promo Loan</option>
                        <option value="Livelihood Loan" <?php echo $loan_product === 'Livelihood Loan' ? 'selected' : ''; ?>>Livelihood Loan</option>
                        <option value="Quick Loan - Productive" <?php echo $loan_product === 'Quick Loan - Productive' ? 'selected' : ''; ?>>Quick Loan - Productive</option>
                        <option value="Collateralized Loan" <?php echo $loan_product === 'Collateralized Loan' ? 'selected' : ''; ?>>Collateralized Loan</option>
                        <option value="Calamity Loan" <?php echo $loan_product === 'Calamity Loan' ? 'selected' : ''; ?>>Calamity Loan</option>
                        <option value="Educational / Tuition Loan" <?php echo $loan_product === 'Educational / Tuition Loan' ? 'selected' : ''; ?>>Educational / Tuition Loan</option>
                        <option value="Medical / Dental Loan" <?php echo $loan_product === 'Medical / Dental Loan' ? 'selected' : ''; ?>>Medical / Dental Loan</option>
                        <option value="Quick Loan - Providential" <?php echo $loan_product === 'Quick Loan - Providential' ? 'selected' : ''; ?>>Quick Loan - Providential</option>
                        <option value="Minor Home Improvement Loan" <?php echo $loan_product === 'Minor Home Improvement Loan' ? 'selected' : ''; ?>>Minor Home Improvement Loan</option>
                        <option value="Petty Cash Loan" <?php echo $loan_product === 'Petty Cash Loan' ? 'selected' : ''; ?>>Petty Cash Loan</option>
                        <option value="Multipurpose Loan" <?php echo $loan_product === 'Multipurpose Loan' ? 'selected' : ''; ?>>Multipurpose Loan</option>
                        <option value="Easyoperability Gadgets Loan" <?php echo $loan_product === 'Easyoperability Gadgets Loan' ? 'selected' : ''; ?>>Easyoperability Gadgets Loan</option>
                        <option value="Easyoperability BHK Loan" <?php echo $loan_product === 'Easyoperability BHK Loan' ? 'selected' : ''; ?>>Easyoperability BHK Loan</option>
                        <option value="Easyoperability Optical Loan" <?php echo $loan_product === 'Easyoperability Optical Loan' ? 'selected' : ''; ?>>Easyoperability Optical Loan</option>
                        <option value="Health &amp; Wellness Loan" <?php echo $loan_product === 'Health & Wellness Loan' ? 'selected' : ''; ?>>Health &amp; Wellness Loan</option>
                        <option value="Memorial Plan" <?php echo $loan_product === 'Memorial Plan' ? 'selected' : ''; ?>>Memorial Plan</option>
                        <option value="Personal Accident Insurance" <?php echo $loan_product === 'Personal Accident Insurance' ? 'selected' : ''; ?>>Personal Accident Insurance</option>
                        <option value="Manila North Green Park" <?php echo $loan_product === 'Manila North Green Park' ? 'selected' : ''; ?>>Manila North Green Park</option>
                        <option value="Special Loan" <?php echo $loan_product === 'Special Loan' ? 'selected' : ''; ?>>Special Loan</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="loan-amount">Desired Loan Amount (PHP)</label>
                    <input id="loan-amount" type="number" step="0.01" min="1" name="loan_amount" placeholder="e.g. 10000" required>
                    <small class="field-note">Enter the amount you want to request. Final approval depends on eligibility and review.</small>
                </div>
                <div class="input-group">
                    <label for="loan-term">Preferred Term</label>
                    <select id="loan-term" name="loan_term">
                        <option <?php echo $loan_term === '6 months' ? 'selected' : ''; ?>>6 months</option><option <?php echo $loan_term === '12 months' ? 'selected' : ''; ?>>12 months</option><option <?php echo $loan_term === '24 months' ? 'selected' : ''; ?>>24 months</option><option <?php echo $loan_term === '36 months' ? 'selected' : ''; ?>>36 months</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="contact-number">Contact Number</label>
                    <input id="contact-number" type="tel" name="contact_number" value="<?php echo htmlspecialchars($contact_number, ENT_QUOTES, 'UTF-8'); ?>" placeholder="09XX XXX XXXX" required>
                </div>
                <div class="input-group full">
                    <label for="loan-purpose">Purpose of Loan</label>
                    <textarea id="loan-purpose" name="loan_purpose" placeholder="Tell us briefly how the loan will be used" required><?php echo htmlspecialchars($loan_purpose, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <h2 class="section-heading">Personal and Employment Details</h2>
                <div class="input-group"><label for="birth-date">Birth Date</label><input id="birth-date" type="date" name="birth_date" value="<?php echo htmlspecialchars($birth_date, ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="input-group"><label for="civil-status">Civil Status</label><select id="civil-status" name="civil_status" required><option value="">Select status</option><?php foreach (['Single', 'Married', 'Widowed', 'Separated'] as $status): ?><option value="<?php echo $status; ?>" <?php echo $civil_status === $status ? 'selected' : ''; ?>><?php echo $status; ?></option><?php endforeach; ?></select></div>
                <div class="input-group full"><label for="address">Complete Home Address</label><input id="address" name="address" value="<?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="input-group"><label for="occupation">Occupation</label><input id="occupation" name="occupation" value="<?php echo htmlspecialchars($occupation, ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="input-group"><label for="employer">Employer or Business Name</label><input id="employer" name="employer" value="<?php echo htmlspecialchars($employer, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter N/A if not applicable"></div>
                <div class="input-group"><label for="monthly-income">Monthly Income (PHP)</label><input id="monthly-income" type="number" min="0" step="0.01" name="monthly_income" value="<?php echo htmlspecialchars($monthly_income, ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <h2 class="section-heading">Identity Verification</h2>
                <div class="input-group"><label for="id-type">Government ID Type</label><select id="id-type" name="id_type" required><option value="">Select ID type</option><?php foreach (['Philippine Passport', 'Driver License', 'UMID', 'National ID', 'PRC ID', 'Voter ID', 'Other Government ID'] as $option): ?><option value="<?php echo $option; ?>" <?php echo $id_type === $option ? 'selected' : ''; ?>><?php echo $option; ?></option><?php endforeach; ?></select></div>
                <div class="input-group"><label for="id-number">Government ID Number</label><input id="id-number" name="id_number" value="<?php echo htmlspecialchars($id_number, ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="input-group"><label for="id-document">Upload Government ID</label><input id="id-document" type="file" name="id_document" accept="image/jpeg,image/png,application/pdf" required><small class="file-note">JPG, PNG, or PDF. Maximum 5 MB.</small></div>
                <div class="input-group"><label for="proof-income">Upload Proof of Income</label><input id="proof-income" type="file" name="proof_income" accept="image/jpeg,image/png,application/pdf" required><small class="file-note">Payslip, certificate, or business proof. Maximum 5 MB.</small></div>
            </div>
            <div class="form-actions"><button type="submit">Submit Application</button></div>
        </form>
    </section>

    <aside class="loan-guide">
        <h2>Application Steps</h2>
        <ol><li>Choose a loan product and amount.</li><li>Provide your preferred term and purpose.</li><li>Submit for committee review.</li><li>Track the status below after submission.</li></ol>
        <p><a class="button secondary" href="credit.php">Review loan products</a></p>
    </aside>

    <section class="card loan-history">
        <h2>My Loan Applications</h2>
        <?php if ($loan_history): ?>
            <div style="overflow-x:auto"><table style="border-collapse:collapse;width:100%"><thead><tr><th>Loan</th><th>Product</th><th>Amount</th><th>Remaining</th><th>Status</th><th>Applied</th></tr></thead><tbody>
            <?php foreach ($loan_history as $loan): ?><tr><td><?php echo htmlspecialchars($loan['loan_number'], ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars($loan['loan_term'], ENT_QUOTES, 'UTF-8'); ?></small></td><td><?php echo htmlspecialchars($loan['loan_product'] ?? 'Loan application', ENT_QUOTES, 'UTF-8'); ?></td><td>PHP <?php echo number_format($loan['loan_amount'], 2); ?></td><td>PHP <?php echo number_format($loan['remaining_balance'], 2); ?></td><td><?php echo strtoupper(htmlspecialchars($loan['status'], ENT_QUOTES, 'UTF-8')); ?><br><small>Identity: <?php echo htmlspecialchars($loan['identity_status'] ?? 'pending', ENT_QUOTES, 'UTF-8'); ?></small></td><td><?php echo htmlspecialchars($loan['application_date'], ENT_QUOTES, 'UTF-8'); ?><br><a href="loan_application_print.php?loan_id=<?php echo (int) $loan['id']; ?>" target="_blank" rel="noopener">Print / Save PDF</a></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php else: ?><p>No loan applications yet.</p><?php endif; ?>
    </section>
    </div>
</div>

<?php pascco_global_footer(); ?>

</body>
</html>
