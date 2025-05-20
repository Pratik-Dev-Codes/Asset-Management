@echo off
echo Starting optimized migration with custom PHP settings...

set PHP_INI_SCAN_DIR=
set PHPRC=%~dp0custom-php.ini

php -c "%CD%\custom-php.ini" -d memory_limit=2G -d max_execution_time=300 run_migration.php

echo Migration completed with status: %errorlevel%
pause
