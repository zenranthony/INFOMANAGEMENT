<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/auth.php';
$config = require 'C:/xampp/pascco-private/config.php';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=paco_cooperative;charset=utf8mb4', 'pascco_app', $config['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$username = 'security_admin_' . bin2hex(random_bytes(4)); $password = bin2hex(random_bytes(20));
$secret = pascco_base32_encode(random_bytes(20)); $id = null;
$curl = curl_init();
curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => '', CURLOPT_CAINFO => 'C:/xampp/pascco-private/localhost.crt', CURLOPT_TIMEOUT => 15]);
function page(string $path, ?array $post = null): string {
    global $curl;
    curl_setopt($curl, CURLOPT_URL, 'https://localhost/paco_cooperative/' . $path);
    curl_setopt($curl, CURLOPT_POST, $post !== null);
    if ($post !== null) { curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $body = curl_exec($curl);
    if ($body === false) { throw new RuntimeException(curl_error($curl)); }
    return $body;
}
function token(string $body): string { preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/', $body, $m); return $m[1] ?? ''; }
try {
    $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, 'admin', 'active')")->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    $id = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO user_mfa (user_id, secret, last_step) VALUES (?, ?, -1)')->execute([$id, pascco_encrypt_secret($secret, base64_decode($config['encryption_key']))]);
    $body = page('login.php'); page('login.php', ['username' => $username, 'password' => $password, 'csrf_token' => token($body)]);
    $body = page('otp_verify.php'); page('otp_verify.php', ['otp' => pascco_totp($secret, intdiv(time(), 30)), 'csrf_token' => token($body)]);
    if (curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 302) { throw new RuntimeException('Admin MFA login failed'); }
    foreach (['admin_dashboard.php', 'admin_members.php', 'admin_membership.php', 'admin_account_requests.php', 'admin_loans.php', 'admin_deposits.php', 'admin_announcements.php'] as $path) {
        $body = page($path);
        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200 || str_contains($body, 'Fatal error') || str_contains($body, 'Access Denied')) { throw new RuntimeException('Admin page failed: ' . $path); }
        echo "PASS: $path with limited database account\n";
    }
} finally {
    if ($id) {
        $pdo->prepare('DELETE FROM audit_logs WHERE user_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        foreach ([['password-account', 'id:' . $id], ['authenticator-user', (string) $id]] as [$scope, $identity]) { $pdo->prepare('DELETE FROM auth_throttle WHERE bucket = ?')->execute([hash('sha256', $scope . ':' . $identity)]); }
    }
    curl_close($curl);
}
