@echo off
setlocal

set PHP_DIR=%~dp0php

echo ========================================
echo   Manar Market - PHP Setup
echo ========================================
echo.

if not exist "%PHP_DIR%\php.exe" (
    echo PHP not found. Running download script...
    call "%~dp0download-php.bat"
    if errorlevel 1 exit /b 1
)

echo Configuring php.ini...

(
echo [PHP]
echo extension_dir = "ext"
echo.
echo ; Extensions
echo extension=curl
echo extension=fileinfo
echo extension=gd
echo extension=intl
echo extension=mbstring
echo extension=openssl
echo extension=pdo_sqlite
echo extension=pdo_mysql
echo extension=sqlite3
echo extension=zip
echo.
echo ; Settings
echo memory_limit = 256M
echo upload_max_filesize = 50M
echo post_max_size = 50M
echo max_execution_time = 300
echo max_input_time = 300
echo.
echo ; Error handling
echo display_errors = Off
echo log_errors = On
echo error_log = "%PHP_DIR%\php_errors.log"
echo.
echo ; Timezone
echo date.timezone = "Africa/Tripoli"
echo.
echo ; Session
echo session.save_path = "%TEMP%"
) > "%PHP_DIR%\php.ini"

echo.
echo PHP configured successfully!
echo.
echo Testing PHP...
"%PHP_DIR%\php.exe" -m

endlocal
