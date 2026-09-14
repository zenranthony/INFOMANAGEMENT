param([Parameter(Mandatory=$true)][string]$BackupFile,[Parameter(Mandatory=$true)][string]$NewDestination)
$ErrorActionPreference = 'Stop'
$secret = Read-Host 'Backup passphrase' -AsSecureString
$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secret)
try {
    $env:PASCCO_BACKUP_PASSWORD = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
    & C:\xampp\php\php.exe "$PSScriptRoot\restore-backup.php" $BackupFile $NewDestination
    if ($LASTEXITCODE -ne 0) { throw 'Restore failed.' }
} finally {
    Remove-Item Env:\PASCCO_BACKUP_PASSWORD -ErrorAction SilentlyContinue
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
}
