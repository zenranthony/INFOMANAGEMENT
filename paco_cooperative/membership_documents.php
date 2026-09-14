<?php
require_once 'db.php';
require_once 'pascco_shell.php';

$error = '';
$success = '';
$application = null;


if (isset($_GET['reset'])) {

    unset($_SESSION['membership_application_id']);

    header("Location: membership_documents.php");
    exit;
}

// Start fresh whenever the page is opened normally
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['membership_application_id']);
}

// ----------------------------------------------------
// STEP 1: FIND APPLICATION
// ----------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['find_application'])
) {

    verify_csrf();

    $reference = trim($_POST['application_reference'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($reference === '' || $email === '') {

        $error = 'Please enter your application reference number and email address.';

    } else {

        $stmt = $pdo->prepare("
            SELECT *
            FROM membership_applications
            WHERE application_reference = ?
            AND email = ?
            LIMIT 1
        ");

        $stmt->execute([
            $reference,
            $email
        ]);

        $found = $stmt->fetch();

        if (!$found) {

            $error = 'No membership application was found using those details.';

        } elseif ($found['status'] === 'pending_schedule') {

            $error = 'Your PMES schedule has not been assigned yet. Please wait for PASCCO to contact you.';

        } elseif ($found['status'] === 'scheduled') {

            $error = 'Your PMES has not yet been marked as completed. Please complete your seminar first.';

        } elseif (in_array(
            $found['status'],
            ['approved', 'rejected'],
            true
        )) {

            $error = 'This membership application has already been processed.';

        } else {

            $_SESSION['membership_application_id'] = $found['id'];

            $application = $found;
        }
    }
}

// ----------------------------------------------------
// STEP 2: UPLOAD MEMBERSHIP DOCUMENTS
// ----------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['submit_documents']) &&
    !empty($_SESSION['membership_application_id'])
) {

    verify_csrf();

    $application_id = (int) $_SESSION['membership_application_id'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM membership_applications
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$application_id]);
    $application = $stmt->fetch();

    if (!$application) {

        $error = 'Application not found.';

    } elseif ($application['status'] !== 'pmes_completed') {

        $error = 'Your application is not yet ready for document submission.';

    } else {

        try {

            $upload_directory =
                __DIR__ .
                DIRECTORY_SEPARATOR .
                'uploads' .
                DIRECTORY_SEPARATOR .
                'membership_documents';

            if (!is_dir($upload_directory)) {
                mkdir($upload_directory, 0700, true);
            }

            $save_upload = static function (
                string $field,
                string $label
            ) use ($upload_directory): string {

                if (
                    !isset($_FILES[$field]) ||
                    $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE
                ) {
                    throw new RuntimeException(
                        $label . ' is required.'
                    );
                }

                $file = $_FILES[$field];

                $allowed_types = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'application/pdf' => 'pdf'
                ];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException(
                        'There was a problem uploading ' . $label . '.'
                    );
                }

                if ($file['size'] > 5 * 1024 * 1024) {
                    throw new RuntimeException(
                        $label . ' must not exceed 5 MB.'
                    );
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($file['tmp_name']);

                if (!isset($allowed_types[$mime_type])) {
                    throw new RuntimeException(
                        $label . ' must be JPG, PNG, or PDF.'
                    );
                }

                $file_name =
                    bin2hex(random_bytes(20)) .
                    '.' .
                    $allowed_types[$mime_type];

                $destination =
                    $upload_directory .
                    DIRECTORY_SEPARATOR .
                    $file_name;

                if (!move_uploaded_file(
                    $file['tmp_name'],
                    $destination
                )) {
                    throw new RuntimeException(
                        $label . ' could not be saved.'
                    );
                }

                return 'uploads/membership_documents/' . $file_name;
            };

            $pmes_certificate =
                $save_upload(
                    'pmes_certificate',
                    'PMES Certificate'
                );

            $id_picture =
                $save_upload(
                    'id_picture',
                    '2x2 ID Picture'
                );

            $barangay_certificate =
                $save_upload(
                    'barangay_certificate',
                    'Barangay Certification'
                );

            $birth_certificate =
                $save_upload(
                    'birth_certificate',
                    'PSA Birth Certificate'
                );

            $government_id =
                $save_upload(
                    'government_id',
                    'Government-Issued ID'
                );

            $proof_billing =
                $save_upload(
                    'proof_billing',
                    'Proof of Billing'
                );

            $tin_document =
                $save_upload(
                    'tin_document',
                    'TIN Document'
                );

            $update = $pdo->prepare("
                UPDATE membership_applications
                SET
                    certificate_path = ?,
                    id_picture_path = ?,
                    barangay_certificate_path = ?,
                    birth_certificate_path = ?,
                    government_id_path = ?,
                    proof_billing_path = ?,
                    tin_document_path = ?,
                    status = 'documents_submitted'
                WHERE id = ?
            ");

            $update->execute([
                $pmes_certificate,
                $id_picture,
                $barangay_certificate,
                $birth_certificate,
                $government_id,
                $proof_billing,
                $tin_document,
                $application_id
            ]);

            $success =
                'Your membership requirements were submitted successfully. ' .
                'PASCCO will review your documents.';

            // Reload updated application
            $stmt = $pdo->prepare("
                SELECT *
                FROM membership_applications
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$application_id]);
            $application = $stmt->fetch();

        } catch (Throwable $exception) {

            $error = $exception->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Complete Membership Application</title>

    <?php pascco_global_styles(); ?>

    <style>

        :root {
            --deep-blue: #071d49;
            --blue: #1456a0;
            --gold: #e7b84b;
            --cream: #f4f7fb;
        }

        body {
            background: var(--cream);
            color: #182a45;
            font-family: Georgia, 'Times New Roman', serif;
            margin: 0;
        }

        .page-container {
            margin: 0 auto;
            max-width: 900px;
            padding: 55px 22px 80px;
        }

        .membership-card {
            background: white;
            border-top: 6px solid var(--blue);
            box-shadow: 0 10px 30px rgba(7, 29, 73, .12);
            padding: 38px;
        }

        .eyebrow {
            color: var(--blue);
            font-size: .82rem;
            font-weight: bold;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        h1 {
            color: var(--deep-blue);
            font-size: clamp(2rem, 5vw, 3rem);
            margin: 8px 0 14px;
        }

        .intro {
            color: #56667c;
            line-height: 1.7;
            margin-bottom: 28px;
        }

        .notice {
            background: #fff8df;
            border-left: 5px solid var(--gold);
            color: var(--deep-blue);
            line-height: 1.6;
            margin-bottom: 28px;
            padding: 16px 18px;
        }

        .error {
            background: #f8d7da;
            border-left: 5px solid #b42318;
            color: #721c24;
            margin-bottom: 22px;
            padding: 14px 16px;
        }

        .success {
            background: #e8f5e9;
            border-left: 5px solid #2e7d32;
            color: #1b5e20;
            margin-bottom: 22px;
            padding: 14px 16px;
        }

        .form-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            color: var(--deep-blue);
            font-weight: bold;
        }

        .field input,
        .field select {
            border: 1px solid #b9c6d8;
            border-radius: 4px;
            font: inherit;
            padding: 12px;
            width: 100%;
        }

        button {
            background: var(--blue);
            border: 0;
            color: white;
            cursor: pointer;
            font: inherit;
            font-weight: bold;
            margin-top: 22px;
            padding: 14px 18px;
        }

        button:hover {
            background: var(--deep-blue);
        }

        .application-summary {
            background: #eef3fa;
            border-left: 4px solid var(--blue);
            margin-bottom: 26px;
            padding: 18px;
        }

        .application-summary p {
            margin: 6px 0;
        }

        @media (max-width: 650px) {

            .membership-card {
                padding: 25px 18px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }
        }

    </style>

    <link rel="stylesheet" href="member-panels.css?v=2">
</head>

<body>

<?php pascco_public_header('Login', 'login.php'); ?>

<div class="page-container member-content">

    <section class="membership-card">

        <div class="eyebrow">
            PASCCO Membership
        </div>

        <h1>
            Complete Membership Application
        </h1>

        <p class="intro">
            Applicants who have completed the Pre-Membership Education
            Seminar (PMES) may continue their membership application here.
            Enter the application reference number you received when you
            initially applied.
        </p>

        <div class="notice">
            <strong>Before continuing:</strong>
            Your PMES attendance must first be confirmed by PASCCO.
            Once confirmed, you may submit your PMES certificate and
            required membership documents for review.
        </div>

        <?php if ($error): ?>

    <div class="error">
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>

<?php endif; ?>


<?php if (!$application): ?>

    <h2>Find Your Application</h2>

    <p class="intro">
        Enter the application reference number and email address
        you used when you first applied for PASCCO membership.
    </p>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars(
                $_SESSION['csrf_token'],
                ENT_QUOTES,
                'UTF-8'
            ); ?>"
        >

        <div class="form-grid">

            <div class="field">

                <label for="application-reference">
                    Application Reference Number
                </label>

                <input
                    id="application-reference"
                    type="text"
                    name="application_reference"
                    placeholder="APP-2026-XXXXXX"
                    required
                >

            </div>


            <div class="field">

                <label for="email">
                    Email Address
                </label>

                <input
                    id="email"
                    type="email"
                    name="email"
                    placeholder="example@email.com"
                    required
                >

            </div>

        </div>


        <button
            type="submit"
            name="find_application"
        >
            Find My Application
        </button>

    </form>

<?php endif; ?>


<?php if ($application): ?>

    <div class="application-summary">

        <p>
            <strong>Applicant:</strong>
            <?php echo htmlspecialchars(
                $application['member_name'],
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </p>

        <p>
            <strong>Reference:</strong>
            <?php echo htmlspecialchars(
                $application['application_reference'],
                ENT_QUOTES,
                'UTF-8'
            ); ?>
        </p>

        <p>
            <strong>Status:</strong>
            <?php echo strtoupper(
                htmlspecialchars(
                    $application['status'],
                    ENT_QUOTES,
                    'UTF-8'
                )
            ); ?>
        </p>

    </div>

<?php endif; ?>

<?php if (
    $application &&
    $application['status'] === 'pmes_completed'
): ?>

    <h2>Submit Membership Requirements</h2>

    <p class="intro">
        Upload the required documents below.
        Accepted file types are JPG, PNG, and PDF.
        Maximum file size is 5 MB per document.
    </p>

    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars(
                $_SESSION['csrf_token'],
                ENT_QUOTES,
                'UTF-8'
            ); ?>"
        >

        <div class="form-grid">

            <div class="field">
                <label for="pmes-certificate">
                    PMES Certificate *
                </label>

                <input
                    id="pmes-certificate"
                    type="file"
                    name="pmes_certificate"
                    accept="image/jpeg,image/png,application/pdf"
                    required
                >
            </div>

            <div class="field">
                <label for="id-picture">
                    2x2 ID Picture *
                </label>

                <input
                    id="id-picture"
                    type="file"
                    name="id_picture"
                    accept="image/jpeg,image/png"
                    required
                >
            </div>

            <div class="field">
                <label for="barangay-certificate">
                    Barangay Certification *
                </label>

                <input
                    id="barangay-certificate"
                    type="file"
                    name="barangay_certificate"
                    accept="image/jpeg,image/png,application/pdf"
                    required
                >
            </div>

            <div class="field">
                <label for="birth-certificate">
                    PSA Birth Certificate *
                </label>

                <input
                    id="birth-certificate"
                    type="file"
                    name="birth_certificate"
                    accept="image/jpeg,image/png,application/pdf"
                    required
                >
            </div>

            <div class="field">
                <label for="government-id">
                    Valid Government-Issued ID *
                </label>

                <input
                    id="government-id"
                    type="file"
                    name="government_id"
                    accept="image/jpeg,image/png,application/pdf"
                    required
                >
            </div>

            <div class="field">
                <label for="proof-billing">
                    Proof of Billing *
                </label>

                <input
                    id="proof-billing"
                    type="file"
                    name="proof_billing"
                    accept="image/jpeg,image/png,application/pdf"
                    required
                >
            </div>

            <div class="field">
                <label for="tin-document">
                    TIN Document *
                </label>

                <input
                    id="tin-document"
                    type="file"
                    name="tin_document"
                    accept="image/jpeg,image/png,application/pdf"
                    required
                >
            </div>

        </div>

        <button
            type="submit"
            name="submit_documents"
        >
            Submit Membership Requirements
        </button>

    </form>

<?php endif; ?>

<?php if (
    $application &&
    in_array(
        $application['status'],
        ['documents_submitted', 'under_review'],
        true
    )
): ?>

    <div class="success">

        <strong>
            Requirements Submitted
        </strong>

        <p>
            Your documents have been received and are waiting
            for PASCCO administrator review.
        </p>

    </div>

<?php endif; ?>

    </section>

</div>

<?php pascco_global_footer(); ?>

</body>
</html>
