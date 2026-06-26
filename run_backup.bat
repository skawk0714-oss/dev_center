@echo off
chcp 65001 >nul
cd /d "%~dp0"

set "PHP=C:\xampp\php\php.exe"
if not exist "%PHP%" (
    echo [ERROR] PHP not found: %PHP%
    pause
    exit /b 1
)

"%PHP%" -d memory_limit=512M backup_dev_pc.php
set "RC=%ERRORLEVEL%"

echo.
if "%RC%"=="0" (
    echo [OK] Backup finished.
) else (
    echo [FAIL] Backup failed. code=%RC%
)

if not "%1"=="--silent" pause
exit /b %RC%
