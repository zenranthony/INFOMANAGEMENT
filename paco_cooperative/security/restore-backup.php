<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/backup-crypto.php';
if ($argc !== 3 || !is_file($argv[1]) || file_exists($argv[2])) { exit("Supply an encrypted backup and a NEW destination directory. Existing directories are never overwritten.\n"); }
$plain = pascco_open_backup(file_get_contents($argv[1]), getenv('PASCCO_BACKUP_PASSWORD') ?: '');
$temp = 'C:/xampp/pascco-private/restore-' . bin2hex(random_bytes(8)) . '.tar';
try {
    file_put_contents($temp, $plain); unset($plain);
    $archive = new PharData($temp);
    mkdir($argv[2], 0700, true);
    $archive->extractTo($argv[2], null, false);
    unset($archive);
    echo "Backup authenticated and extracted. No live database or application files were modified.\n";
} finally { if (is_file($temp)) { unlink($temp); } }
