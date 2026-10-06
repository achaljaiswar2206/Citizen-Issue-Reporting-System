@echo off
title Citizen Issue Reporting System (CIRS) - Port 8000
echo ===================================================
echo   Citizen Issue Reporting System (CIRS)
echo   Starting Web Application on Port 8000...
echo ===================================================
echo.

:: Check if MySQL port 3306 is reachable
netstat -ano | findstr ":3306" >nul 2>&1
if %errorlevel% neq 0 (
    echo [WARNING] MySQL (port 3306) does not seem to be running!
    echo Please make sure MySQL is started in XAMPP Control Panel.
    echo.
)

echo Starting PHP Server at http://localhost:8000 ...
echo Press Ctrl+C at any time to stop the server.
echo.

:: Open default browser to CIRS portal
start "" "http://localhost:8000"

:: Start PHP built-in server
if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S 127.0.0.1:8000
) else (
    php -S 127.0.0.1:8000
)

pause
