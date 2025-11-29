@echo off
echo ===========================================
echo   MENJALANKAN SERVER PHP
echo ===========================================
echo.
echo Server akan berjalan di: http://localhost:8000
echo.
echo TEKAN CTRL+C untuk menghentikan server
echo.
echo ===========================================
echo.

cd /d "%~dp0"
php -S localhost:8000

pause

