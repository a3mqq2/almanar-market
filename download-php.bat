@echo off
setlocal enabledelayedexpansion

set PHP_VERSION=8.3.29
set PHP_URL=https://windows.php.net/downloads/releases/php-%PHP_VERSION%-nts-Win32-vs16-x64.zip
set PHP_DIR=%~dp0php
set PHP_ZIP=%TEMP%\php.zip

echo ========================================
echo   Manar Market - PHP Downloader
echo ========================================
echo.

if exist "%PHP_DIR%\php.exe" (
    echo PHP already exists at %PHP_DIR%\php.exe
    "%PHP_DIR%\php.exe" -v
    echo.
    set /p REINSTALL="Reinstall PHP? (y/n): "
    if /i not "!REINSTALL!"=="y" (
        echo Cancelled.
        exit /b 0
    )
    echo.
    echo Removing old PHP...
    rmdir /s /q "%PHP_DIR%" 2>nul
)

echo Downloading PHP %PHP_VERSION%...
echo URL: %PHP_URL%
echo.

powershell -Command "& {[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri '%PHP_URL%' -OutFile '%PHP_ZIP%'}"

if not exist "%PHP_ZIP%" (
    echo ERROR: Download failed!
    exit /b 1
)

echo Extracting to %PHP_DIR%...
if not exist "%PHP_DIR%" mkdir "%PHP_DIR%"

powershell -Command "& {Expand-Archive -Path '%PHP_ZIP%' -DestinationPath '%PHP_DIR%' -Force}"

del "%PHP_ZIP%" 2>nul

if exist "%PHP_DIR%\php.exe" (
    echo.
    echo ========================================
    echo   PHP installed successfully!
    echo ========================================
    echo.
    "%PHP_DIR%\php.exe" -v
    echo.
    echo Location: %PHP_DIR%\php.exe
) else (
    echo ERROR: Installation failed!
    exit /b 1
)

endlocal
