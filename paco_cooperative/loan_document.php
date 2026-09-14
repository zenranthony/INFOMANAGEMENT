<?php
require_once 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

$loan_id = (int) ($_GET['loan_id'] ?? 0);
$type = $_GET['type'] ?? '';
$column = $type === 'id' ? 'id_document_path' : ($type === 'income' ? 'proof_income_path' : '');
if ($loan_id <= 0 || $column === '') {
    http_response_code(400);
    exit('Invalid document request.');
}

$stmt = $pdo->prepare("SELECT {$column} AS document_path FROM loans WHERE id = ? LIMIT 1");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();
$relative_path = $loan['document_path'] ?? '';
$root = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'loan_documents');
$file_path = $relative_path !== '' ? realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path)) : false;

if (!$root || !$file_path || strpos($file_path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($file_path)) {
    http_response_code(404);
    exit('Document not found.');
}

$mime_type = (new finfo(FILEINFO_MIME_TYPE))->file($file_path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime_type);
header('Content-Disposition: inline; filename="loan-document-' . $loan_id . '"');
header('X-Content-Type-Options: nosniff');
readfile($file_path);
