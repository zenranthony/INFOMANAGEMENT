# PASCCO local security setup

Open **https://localhost/paco_cooperative/**. HTTP redirects to HTTPS. This installation is intentionally limited to this computer; phones and other LAN devices cannot browse it.

## Login and authenticator enrollment

After entering the correct username/password, existing accounts enroll an authenticator on their first login. In your phone's authenticator app, choose manual setup, enter the displayed key, and select time-based codes. Enter a code from the app to activate it. Later logins display no setup key or demo OTP. Complete initial enrollment yourself before letting anyone else use your account.

Codes use RFC 6238 (SHA-1, 6 digits, 30 seconds, a one-step clock allowance). Used codes cannot be reused. Keep your phone clock automatic. MFA secrets are encrypted with AES-256-GCM; the encryption key is in `C:\xampp\pascco-private\config.php`, outside Apache's document root with restricted Windows permissions.

Limits: 10 password attempts per account and 60 per IP per 15 minutes; 5 authenticator attempts per account and 30 per IP per 15 minutes. Attempts are stored in MySQL and do not reset when the browser session changes. These fixed limits can temporarily block legitimate access too. Wait 15 minutes instead of repeatedly retrying.

Old pre-MFA sessions are invalidated. Authenticated sessions expire after 30 minutes idle or 8 hours overall. Password changes, account deactivation, role changes and authenticator resets invalidate protected-page access. Cookies are Secure, HttpOnly, and SameSite=Lax.

## Lost phone

The local operator must verify the account owner's identity in person before running:

```powershell
C:\xampp\php\php.exe security\reset-authenticator.php USERNAME --identity-verified
```

This is a command-line recovery operation, not a public web endpoint. It clears enrollment and records an audit event. The owner then signs in with the password and enrolls again. Possession of Windows access to the private configuration is privileged; do not share this Windows account.

## Database

The app connects as `pascco_app@localhost` using a generated password. Its privileges are SELECT, INSERT, UPDATE and DELETE on `paco_cooperative` only. It cannot create/drop tables or manage database accounts. Schema creation is in `security/schema.sql` and the CLI-only `security/install.php`, rather than normal page requests. The shared XAMPP root administrator account was not changed, to avoid breaking the other local project; it must never be used by this app or exposed remotely.

MySQL is bound to 127.0.0.1. Apache is configured for loopback. The application's Apache access policy also requires local access. No router forwarding is needed.

## HTTPS

A localhost-only certificate is installed in the current Windows user's trust store. Certificate and private key are in `C:\xampp\pascco-private`. The certificate expires September 14, 2027. Renew before expiry; do not bypass certificate warnings. Other Windows accounts require the certificate to be trusted separately. This is local development TLS, not a publicly trusted deployment certificate. Apache permits TLS 1.2 and 1.3.

## Encrypted backups

Connect an external drive, create a backup folder, then run from the project directory:

```powershell
powershell -NoProfile -File security\backup.ps1 -Destination "E:\PASCCO-backups"
```

Use your actual external drive, not the example drive letter. Enter a strong passphrase of at least 16 characters when prompted. Keep it separately from the backup. The passphrase is not placed in command arguments or written into a configuration file. It is passed to the child process in its environment only for that run; a privileged local process can still inspect process memory/environment.

The authenticated encrypted archive contains the database tables/data, application files, uploaded documents, and the private database/MFA configuration needed for recovery. TLS keys are excluded; issue a new certificate on a replacement machine. Current backups omit stored routines, events and triggers; none are required by this app. Review the backup implementation if those are introduced. Backups read the app files while the database dump uses a transaction snapshot: take them while the app is idle, with no uploads or code changes in progress.

To decrypt and extract into a NEW directory without overwriting the live app:

```powershell
powershell -NoProfile -File security\restore-backup.ps1 -BackupFile "E:\PASCCO-backups\YOUR-BACKUP.pascco" -NewDestination "C:\xampp\pascco-private\recovery"
```

Then restore `database.sql` into a separate database for verification. For a replacement installation, restore the app and private config, import the SQL, run the setup tool with database-administrator credentials to recreate the application account, and configure localhost TLS. Do not copy private config into the web root. An encrypted file on C: is not an off-computer backup. Keep a disconnected external copy and periodically test restoration.

## Validation

`test-auth.php` checks all six SHA-1 RFC 6238 test vectors, expired/malformed/reused codes, and encrypted-secret tamper detection. `test-integration.php` uses a disposable account to exercise HTTPS, enrollment, second-factor requirements, role boundaries, replay rejection, logout, rate limits, CSRF rejection, setup-file blocking and denied schema permissions. It counts toward the real localhost IP limits, so do not run it repeatedly during normal login use.

`test-backup.php` requires local database-administrator access, creates an encrypted backup, rejects incorrect passphrases and tampering, restores into an isolated temporary database, compares all table row counts, and cleans up only its own test artifacts. It does not overwrite the live database.

References: [RFC 6238](https://www.rfc-editor.org/rfc/rfc6238), [OWASP MFA](https://cheatsheetseries.owasp.org/cheatsheets/Multifactor_Authentication_Cheat_Sheet.html), [Apache access control](https://httpd.apache.org/docs/2.4/howto/access.html).
