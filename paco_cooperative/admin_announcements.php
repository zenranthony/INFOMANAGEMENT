<?php
require_once 'db.php';
require_once 'pascco_shell.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}


$message = '';
$error = '';
$upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'announcements';
$upload_url = 'uploads/announcements/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $announcement_id = (int) ($_POST['announcement_id'] ?? 0);

    if ($action === 'delete' && $announcement_id > 0) {
        $delete = $pdo->prepare("UPDATE announcements SET status = 'archived' WHERE id = ?");
        $delete->execute([$announcement_id]);
        $message = 'Announcement archived.';
    } elseif ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $image_path = null;

        if ($title === '' || $body === '') {
            $error = 'Title and announcement details are required.';
        } elseif (!empty($_FILES['image']['name'])) {
            $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $file_type = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK || !isset($allowed_types[$file_type]) || $_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = 'Upload a JPG, PNG, or WEBP image up to 5 MB.';
            } else {
                $file_name = bin2hex(random_bytes(16)) . '.' . $allowed_types[$file_type];
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . DIRECTORY_SEPARATOR . $file_name)) {
                    $error = 'The announcement image could not be saved.';
                } else {
                    $image_path = $upload_url . $file_name;
                }
            }
        }

        if ($error === '') {
            $insert = $pdo->prepare("INSERT INTO announcements (title, body, image_path, created_by) VALUES (?, ?, ?, ?)");
            $insert->execute([$title, $body, $image_path, $_SESSION['user_id']]);
            $message = 'Announcement published for members.';
        }
    }
}

$announcements = $pdo->query("SELECT id, title, body, image_path, published_at, status FROM announcements ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?>
    <title>Manage Announcements | PASCCO</title>
    <style>
        body { background: #f4f7fb; color: #182a45; font-family: Georgia, 'Times New Roman', serif; margin: 0; }
        .admin-page { margin: 0 auto; max-width: 1120px; padding: 44px 22px 70px; }
        .heading { align-items: end; display: flex; justify-content: space-between; margin-bottom: 24px; }
        .heading h1 { color: #071d49; margin: 8px 0 0; }
        .eyebrow { color: #1456a0; font-size: .8rem; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; }
        .columns { display: grid; gap: 22px; grid-template-columns: minmax(280px, .7fr) minmax(0, 1.3fr); }
        .card { background: white; border: 1px solid #d8e0ee; border-top: 4px solid #1456a0; padding: 24px; }
        label { color: #071d49; display: block; font-weight: bold; margin: 14px 0 6px; }
        input, textarea { border: 1px solid #b8c8d9; border-radius: 3px; font: inherit; padding: 11px; width: 100%; }
        textarea { min-height: 160px; resize: vertical; }
        button { background: #e7b84b; border: 0; color: #071d49; cursor: pointer; font: inherit; font-weight: bold; margin-top: 16px; padding: 12px 16px; width: 100%; }
        .notice { background: #fff4cf; border-left: 4px solid #e7b84b; color: #071d49; margin-bottom: 18px; padding: 12px; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; margin-bottom: 18px; padding: 12px; }
        .managed-item { border-bottom: 1px solid #d8e0ee; padding: 0 0 18px; margin-bottom: 18px; }
        .managed-item:last-child { border-bottom: 0; margin-bottom: 0; }
        .managed-item h2 { color: #071d49; font-size: 1.2rem; margin: 0 0 6px; }
        .managed-item img { max-height: 130px; max-width: 100%; object-fit: cover; }
        .managed-item p { line-height: 1.55; white-space: pre-line; }
        .archive { background: #1456a0; color: white; margin-top: 0; width: auto; }
        @media (max-width: 760px) { .columns { grid-template-columns: 1fr; } .heading { align-items: flex-start; flex-direction: column; } }
    </style>
</head>
<body>
<?php pascco_admin_header(); ?>
<main class="admin-page">
    <section class="heading"><div><div class="eyebrow">Community management</div><h1>Announcements</h1></div><a href="announcements.php">View Member Page</a></section>
    <?php if ($message): ?><div class="notice" role="status"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <div class="columns">
        <section class="card"><h2>Publish Announcement</h2><form method="POST" action="admin_announcements.php" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="create"><label for="title">Title</label><input id="title" name="title" maxlength="160" required><label for="body">Announcement Details</label><textarea id="body" name="body" required></textarea><label for="image">Picture (optional)</label><input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp"><button type="submit">Publish to Members</button></form></section>
        <section class="card"><h2>Published and Archived</h2><?php if ($announcements): ?><?php foreach ($announcements as $announcement): ?><article class="managed-item"><?php if ($announcement['image_path']): ?><img src="<?php echo htmlspecialchars($announcement['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Announcement image"><?php endif; ?><h2><?php echo htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8'); ?></h2><small><?php echo htmlspecialchars($announcement['published_at']); ?> · <?php echo htmlspecialchars($announcement['status']); ?></small><p><?php echo htmlspecialchars($announcement['body'], ENT_QUOTES, 'UTF-8'); ?></p><?php if ($announcement['status'] === 'published'): ?><form method="POST" action="admin_announcements.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>"><button class="archive" type="submit">Archive</button></form><?php endif; ?></article><?php endforeach; ?><?php else: ?><p>No announcements have been published.</p><?php endif; ?></section>
    </div>
</main>
<?php pascco_global_footer(); ?>
</body>
</html>
