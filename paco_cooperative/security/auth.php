<?php
function pascco_base32_encode(string $bytes): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $buffer = 0; $bits = 0; $out = '';
    foreach (unpack('C*', $bytes) as $byte) {
        $buffer = ($buffer << 8) | $byte; $bits += 8;
        while ($bits >= 5) { $bits -= 5; $out .= $alphabet[($buffer >> $bits) & 31]; }
        $buffer &= (1 << $bits) - 1;
    }
    if ($bits) { $out .= $alphabet[($buffer << (5 - $bits)) & 31]; }
    return $out;
}
function pascco_base32_decode(string $text): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $buffer = 0; $bits = 0; $out = '';
    foreach (str_split(strtoupper($text)) as $char) {
        $value = strpos($alphabet, $char);
        if ($value === false) { throw new InvalidArgumentException('Invalid authenticator key.'); }
        $buffer = ($buffer << 5) | $value; $bits += 5;
        if ($bits >= 8) { $bits -= 8; $out .= chr(($buffer >> $bits) & 255); }
        $buffer &= (1 << $bits) - 1;
    }
    return $out;
}
function pascco_totp(string $secret, int $step, int $digits = 6): string {
    $hash = hash_hmac('sha1', pack('N2', intdiv($step, 4294967296), $step & 0xffffffff), pascco_base32_decode($secret), true);
    $offset = ord($hash[19]) & 15;
    $number = unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff;
    return str_pad((string) ($number % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
}
function pascco_totp_step(string $secret, string $code, int $lastStep, ?int $now = null): ?int {
    if (!preg_match('/^[0-9]{6}$/D', $code)) { return null; }
    $current = intdiv($now ?? time(), 30);
    foreach ([$current, $current - 1, $current + 1] as $step) {
        if ($step > $lastStep && hash_equals(pascco_totp($secret, $step), $code)) { return $step; }
    }
    return null;
}
function pascco_encrypt_secret(string $secret, string $key): string {
    $iv = random_bytes(12); $tag = '';
    $cipher = openssl_encrypt($secret, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) { throw new RuntimeException('Unable to protect authenticator key.'); }
    return base64_encode($iv . $tag . $cipher);
}
function pascco_decrypt_secret(string $encrypted, string $key): string {
    $raw = base64_decode($encrypted, true);
    if ($raw === false || strlen($raw) < 29) { throw new RuntimeException('Invalid authenticator record.'); }
    $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    if ($plain === false) { throw new RuntimeException('Unable to read authenticator key.'); }
    return $plain;
}
// Count every attempt before checking credentials, atomically across sessions.
function pascco_take_attempt(PDO $pdo, string $scope, string $identity, int $limit, int $window = 900): bool {
    $bucket = hash('sha256', $scope . ':' . $identity); $now = time();
    $stmt = $pdo->prepare('INSERT INTO auth_throttle (bucket, attempts, window_started) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE attempts = IF(window_started <= ?, 1, attempts + 1), window_started = IF(window_started <= ?, VALUES(window_started), window_started)');
    $stmt->execute([$bucket, $now, $now - $window, $now - $window]);
    $read = $pdo->prepare('SELECT attempts FROM auth_throttle WHERE bucket = ?'); $read->execute([$bucket]);
    return (int) $read->fetchColumn() <= $limit;
}
