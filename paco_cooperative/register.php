<?php
require_once 'db.php';
require_once 'pascco_shell.php';

$error = '';
$success = '';

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$preferred_mode = trim($_POST['preferred_mode'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $allowed_modes = [
        'face_to_face',
        'online',
        'either'
    ];

    if (
        $first_name === '' ||
        $last_name === '' ||
        $email === '' ||
        $phone === '' ||
        $address === ''
    ) {

        $error = 'Please complete all required fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif (!in_array($preferred_mode, $allowed_modes, true)) {

        $error = 'Please select your preferred PMES mode.';

    } else {

        try {

            // Check if this email already has an active application
            $check = $pdo->prepare("
                SELECT id
                FROM membership_applications
                WHERE email = ?
                AND status NOT IN ('approved', 'rejected')
                LIMIT 1
            ");

            $check->execute([$email]);

            if ($check->fetch()) {

                $error = 'There is already an active membership application using this email address.';

            } else {

                // Create application reference
                $application_reference =
                    'APP-' .
                    date('Y') .
                    '-' .
                    strtoupper(bin2hex(random_bytes(3)));

                $member_name = $first_name . ' ' . $last_name;

                $insert = $pdo->prepare("
                    INSERT INTO membership_applications
                    (
                        user_id,
                        first_name,
                        last_name,
                        member_name,
                        email,
                        phone,
                        address,
                        preferred_mode,
                        application_reference,
                        status
                    )
                    VALUES (
                        NULL,
                        ?, ?, ?, ?, ?, ?, ?, ?,
                        'pending_schedule'
                    )
                ");

                $insert->execute([
                    $first_name,
                    $last_name,
                    $member_name,
                    $email,
                    $phone,
                    $address,
                    $preferred_mode,
                    $application_reference
                ]);

                $success =
                    'Application submitted successfully. ' .
                    'Your reference number is ' .
                    $application_reference .
                    '. PASCCO will contact you by email regarding available PMES schedules.';

                $first_name = '';
                $last_name = '';
                $email = '';
                $phone = '';
                $address = '';
                $preferred_mode = '';
            }

        } catch (Throwable $exception) {

            $error = 'Unable to submit your application. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?>
    <title>Register | PASCCO</title>
    <style>
        :root { --deep-blue: #071d49; --blue: #1456a0; --gold: #e7b84b; }
        * { box-sizing: border-box; }
        body { background: var(--deep-blue) url('paco2.png') center / cover fixed; color: white; font-family: Georgia, 'Times New Roman', serif; margin: 0; min-height: 100vh; }
        body::before { background: rgba(7,29,73,.8); content: ''; inset: 0; position: fixed; z-index: -1; }
        .site-header { align-items: center; background: linear-gradient(90deg, var(--deep-blue), var(--blue)); display: flex; gap: 24px; min-height: 84px; padding: 12px clamp(18px, 5vw, 70px); }
        .brand { align-items: center; color: white; display: flex; gap: 10px; margin-right: auto; text-decoration: none; }
        .brand img { height: 54px; width: 54px; }
        .brand strong { font-family: Impact, 'Arial Black', sans-serif; font-size: clamp(1.7rem, 4vw, 2.5rem); letter-spacing: .05em; }
        .site-nav { display: flex; gap: 18px; }
        .site-nav a, .header-action { color: white; font-size: .82rem; font-weight: bold; text-decoration: none; }
        .site-nav a:hover, .header-action:hover { color: var(--gold); }
        .header-action { border: 1px solid var(--gold); padding: 9px 12px; }
        .page { align-items: center; display: grid; gap: clamp(30px, 7vw, 100px); grid-template-columns: minmax(0, 1fr) minmax(340px, 520px); margin: 0 auto; max-width: 1120px; min-height: calc(100vh - 84px); padding: 48px 22px 70px; }
        .intro { text-align: center; }
        .intro img { max-width: 150px; width: 35%; }
        .intro h1 { font-size: clamp(2.3rem, 5vw, 4.2rem); line-height: 1.05; margin: 25px 0 0; text-shadow: 3px 4px 0 rgba(0,0,0,.35); }
        .intro p { color: #ffe7a1; letter-spacing: .16em; }
        .card { backdrop-filter: blur(14px); background: rgba(0,0,0,.42); border: 1px solid rgba(231,184,75,.9); border-radius: 22px; box-shadow: 0 18px 50px rgba(0,0,0,.3); padding: clamp(26px, 5vw, 44px); }
        .card h2 { margin: 0 0 22px; text-align: center; }
        .error { background: #fff4cf; border-left: 4px solid var(--gold); color: var(--deep-blue); margin-bottom: 18px; padding: 12px; }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: .9rem; font-weight: bold; margin-bottom: 7px; }
        .field input { background: rgba(255,255,255,.08); border: 0; border-bottom: 2px solid rgba(255,255,255,.75); color: white; font: inherit; outline: 0; padding: 11px 4px; width: 100%; }
        .field select { background: #071d49; border: 0; border-bottom: 2px solid rgba(255,255,255,.75); color: white; font: inherit; outline: 0; padding: 11px 4px; width: 100%; }
        .field input:focus { border-bottom-color: var(--gold); }
        button { background: linear-gradient(90deg, var(--blue), var(--deep-blue), var(--blue)); border: 1px solid var(--gold); border-radius: 30px; color: white; cursor: pointer; font: inherit; font-weight: bold; margin-top: 8px; padding: 14px; width: 100%; }
        .login-link { font-size: .9rem; text-align: center; }
        .login-link a { color: var(--gold); font-weight: bold; }
        @media (max-width: 760px) { .site-nav { display: none; } .page { grid-template-columns: 1fr; min-height: auto; } .card { max-width: 520px; width: 100%; justify-self: center; } .intro h1 { font-size: 2.6rem; } }
        .subtitle {
    color: #f2f4f8;
    line-height: 1.55;
    margin: 0 0 24px;
}

.success-box {
    background: rgba(231, 184, 75, 0.14);
    border: 1px solid #e7b84b;
    border-left: 5px solid #e7b84b;
    border-radius: 8px;
    color: white;
    margin: 22px 0 28px;
    padding: 18px 20px;
}

.success-box strong {
    color: #ffe7a1;
    display: block;
    font-size: 1.05rem;
    margin-bottom: 8px;
}

.success-box p {
    line-height: 1.5;
    margin: 0;
}

.success-box .reference-note {
    color: #dce6f7;
    font-size: 0.9rem;
    margin-top: 10px;
}

.field {
    margin-bottom: 20px;
}

.success-box + .field {
    margin-top: 8px;
}
    </style>
</head>
<body>
    <?php pascco_public_header('Login', 'login.php'); ?>
    <main class="page">
        <section class="intro"><img src="LOGO.png" alt="Paco Savings and Credit Cooperative logo"><h1>JOIN PASCCO</h1><p>BUILDING FINANCIAL CONFIDENCE TOGETHER</p></section>
        <section class="card" aria-labelledby="register-title">
            <h2 id="register-title">Apply for Membership</h2>
                <p class="subtitle">
                    To begin the PASCCO membership process, you must first attend the
                    Pre-Membership Education Seminar (PMES). The PMES introduces applicants
                    to the cooperative, its services, member responsibilities, and the basic
                    requirements for becoming a PASCCO member.
                    After submitting this form, PASCCO will contact you through email with
                    available PMES schedules.
                </p>
            <?php if ($error): ?><div class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="POST" action="register.php">

    <input
        type="hidden"
        name="csrf_token"
        value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"
    >

    <?php if ($error): ?>
        <div class="error">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

   <?php if ($success): ?>
    <div class="success-box">
        <strong>Application Submitted Successfully</strong>

        <p>
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <p class="reference-note">
            Please save your application reference number. You will need it
            later when submitting your PMES certificate and membership documents.
        </p>
    </div>
    <?php endif; ?>

    <div class="field">
        <label for="first-name">First Name</label>
        <input
            id="first-name"
            type="text"
            name="first_name"
            value="<?php echo htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8'); ?>"
            required
        >
    </div>

    <div class="field">
        <label for="last-name">Last Name</label>
        <input
            id="last-name"
            type="text"
            name="last_name"
            value="<?php echo htmlspecialchars($last_name, ENT_QUOTES, 'UTF-8'); ?>"
            required
        >
    </div>

    <div class="field">
        <label for="email">Email Address</label>
        <input
            id="email"
            type="email"
            name="email"
            value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
            required
        >
    </div>

    <div class="field">
        <label for="phone">Contact Number</label>
        <input
            id="phone"
            type="tel"
            name="phone"
            value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>"
            required
        >
    </div>

    <div class="field">
        <label for="address">Complete Address</label>
        <input
            id="address"
            type="text"
            name="address"
            value="<?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?>"
            required
        >
    </div>

    <div class="field">
        <label for="preferred-mode">Preferred PMES Mode</label>

        <select
            id="preferred-mode"
            name="preferred_mode"
            required
        >
            <option value="">Select preferred mode</option>

            <option
                value="face_to_face"
                <?php echo $preferred_mode === 'face_to_face' ? 'selected' : ''; ?>
            >
                Face-to-Face
            </option>

            <option
                value="online"
                <?php echo $preferred_mode === 'online' ? 'selected' : ''; ?>
            >
                Online
            </option>

            <option
                value="either"
                <?php echo $preferred_mode === 'either' ? 'selected' : ''; ?>
            >
                Either
            </option>
        </select>
    </div>

    <button type="submit">
        Submit Application
    </button>

</form>
            <p class="login-link">Already registered? <a href="login.php">Sign in</a></p>
        </section>
    </main>
    <?php pascco_global_footer(); ?>
</body>
</html>
