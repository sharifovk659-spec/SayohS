# Creates deploy.local.json securely (gitignored). Do not commit.
# Usage: powershell -ExecutionPolicy Bypass -File scripts/write-deploy-secrets.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$target = Join-Path $root 'deploy.local.json'

Write-Host 'Паролҳоро инҷо ворид кунед (дар экран наменамояд).' -ForegroundColor Cyan
$sshSecure = Read-Host -AsSecureString 'SSH password'
$mysqlSecure = Read-Host -AsSecureString 'MySQL password'

$sshBstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($sshSecure)
$mysqlBstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($mysqlSecure)
try {
  $sshPass = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($sshBstr)
  $mysqlPass = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($mysqlBstr)
} finally {
  [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($sshBstr)
  [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($mysqlBstr)
}

$obj = [ordered]@{
  ssh = [ordered]@{
    host = '45.84.204.68'
    port = 65002
    username = 'u417315406'
    password = $sshPass
    likely_docroot = '/home/u417315406/domains/inovaauto.com/public_html/aroma'
  }
  mysql = [ordered]@{
    host = 'localhost'
    port = 3306
    database = 'u417315406_aroma'
    username = 'u417315406_aroma'
    password = $mysqlPass
  }
  site_url = 'https://aroma.inovaauto.com'
  admin = [ordered]@{
    email = 'sharifovk659@gmail.com'
    name = 'Komron Sharifov'
  }
}

$obj | ConvertTo-Json -Depth 5 | Set-Content -Path $target -Encoding UTF8
Write-Host "Сабт шуд: $target (ба Git намеравад)" -ForegroundColor Green
