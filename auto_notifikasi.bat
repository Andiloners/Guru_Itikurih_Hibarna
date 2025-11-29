@echo off
REM File batch untuk menjalankan auto notifikasi di Windows
REM File ini bisa dijadwalkan via Task Scheduler

cd /d "%~dp0"
C:\xampp\php\php.exe cron_notifikasi.php

REM Jika PHP tidak di C:\xampp, sesuaikan path di atas
REM Contoh: D:\xampp\php\php.exe atau C:\php\php.exe

pause

