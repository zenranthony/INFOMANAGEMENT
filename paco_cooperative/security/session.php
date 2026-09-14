<?php
if (PHP_SAPI !== 'cli') {
    if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
        http_response_code(403); exit('PASCCO is available only on this computer.');
    }
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
}
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('PASCCOSESSID');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/paco_cooperative/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
if (isset($_SESSION['user_id']) && (empty($_SESSION['mfa_verified']) || time() - ($_SESSION['last_activity'] ?? 0) > 1800 || time() - ($_SESSION['authenticated_at'] ?? 0) > 28800)) {
    $_SESSION = []; session_regenerate_id(true);
}
if (isset($_SESSION['user_id'])) { $_SESSION['last_activity'] = time(); }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
