@echo off
set PHP_MEMORY_LIMIT=2048M
set PHP_INI_SCAN_DIR=
set PHPRC=

php -d memory_limit=%PHP_MEMORY_LIMIT% artisan migrate

pause
