<?php
// Run locally from the command line once, with database-administrator access.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$private = 'C:/xampp/pascco-private';
if (!is_dir($private)) { throw new RuntimeException('Create the restricted private directory first.'); }
$configPath = $private . '/config.php';
$existing = is_file($configPath) ? require $configPath : null;
$pdo = new PDO('mysql:host=127.0.0.1;dbname=paco_cooperative;charset=utf8mb4', 'root', getenv('PASCCO_SETUP_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
$pdo->exec(file_get_contents(dirname(__DIR__) . '/database_migration.sql'));
$pdo->exec("CREATE TABLE IF NOT EXISTS auth_throttle (bucket CHAR(64) PRIMARY KEY, attempts INT UNSIGNED NOT NULL DEFAULT 0, window_started BIGINT NOT NULL) ENGINE=InnoDB");
$pdo->exec("CREATE TABLE IF NOT EXISTS user_mfa (user_id INT UNSIGNED PRIMARY KEY, secret VARCHAR(255) NOT NULL, last_step BIGINT NOT NULL DEFAULT -1, CONSTRAINT fk_mfa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB");
$password = $existing['db_password'] ?? bin2hex(random_bytes(32));
$pdo->exec("CREATE USER IF NOT EXISTS 'pascco_app'@'localhost' IDENTIFIED BY " . $pdo->quote($password));
$pdo->exec("ALTER USER 'pascco_app'@'localhost' IDENTIFIED BY " . $pdo->quote($password));
$pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON paco_cooperative.* TO 'pascco_app'@'localhost'");
$config = $existing ?: ['db_password' => $password, 'encryption_key' => base64_encode(random_bytes(32))];
file_put_contents($configPath, "<?php\nreturn " . var_export($config, true) . ";\n", LOCK_EX);
echo "Schema installed; dedicated application account configured. Secrets saved outside the document root.\n";
