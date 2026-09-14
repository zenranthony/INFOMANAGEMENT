<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/backup-crypto.php';
if ($argc !== 2 || !is_dir($argv[1])) { exit("Usage: backup.php EXISTING_BACKUP_DIRECTORY\n"); }
$password = getenv('PASCCO_BACKUP_PASSWORD') ?: '';
if (strlen($password) < 16) { throw new RuntimeException('Use backup.ps1 to enter a passphrase of at least 16 characters.'); }
$config = require 'C:/xampp/pascco-private/config.php';
$id = date('Ymd-His') . '-' . bin2hex(random_bytes(4));
$temp = 'C:/xampp/pascco-private/backup-' . $id;
mkdir($temp, 0700);
$destination = rtrim($argv[1], '/\\') . '/pascco-' . $id . '.pascco';
try {
    file_put_contents($temp . '/mysql.cnf', "[client]\nhost=127.0.0.1\nuser=pascco_app\npassword=" . $config['db_password'] . "\n");
    $process = proc_open(['C:/xampp/mysql/bin/mysqldump.exe', '--defaults-extra-file=' . $temp . '/mysql.cnf', '--single-transaction', '--skip-lock-tables', '--no-tablespaces', '--skip-triggers', '--result-file=' . $temp . '/database.sql', 'paco_cooperative'], [0 => ['pipe', 'r'], 1 => ['file', $temp . '/dump.log', 'w'], 2 => ['file', $temp . '/dump.log', 'a']], $pipes);
    if (!is_resource($process)) { throw new RuntimeException('Cannot start database backup.'); }
    fclose($pipes[0]);
    if (proc_close($process) !== 0) { throw new RuntimeException('Database dump failed.'); }
    $archive = new PharData($temp . '/restore.tar');
    $archive->addFile($temp . '/database.sql', 'database.sql');
    $archive->addFile('C:/xampp/pascco-private/config.php', 'private/config.php');
    $root = dirname(__DIR__);
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isLink()) { continue; }
        if ($file->isFile()) { $archive->addFile($file->getPathname(), 'application/' . str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1))); }
    }
    $archive->addFromString('README.txt', "PASCCO backup: database.sql, application (including uploaded documents), and private/config.php (database password and MFA encryption key).\nRestore into a separate location first. Recreate the application DB user using security/install.php. Configure new-host TLS separately.\n");
    unset($archive);
    $sealed = pascco_seal_backup(file_get_contents($temp . '/restore.tar'), $password);
    $output = fopen($destination, 'xb');
    if (!$output) { throw new RuntimeException('Cannot create backup destination.'); }
    try { if (fwrite($output, $sealed) !== strlen($sealed)) { throw new RuntimeException('Incomplete backup write.'); } } finally { fclose($output); }
    if (!hash_equals(hash_file('sha256', $temp . '/restore.tar'), hash('sha256', pascco_open_backup(file_get_contents($destination), $password)))) { throw new RuntimeException('Backup verification failed.'); }
    echo "Encrypted backup written and verified: $destination\n";
} finally {
    foreach (glob($temp . '/*') as $file) { unlink($file); }
    rmdir($temp);
}
