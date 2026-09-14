$ErrorActionPreference = 'Stop'
$private = 'C:\xampp\pascco-private'
$apacheConfig = 'C:\xampp\apache\conf\httpd.conf'
$sslConfig = 'C:\xampp\apache\conf\extra\httpd-ssl.conf'
$mysqlConfig = 'C:\xampp\mysql\bin\my.ini'
$utf8 = New-Object Text.UTF8Encoding $false
$env:OPENSSL_CONF = 'C:\xampp\apache\conf\openssl.cnf'
if (!(Test-Path -LiteralPath "$private\localhost.crt")) {
    $ErrorActionPreference = 'Continue'
    & C:\xampp\apache\bin\openssl.exe req -x509 -newkey rsa:3072 -nodes -days 365 -subj '/CN=localhost' -addext 'subjectAltName=DNS:localhost,IP:127.0.0.1,IP:::1' -addext 'basicConstraints=critical,CA:FALSE' -keyout "$private\localhost.key" -out "$private\localhost.crt" 2> "$private\certificate-generation.log"
    $ErrorActionPreference = 'Stop'
    if ($LASTEXITCODE -ne 0) { throw 'Certificate generation failed' }
}
Import-Certificate -FilePath "$private\localhost.crt" -CertStoreLocation Cert:\CurrentUser\Root | Select-Object Subject,NotAfter
$source = [IO.File]::ReadAllText($apacheConfig)
$source = [regex]::Replace($source, '(?m)^Listen 80\s*$', "Listen 127.0.0.1:80`r`nListen [::1]:80")
[IO.File]::WriteAllText($apacheConfig, $source, $utf8)
$source = [IO.File]::ReadAllText($sslConfig)
$source = [regex]::Replace($source, '(?m)^Listen 443\s*$', "Listen 127.0.0.1:443`r`nListen [::1]:443")
$source = [regex]::Replace($source, '(?m)^SSLCertificateFile .+$', 'SSLCertificateFile "C:/xampp/pascco-private/localhost.crt"')
$source = [regex]::Replace($source, '(?m)^SSLCertificateKeyFile .+$', 'SSLCertificateKeyFile "C:/xampp/pascco-private/localhost.key"')
$source = [regex]::Replace($source, '(?m)^SSLProtocol .+$', 'SSLProtocol -all +TLSv1.2 +TLSv1.3')
$source = [regex]::Replace($source, '(?m)^SSLProxyProtocol .+$', 'SSLProxyProtocol -all +TLSv1.2 +TLSv1.3')
[IO.File]::WriteAllText($sslConfig, $source, $utf8)
$source = [IO.File]::ReadAllText($mysqlConfig)
if ($source -notmatch '(?m)^bind-address\s*=') { $source = $source.Replace('[mysqld]', "[mysqld]`r`nbind-address=127.0.0.1") }
[IO.File]::WriteAllText($mysqlConfig, $source, $utf8)
& C:\xampp\apache\bin\httpd.exe -t
if ($LASTEXITCODE -ne 0) { throw 'Apache configuration check failed; no services restarted.' }
& C:\xampp\mysql\bin\mysqladmin.exe -u root shutdown
if ($LASTEXITCODE -ne 0) { throw 'MySQL shutdown failed; refusing duplicate start.' }
Start-Sleep -Seconds 2
Start-Process -FilePath 'C:\xampp\mysql\bin\mysqld.exe' -ArgumentList '--defaults-file=C:\xampp\mysql\bin\my.ini','--standalone' -WindowStyle Hidden
$apache = Get-CimInstance Win32_Process -Filter "name='httpd.exe'" | Where-Object { $_.ExecutablePath -ieq 'C:\xampp\apache\bin\httpd.exe' }
foreach ($process in $apache) { Stop-Process -Id $process.ProcessId -Force -ErrorAction SilentlyContinue }
Start-Process -FilePath 'C:\xampp\apache\bin\httpd.exe' -WorkingDirectory 'C:\xampp\apache' -WindowStyle Hidden
Start-Sleep -Seconds 3
Write-Output 'Local TLS and loopback configuration applied. Apache and MySQL restarted.'
