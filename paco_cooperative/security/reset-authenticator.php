<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if ($argc !== 3 || $argv[2] !== '--identity-verified') {
    exit("After verifying the account owner's identity in person, run:\nphp security/reset-authenticator.php USERNAME --identity-verified\n");
}
$config = require 'C:/xampp/pascco-private/config.php';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=paco_cooperative;charset=utf8mb4', 'pascco_app', $config['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->beginTransaction();
$find = $pdo->prepare('SELECT id FROM users WHERE username = ? FOR UPDATE'); $find->execute([$argv[1]]); $id = $find->fetchColumn();
if (!$id) { $pdo->rollBack(); exit("Account not found.\n"); }
$pdo->prepare('DELETE FROM user_mfa WHERE user_id = ?')->execute([$id]);
$pdo->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address, status) VALUES (?, 'mfa_recovery', 'Local operator confirmed identity and reset authenticator enrollment.', '127.0.0.1', 'success')")->execute([$id]);
$pdo->commit();
echo "Enrollment reset. Existing authenticated sessions will be rejected; the owner must sign in and enroll again.\n";
