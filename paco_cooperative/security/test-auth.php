<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/auth.php';
function check(bool $value, string $label): void { if (!$value) { throw new RuntimeException($label); } echo "PASS: $label\n"; }
// RFC 6238 Appendix B, SHA-1 / 8 digits.
$secret = pascco_base32_encode('12345678901234567890');
foreach ([59 => '94287082', 1111111109 => '07081804', 1111111111 => '14050471', 1234567890 => '89005924', 2000000000 => '69279037', 20000000000 => '65353130'] as $time => $expected) {
    check(pascco_totp($secret, intdiv($time, 30), 8) === $expected, "RFC vector $time");
}
$now = 1234567890; $step = intdiv($now, 30); $code = pascco_totp($secret, $step);
check(pascco_totp_step($secret, $code, -1, $now) === $step, 'Valid six-digit code');
check(pascco_totp_step($secret, $code, $step, $now) === null, 'Replay rejected');
check(pascco_totp_step($secret, $code, -1, $now + 120) === null, 'Expired code rejected');
check(pascco_totp_step($secret, '12345x', -1, $now) === null, 'Malformed code rejected');
$key = random_bytes(32); $encrypted = pascco_encrypt_secret($secret, $key);
check(pascco_decrypt_secret($encrypted, $key) === $secret, 'Encrypted secret roundtrip');
$raw = base64_decode($encrypted); $raw[30] = chr(ord($raw[30]) ^ 1);
try { pascco_decrypt_secret(base64_encode($raw), $key); throw new LogicException('Tampered ciphertext accepted'); }
catch (RuntimeException $e) { echo "PASS: Tampered ciphertext rejected\n"; }
