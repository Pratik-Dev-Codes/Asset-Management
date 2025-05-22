@echo off
setlocal enabledelayedexpansion

:: Colors
set "RED=<NUL set /p=^>&2"
set "GREEN=<NUL set /p=^>&2"
set "YELLOW=<NUL set /p=^>&2"
set "NC=<NUL set /p=^>&2"

echo !YELLOW!Starting Laravel Application Tests...!NC!
echo.

:: Check if PHP is installed
where php >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo !RED!PHP is not installed or not in PATH!NC!
    exit /b 1
)

:: Check if Composer is installed
where composer >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo !RED!Composer is not installed or not in PATH!NC!
    exit /b 1
)

:: Check if Node.js is installed
where node >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo !YELLOW!Node.js is not installed or not in PATH!NC!
)

:: Check if NPM is installed
where npm >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo !YELLOW!NPM is not installed or not in PATH!NC!
)

:: Install PHP dependencies
echo !YELLOW!Installing PHP dependencies...!NC!
call composer install --no-interaction --prefer-dist --optimize-autoloader

:: Install NPM dependencies if package.json exists
if exist package.json (
    echo !YELLOW!Installing NPM dependencies...!NC!
    call npm install
    
    echo !YELLOW!Building assets...!NC!
    call npm run dev
)

:: Create environment file if it doesn't exist
if not exist .env (
    echo !YELLOW!Creating .env file...!NC!
    copy .env.example .env
    call php artisan key:generate
)

:: Set application key if not set
findstr /C:"APP_KEY=" .env | findstr /C:"APP_KEY=$" >nul
if not errorlevel 1 (
    echo !YELLOW!Generating application key...!NC!
    call php artisan key:generate
)

:: Create storage link if it doesn't exist
if not exist "public\storage" (
    echo !YELLOW!Creating storage link...!NC!
    call php artisan storage:link
)

:: Set directory permissions
echo !YELLOW!Setting directory permissions...!NC!
icacls "storage" /grant "IIS_IUSRS:(OI)(CI)F" /T
icacls "bootstrap\cache" /grant "IIS_IUSRS:(OI)(CI)F" /T

:: Clear caches
echo !YELLOW!Clearing caches...!NC!
call php artisan config:clear
call php artisan cache:clear
call php artisan view:clear
call php artisan route:clear

:: Run database migrations
echo !YELLOW!Running database migrations...!NC!
call php artisan migrate:fresh --seed

:: Run tests
echo !YELLOW!Running PHPUnit tests...!NC!
call php artisan test --stop-on-failure

:: Check for PHP syntax errors
echo !YELLOW!Checking for PHP syntax errors...!NC!
for /r app %%f in (*.php) do (
    php -l "%%f" | findstr /v "No syntax"
)

:: Check for JavaScript errors
if exist webpack.mix.js (
    echo !YELLOW!Checking for JavaScript errors...!NC!
    npx eslint resources/js/
)

:: Check for security vulnerabilities
echo !YELLOW!Checking for security vulnerabilities...!NC!
call php artisan security:check

:: Run static analysis
if exist vendor\bin\phpstan (
    echo !YELLOW!Running static analysis...!NC!
    call vendor\bin\phpstan analyse
)

:: Check code style
if exist vendor\bin\phpcs (
    echo !YELLOW!Checking code style...!NC!
    call vendor\bin\phpcs --standard=PSR12 app/
)

echo !GREEN!Test script completed!!NC!
echo.

:: Display any remaining issues
if exist storage\logs\laravel.log (
    echo !YELLOW!Recent errors from laravel.log:!NC!
    type storage\logs\laravel.log | tail -n 20
)

echo !GREEN!Application is ready for testing!!NC!
echo Run: !YELLOW!php artisan serve!NC! to start the development server
echo Then visit: !YELLOW!http://localhost:8000!NC! in your browser
echo.
