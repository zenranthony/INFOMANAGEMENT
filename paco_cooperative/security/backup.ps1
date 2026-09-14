param([Parameter(Mandatory=$true)][string]$Destination)
$ErrorActionPreference = 'Stop'
if (!(Test-Path -LiteralPath $Destination -PathType Container)) { throw 'Connect your external drive and provide an existing backup folder.' }
$secret = Read-Host 'Backup passphrase (at least 16 characters; keep it separately)' -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secret)
try {
    $env:PASCCO_BACKUP_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
    & C:\xampp\php\php.exe "$PSScriptRoot\backup.php" $Destination
    if ($LASTEXITCODE -ne 0) { throw 'Backup failed.' }
} finally {
    Remove-Item Env:\PASCCO_BACKUP_PASSWORD -ErrorAction SilentlyContinue
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
}
