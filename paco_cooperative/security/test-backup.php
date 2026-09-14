<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/backup-crypto.php';
$root = 'C:/xampp/pascco-private/backup-test-' . bin2hex(random_bytes(6));
mkdir($root, 0700);
$password = bin2hex(random_bytes(24)); putenv('PASCCO_BACKUP_PASSWORD=' . $password);
$scratch = 'pascco_restore_test_' . bin2hex(random_bytes(6));
$admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
try {
    $proc = proc_open([PHP_BINARY, __DIR__ . '/backup.php', $root], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]); $out = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($proc) !== 0) { throw new RuntimeException('Backup failed: ' . $error); }
    echo "PASS: Live database and app encrypted and verified\n";
    $backup = glob($root . '/*.pascco')[0]; $data = file_get_contents($backup);
    try { pascco_open_backup($data, 'incorrect-passphrase'); throw new LogicException('Wrong password accepted'); }
    catch (RuntimeException $e) { echo "PASS: Incorrect backup passphrase rejected\n"; }
    $tampered = $data; $tampered[60] = chr(ord($tampered[60]) ^ 1);
    try { pascco_open_backup($tampered, $password); throw new LogicException('Tampering accepted'); }
    catch (RuntimeException $e) { echo "PASS: Backup tampering rejected\n"; }
    $proc = proc_open([PHP_BINARY, __DIR__ . '/restore-backup.php', $backup, $root . '/restored'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]); $out = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($proc) !== 0) { throw new RuntimeException('Restore failed: ' . $error); }
    foreach (['database.sql', 'private/config.php', 'application/login.php'] as $file) { if (!is_file($root . '/restored/' . $file)) { throw new RuntimeException('Incomplete restored archive'); } }
    $admin->exec("CREATE DATABASE `$scratch` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $proc = proc_open(['C:/xampp/mysql/bin/mysql.exe', '-u', 'root', $scratch], [0 => ['file', $root . '/restored/database.sql', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $out = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($proc) !== 0) { throw new RuntimeException('SQL restore failed: ' . $error); }
    foreach ($admin->query('SHOW TABLES FROM paco_cooperative')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        if (!preg_match('/^[a-z_]+$/D', $table)) { throw new RuntimeException('Unexpected table name'); }
        $before = $admin->query("SELECT COUNT(*) FROM paco_cooperative.`$table`")->fetchColumn();
        $after = $admin->query("SELECT COUNT(*) FROM `$scratch`.`$table`")->fetchColumn();
        if ($before != $after) { throw new RuntimeException('Restored table count mismatch: ' . $table); }
    }
    echo "PASS: Backup restored into isolated database; all table row counts match\n";
} finally {
    if (preg_match('/^pascco_restore_test_[a-f0-9]{12}$/D', $scratch)) { $admin->exec("DROP DATABASE IF EXISTS `$scratch`"); }
    putenv('PASCCO_BACKUP_PASSWORD');
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($root);
}
