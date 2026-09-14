<?php
function pascco_seal_backup(string $data, string $password): string {
    if (strlen($password) < 16) { throw new RuntimeException('Use a backup passphrase of at least 16 characters.'); }
    $salt = random_bytes(16); $iv = random_bytes(12); $tag = '';
    $key = hash_pbkdf2('sha256', $password, $salt, 600000, 32, true);
    $cipher = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, 'PASCCO1');
    if ($cipher === false) { throw new RuntimeException('Backup encryption failed.'); }
    return "PASCCO1\n" . $salt . $iv . $tag . $cipher;
}
function pascco_open_backup(string $data, string $password): string {
    if (strlen($data) < 53 || substr($data, 0, 8) !== "PASCCO1\n") { throw new RuntimeException('Invalid backup file.'); }
    $key = hash_pbkdf2('sha256', $password, substr($data, 8, 16), 600000, 32, true);
    $plain = openssl_decrypt(substr($data, 52), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($data, 24, 12), substr($data, 36, 16), 'PASCCO1');
    if ($plain === false) { throw new RuntimeException('Wrong passphrase or damaged backup.'); }
    return $plain;
}
