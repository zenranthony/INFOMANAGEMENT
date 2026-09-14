<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/pascco_shell.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'member') {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$message = '';
$error = '';
$selected_date = trim($_POST['schedule_date'] ?? '');

$application_stmt = $pdo->prepare('SELECT * FROM membership_applications WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$application_stmt->execute([$user_id]);
$application = $application_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if ($selected_date === '') {
        $error = 'Please choose a Saturday schedule.';
    } else {
        try {
            $date_obj = new DateTimeImmutable($selected_date);
            if ((int) $date_obj->format('N') !== 6) {
                throw new RuntimeException('You can only select a Saturday schedule.');
            }

            $schedule_label = $date_obj->format('F j, Y') . ' (Saturday)';
            $update = $pdo->prepare('UPDATE membership_applications SET preferred_schedule_date = ?, pmes_schedule = ?, status = CASE WHEN status = "applied" THEN "scheduled" ELSE status END, updated_at = NOW() WHERE user_id = ? ORDER BY id DESC LIMIT 1');
            $update->execute([$selected_date, $schedule_label, $user_id]);

            record_audit($pdo, $user_id, 'membership_schedule', 'Selected PMES schedule: ' . $schedule_label, 'success');
            $message = 'Your preferred Saturday schedule has been saved. You will be notified once the PMES schedule is confirmed.';
            $selected_date = '';
            $application_stmt->execute([$user_id]);
            $application = $application_stmt->fetch();
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

$today = new DateTimeImmutable('today');
$saturdays = [];
for ($i = 0; $i < 12; $i++) {
    $date = $today->modify('+' . $i . ' weeks');
    if ((int) $date->format('N') === 6) {
        $saturdays[] = $date->format('Y-m-d');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?>
    <title>Choose PMES Schedule | PASCCO</title>
    <style>
        body { background: #f4f7fb; color: #182a45; font-family: Georgia, 'Times New Roman', serif; margin: 0; }
        .page { margin: 0 auto; max-width: 1000px; padding: 42px 22px 70px; }
        .card { background: white; border-top: 5px solid #1456a0; box-shadow: 0 6px 20px rgba(7,29,73,.09); padding: clamp(22px, 5vw, 38px); }
        .eyebrow { color: #1456a0; font-size: .8rem; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; }
        .schedule-grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-top: 18px; }
        .day-option { background: #f8fbff; border: 1px solid #d8e0ee; border-radius: 8px; padding: 18px 14px; text-align: center; }
        .day-option strong { display: block; font-size: 1.1rem; margin-bottom: 6px; }
        .day-option input { accent-color: #1456a0; }
        .msg { margin-bottom: 18px; padding: 12px; }
        .msg.success { background: #fff4cf; color: #071d49; }
        .msg.error { background: #f8d7da; color: #721c24; }
        button { background: #1456a0; border: 0; color: white; cursor: pointer; font: inherit; font-weight: bold; margin-top: 18px; padding: 14px; width: 100%; }
        .status-box { background: #e6edf8; border-left: 4px solid #e7b84b; margin-top: 18px; padding: 14px; }
        @media (max-width: 720px) {
            .page { padding: 28px 14px 52px; }
            .card { padding: 20px 16px; }
            .schedule-grid { grid-template-columns: 1fr; }
        }
    </style>
    <link rel="stylesheet" href="member-panels.css?v=2">
</head>
<body>
<?php pascco_member_header('Choose PMES Schedule'); ?>
<main class="page member-content">
    <div class="eyebrow">Membership</div>
    <h1>Choose Your PMES Schedule</h1>
    <section class="card">
        <?php if ($message): ?><div class="msg success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="msg error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <p>Select one of the available Saturday schedules below. The cooperative will confirm the final appointment once it is available.</p>

        <form method="POST" action="membership_schedule.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="schedule-grid">
                <?php foreach ($saturdays as $date_value): ?>
                    <label class="day-option">
                        <input type="radio" name="schedule_date" value="<?php echo htmlspecialchars($date_value, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($application['preferred_schedule_date'] ?? '') === $date_value ? 'checked' : ''; ?> required>
                        <strong><?php echo htmlspecialchars(date('M j', strtotime($date_value)), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php echo htmlspecialchars(date('D', strtotime($date_value)), ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit">Save Schedule</button>
        </form>

        <?php if ($application): ?>
            <div class="status-box">
                <strong>Current selection:</strong>
                <?php echo htmlspecialchars($application['preferred_schedule_date'] ?: 'No date selected yet', ENT_QUOTES, 'UTF-8'); ?><br>
                <strong>Status:</strong> <?php echo strtoupper(htmlspecialchars($application['status'], ENT_QUOTES, 'UTF-8')); ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php pascco_global_footer(); ?>
</body>
</html>
