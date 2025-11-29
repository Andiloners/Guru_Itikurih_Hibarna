# Script PowerShell untuk menjalankan PHP Server
Write-Host "===========================================" -ForegroundColor Cyan
Write-Host "  MENJALANKAN SERVER PHP" -ForegroundColor Cyan
Write-Host "===========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Server akan berjalan di: http://localhost:8000" -ForegroundColor Green
Write-Host ""
Write-Host "TEKAN CTRL+C untuk menghentikan server" -ForegroundColor Yellow
Write-Host ""
Write-Host "===========================================" -ForegroundColor Cyan
Write-Host ""

# Masuk ke folder script
Set-Location $PSScriptRoot

# Jalankan PHP server
php -S localhost:8000

