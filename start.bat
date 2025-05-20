@echo off
echo Starting Asset Management System...
echo =================================

echo [1/4] Setting up environment...
set PHP_INI_SCAN_DIR=
set PHPRC=%~dp0php.ini

if not exist php.ini (
    echo Creating php.ini with optimized settings...
    echo memory_limit = 2G > php.ini
    echo max_execution_time = 300 >> php.ini
    echo max_input_time = 300 >> php.ini
    echo post_max_size = 100M >> php.ini
    echo upload_max_filesize = 100M >> php.ini
    echo max_input_vars = 3000 >> php.ini
    echo date.timezone = UTC >> php.ini
)

echo [2/4] Checking environment...
php check_environment.php

if %ERRORLEVEL% NEQ 0 (
    echo Error: Environment check failed. Please fix the issues above.
    pause
    exit /b %ERRORLEVEL%
)

echo [3/4] Installing dependencies...
if not exist vendor (
    composer install --no-dev --optimize-autoloader
) else (
    composer update --no-dev --optimize-autoloader
)

echo [4/4] Starting the application...

:: Check if .env exists and has APP_KEY
findstr /C:"APP_KEY=" .env >nul
if %ERRORLEVEL% NEQ 0 (
    echo Generating application key...
    php artisan key:generate
)

:: Run migrations
php artisan migrate --force

:: Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

:: Start the development server
php artisan serve --host=0.0.0.0 --port=8000

pause
