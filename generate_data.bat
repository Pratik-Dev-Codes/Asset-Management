@echo off
echo Starting memory data generation with optimized settings...

set PHP_INI_SCAN_DIR=
set PHPRC=%~dp0custom-php.ini

php -c "%CD%\custom-php.ini" -d memory_limit=2G -d max_execution_time=0 generate_memory_data.php %*

echo Data generation completed with status: %errorlevel%
pause
