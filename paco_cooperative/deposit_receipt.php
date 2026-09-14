<?php
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

$request_id = (int) ($_GET['request_id'] ?? 0);
$stmt = $pdo->prepare('SELECT receipt_path FROM deposit_requests WHERE id = ? LIMIT 1');
$stmt->execute([$request_id]);
$request = $stmt->fetch();
$root = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'deposit_receipts');
$relative_path = $request['receipt_path'] ?? '';
$file_path = $relative_path !== '' ? realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path)) : false;

if (!$root || !$file_path || strpos($file_path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($file_path)) {
    http_response_code(404);
    exit('Receipt not found.');
}

$mime_type = (new finfo(FILEINFO_MIME_TYPE))->file($file_path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime_type);
header('Content-Disposition: inline; filename="deposit-receipt-' . $request_id . '"');
header('X-Content-Type-Options: nosniff');
readfile($file_path);
