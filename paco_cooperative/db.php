<?php
require_once __DIR__ . '/security/session.php';
require_once __DIR__ . '/security/auth.php';
$configPath = 'C:/xampp/pascco-private/config.php';
if (!is_file($configPath)) { http_response_code(503); exit('PASCCO security setup is required.'); }
$pascco_config = require $configPath;
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=paco_cooperative;charset=utf8mb4', 'pascco_app', $pascco_config['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
} catch (PDOException $exception) {
    error_log('PASCCO database connection failed.');
    http_response_code(503); exit('Database connection failed. Please contact the administrator.');
}
if (isset($_SESSION['user_id'])) {
    $check = $pdo->prepare('SELECT u.password, u.status, u.role, m.secret FROM users u LEFT JOIN user_mfa m ON m.user_id = u.id WHERE u.id = ?');
    $check->execute([(int) $_SESSION['user_id']]);
    $currentUser = $check->fetch();
    if (!$currentUser || !$currentUser['secret'] || !hash_equals($_SESSION['mfa_version'] ?? '', hash('sha256', $currentUser['secret'])) || $currentUser['status'] !== 'active' || !hash_equals($_SESSION['credential_version'] ?? '', hash('sha256', $currentUser['password'])) || $currentUser['role'] !== ($_SESSION['role'] ?? '')) {
        $_SESSION = []; session_regenerate_id(true);
        header('Location: login.php'); exit;
    }
}

// Generate CSRF token if one does not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to verify CSRF token on POST requests
function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submitted_token = (string) ($_POST['csrf_token'] ?? '');
        $session_token = (string) ($_SESSION['csrf_token'] ?? '');
        if ($session_token === '' || $submitted_token === '' || !hash_equals($session_token, $submitted_token)) {
            http_response_code(403);
            die("Security Error: Invalid or missing CSRF token. Request blocked.");
        }
    }
}



function record_audit(PDO $pdo, ?int $user_id, string $action, string $description, string $status = 'success'): void
{
    try {
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, description, ip_address, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $action, substr($description, 0, 255), $_SERVER['REMOTE_ADDR'] ?? 'unknown', $status]);
    } catch (Throwable $exception) {
        // Audit failure must not turn a completed member transaction into a failed request.
    }
}
?>
