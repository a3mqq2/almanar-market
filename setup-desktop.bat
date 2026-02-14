@echo off
setlocal

set PHP_DIR=%~dp0php
set APP_DIR=%~dp0

echo ========================================
echo   Manar Market - Desktop Setup
echo ========================================
echo.

if not exist "%PHP_DIR%\php.exe" (
    echo PHP not found. Running download script...
    call "%~dp0download-php.bat"
    if errorlevel 1 exit /b 1
)

if not exist "%PHP_DIR%\php.ini" (
    call "%~dp0setup-php.bat"
)

cd /d "%APP_DIR%"

echo.
echo [1/4] Creating .env file...
if not exist ".env" (
    copy ".env.desktop" ".env" >nul
    echo Created .env from .env.desktop
) else (
    echo .env already exists
)

echo.
echo [2/4] Generating APP_KEY...
"%PHP_DIR%\php.exe" artisan key:generate --force

echo.
echo [3/4] Creating SQLite database...
if not exist "database\database.sqlite" (
    type nul > "database\database.sqlite"
    echo Created database/database.sqlite
) else (
    echo database.sqlite already exists
)

echo.
echo [4/4] Running migrations...
"%PHP_DIR%\php.exe" artisan migrate --force

echo.
echo ========================================
echo   Setup Complete!
echo ========================================
echo.
echo Run start.bat to launch the application
echo.

pause
endlocal
