@echo off
REM ============================================
REM  E-Learning Platform - local dev launcher
REM  Starts MariaDB (if not running) + PHP server
REM ============================================

tasklist /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul
if errorlevel 1 (
    echo Starting MariaDB...
    start "MariaDB" /MIN "C:\Program Files\MariaDB 12.3\bin\mysqld.exe" --console
    timeout /t 3 /nobreak >nul
) else (
    echo MariaDB is already running.
)

cd /d "%~dp0"
echo Starting the app at http://localhost:8000 ...
start "" http://localhost:8000
php -S localhost:8000
