@echo off
setlocal

set PHP_DIR=%~dp0php
set APP_DIR=%~dp0

echo ========================================
echo   Manar Market - Desktop
echo ========================================
echo.

if not exist "%PHP_DIR%\php.exe" (
    echo PHP not found. Setting up...
    call "%~dp0setup-php.bat"
    if errorlevel 1 (
        echo Setup failed!
        pause
        exit /b 1
    )
)

if not exist "%PHP_DIR%\php.ini" (
    call "%~dp0setup-php.bat"
)

cd /d "%APP_DIR%"

echo Starting server on http://127.0.0.1:8000
echo Press Ctrl+C to stop
echo.

start "" "http://127.0.0.1:8000"

"%PHP_DIR%\php.exe" artisan serve --host=127.0.0.1 --port=8000

endlocal
