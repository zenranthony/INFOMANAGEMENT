<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/auth.php';
$config = require 'C:/xampp/pascco-private/config.php';
$pdo = new PDO('mysql:host=127.0.0.1;dbname=paco_cooperative;charset=utf8mb4', 'pascco_app', $config['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
function check(bool $ok, string $label): void { if (!$ok) { throw new RuntimeException($label); } echo "PASS: $label\n"; }
$curl = curl_init();
curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => '', CURLOPT_CAINFO => 'C:/xampp/pascco-private/localhost.crt', CURLOPT_TIMEOUT => 15, CURLOPT_FOLLOWLOCATION => false]);
function request(string $page, ?array $data = null): array {
    global $curl;
    curl_setopt($curl, CURLOPT_URL, 'https://localhost/paco_cooperative/' . $page);
    curl_setopt($curl, CURLOPT_POST, $data !== null);
    if ($data !== null) { curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data)); }
    $body = curl_exec($curl);
    if ($body === false) { throw new RuntimeException(curl_error($curl)); }
    return [(int) curl_getinfo($curl, CURLINFO_HTTP_CODE), $body];
}
function csrf(string $body): string {
    if (!preg_match('/name="csrf_token"\s+value="([a-f0-9]+)"/', $body, $m)) { throw new RuntimeException('CSRF field missing'); }
    return $m[1];
}
$username = 'security_test_' . bin2hex(random_bytes(5)); $password = bin2hex(random_bytes(20)); $userId = null;
try {
    $insert = $pdo->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, 'member', 'active')");
    $insert->execute([$username, password_hash($password, PASSWORD_DEFAULT)]); $userId = (int) $pdo->lastInsertId();
    [$status, $body] = request('login.php'); check($status === 200, 'HTTPS certificate verified and login available');
    [$status] = request('login.php', ['username' => $username, 'password' => $password, 'csrf_token' => csrf($body)]);
    check($status === 302, 'Password leads to second factor');
    [$status] = request('dashboard.php'); check($status === 302, 'Password alone cannot open member dashboard');
    [$status, $body] = request('otp_verify.php');
    check($status === 200 && preg_match('/<code class="setup-key">([A-Z2-7]+)<\/code>/', $body, $match) === 1, 'First login requires authenticator enrollment');
    $secret = $match[1]; $step = intdiv(time(), 30);
    [$status] = request('otp_verify.php', ['otp' => pascco_totp($secret, $step), 'csrf_token' => csrf($body)]);
    check($status === 302, 'Authenticator enrollment completes');
    [$status] = request('dashboard.php'); check($status === 200, 'Verified member opens dashboard');
    [$status, $body] = request('admin_dashboard.php'); check($status !== 200 || str_contains($body, 'Access Denied'), 'Member cannot open admin dashboard');
    request('logout.php');
    [$status, $body] = request('login.php');
    request('login.php', ['username' => $username, 'password' => $password, 'csrf_token' => csrf($body)]);
    [$status, $body] = request('otp_verify.php'); check(!str_contains($body, 'setup-key">') && !str_contains($body, $secret) && !str_contains($body, 'Demo OTP'), 'Enrolled secret and demo codes are not displayed');
    [$status, $body] = request('otp_verify.php', ['otp' => pascco_totp($secret, $step), 'csrf_token' => csrf($body)]);
    check($status === 200 && str_contains($body, 'already used'), 'Used authenticator code is rejected over HTTP');
    [$status] = request('otp_verify.php', ['otp' => pascco_totp($secret, $step + 1), 'csrf_token' => csrf($body)]);
    check($status === 302, 'New authenticator code completes subsequent login');
    request('logout.php'); [$status] = request('dashboard.php'); check($status === 302, 'Logout invalidates authenticated access');
    [$status, $body] = request('login.php');
    request('login.php', ['username' => $username, 'password' => $password, 'csrf_token' => csrf($body)]);
    [$status, $body] = request('otp_verify.php');
    for ($i = 0; $i < 6; $i++) { [$status, $body] = request('otp_verify.php', ['otp' => 'invalid', 'csrf_token' => csrf($body)]); }
    check($status === 429, 'Authenticator throttling persists across logins');
    request('logout.php');
    $fake = $username . '_wrong';
    [$status, $body] = request('login.php');
    for ($i = 0; $i < 11; $i++) { [$status, $body] = request('login.php', ['username' => $fake, 'password' => 'incorrect', 'csrf_token' => csrf($body)]); }
    check($status === 429, 'Password throttling rejects repeated attempts');
    [$status, $body] = request('login.php', ['username' => $fake, 'password' => 'incorrect', 'csrf_token' => 'invalid']);
    check(!str_contains($body, 'Set up your authenticator') && str_contains($body, 'Security Error'), 'Invalid CSRF rejected');
    try { $pdo->exec('CREATE TABLE security_test_denied (id INT)'); throw new LogicException('CREATE unexpectedly allowed'); }
    catch (PDOException $e) { check((int) ($e->errorInfo[1] ?? 0) === 1142, 'Application account cannot create tables'); }
    [$status] = request('security/schema.sql'); check($status === 403, 'Setup files blocked over HTTP');
    [$status] = request('forgot_password.php'); check($status === 200, 'Password recovery works without schema permissions');
    [$status] = request('membership_documents.php'); check($status === 200, 'Membership page remains available');
} finally {
    if ($userId) {
        $pdo->prepare('DELETE FROM audit_logs WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
        foreach ([['password-account', 'id:' . $userId], ['password-account', $username . '_wrong'], ['authenticator-user', (string) $userId]] as [$scope, $identity]) {
            $pdo->prepare('DELETE FROM auth_throttle WHERE bucket = ?')->execute([hash('sha256', $scope . ':' . $identity)]);
        }
    }
    curl_close($curl);
}
