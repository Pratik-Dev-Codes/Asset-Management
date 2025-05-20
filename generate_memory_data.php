<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

// Ensure the current directory is the application's root
define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/
require __DIR__.'/vendor/autoload.php';

// Initialize the application
$app = require_once __DIR__.'/bootstrap/app.php';

// Get command line arguments
$hours = isset($argv[1]) ? (int)$argv[1] : 24;
$interval = isset($argv[2]) ? (int)$argv[2] : 5;

// Configure PHP settings
ini_set('memory_limit', '2G');
set_time_limit(0);

try {
    // Disable query logging
    $app['db']->connection()->disableQueryLog();
    
    // Get the kernel instance
    $kernel = $app->make(Kernel::class);
    
    // Run the memory data generation command
    $status = $kernel->handle(
        $input = new ArgvInput(['artisan', 'memory:generate-data', "--hours={$hours}", "--interval={$interval}"]),
        new ConsoleOutput
    );
    
    $kernel->terminate($input, $status);
    exit($status);
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
