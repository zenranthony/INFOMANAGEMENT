<?php
require_once 'db.php';
require_once 'pascco_shell.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header('Location: login.php');
    exit;
}


$stmt = $pdo->query("SELECT title, body, image_path, published_at FROM announcements WHERE status = 'published' ORDER BY published_at DESC, id DESC");
$announcements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php pascco_global_styles(); ?>
    <title>Community Announcements | PASCCO</title>
    <style>
        body { background: #f4f7fb; color: #182a45; font-family: Georgia, 'Times New Roman', serif; margin: 0; }
        .community { margin: 0 auto; max-width: 1120px; padding: 48px 22px 70px; }
        .community-heading { align-items: end; display: flex; justify-content: space-between; margin-bottom: 28px; }
        .community-heading h1 { color: #071d49; font-size: clamp(2rem, 5vw, 3.5rem); margin: 8px 0 0; }
        .eyebrow { color: #1456a0; font-size: .8rem; font-weight: bold; letter-spacing: .16em; text-transform: uppercase; }
        .announcement-grid { display: grid; gap: 22px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        .announcement-card { background: white; border: 1px solid #d8e0ee; border-top: 5px solid #1456a0; box-shadow: 0 5px 18px rgba(7,29,73,.08); display: flex; flex-direction: column; }
        .announcement-card img { aspect-ratio: 16 / 9; object-fit: cover; width: 100%; }
        .announcement-content { padding: 22px; }
        .announcement-content h2 { color: #071d49; margin: 0 0 8px; }
        .announcement-date { color: #5d6b7e; font-size: .85rem; }
        .announcement-body { line-height: 1.7; white-space: pre-line; }
        .empty { background: white; border: 1px solid #d8e0ee; padding: 36px; text-align: center; }
        @media (max-width: 760px) { .community-heading { align-items: flex-start; flex-direction: column; gap: 8px; } }
    </style>
    <link rel="stylesheet" href="member-panels.css?v=2">
</head>
<body>
<?php pascco_member_header('Community'); ?>
<main class="community member-content">
    <section class="community-heading"><div><div class="eyebrow">PASCCO community</div><h1>Announcements</h1></div><p>Stay informed about cooperative activities, services, and member updates.</p></section>
    <?php if ($announcements): ?>
        <section class="announcement-grid" aria-label="Community announcements">
            <?php foreach ($announcements as $announcement): ?>
                <article class="announcement-card">
                    <?php if ($announcement['image_path']): ?><img src="<?php echo htmlspecialchars($announcement['image_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
                    <div class="announcement-content"><h2><?php echo htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8'); ?></h2><div class="announcement-date"><?php echo date('F j, Y', strtotime($announcement['published_at'])); ?></div><p class="announcement-body"><?php echo htmlspecialchars($announcement['body'], ENT_QUOTES, 'UTF-8'); ?></p></div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <div class="empty"><h2>No announcements yet</h2><p>New PASCCO community updates will appear here.</p></div>
    <?php endif; ?>
</main>
<?php pascco_global_footer(); ?>
</body>
</html>
