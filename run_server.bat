@echo off
echo Starting Laravel development server with optimized settings...

set PHP_INI_SCAN_DIR=
set PHPRC=%~dp0custom-php.ini

php -c "%CD%\custom-php.ini" -d memory_limit=2G -d max_execution_time=0 artisan serve --host=0.0.0.0 --port=8000

pause
