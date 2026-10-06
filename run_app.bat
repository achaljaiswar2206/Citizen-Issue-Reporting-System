@echo off
title CIRS Civic Portal - Port 8000
cls
echo =========================================================
echo    Citizen Issue Reporting System (CIRS) Launcher
echo =========================================================
echo.

:: 1. Check if MySQL (Port 3306) is running in XAMPP
netstat -ano | findstr "LISTENING" | findstr ":3306" >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] WARNING: MySQL is NOT running on port 3306.
    echo     Please click "Start" on MySQL in the XAMPP Control Panel.
    echo.
) else (
    echo [OK] MySQL Database is connected on port 3306.
)

echo.
echo Starting Web Application on http://localhost:8000 ...
echo NOTE: Do NOT close this black window while using the website.
echo.

:: 2. Automatically open the browser
start "" "http://localhost:8000"

:: 3. Launch PHP built-in server on Port 8000
if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S 127.0.0.1:8000
) else (
    php -S 127.0.0.1:8000
)

pause
