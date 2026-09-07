<?php
require_once 'db.php';

// Generate a fresh, valid bcrypt hash for 'password123'
$new_password = password_hash('password123', PASSWORD_BCRYPT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username IN ('admin_juan', 'maria_santos')");
$stmt->execute([$new_password]);

echo "<h2 style='color:green;'>Success! Passwords for admin_juan and maria_santos have been reset to: password123</h2>";
echo "<p><a href='login.php'>Click here to Go to Login Page</a></p>";
?>