@echo off
echo Starting optimized migration...

set PHP_MEMORY_LIMIT=2048M
set PHP_INI_SCAN_DIR=
set PHPRC=

echo Running migrations with increased memory limit...
php -d memory_limit=%PHP_MEMORY_LIMIT% artisan migrate --force

echo Migration completed.
pause
