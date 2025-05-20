@echo off
echo Starting minimal optimization...
echo.

:: Run the minimal optimization command
php -d memory_limit=256M -d opcache.enable=0 -d realpath_cache_size=0 artisan optimize:minimal

echo.
echo Optimization completed.
pause
