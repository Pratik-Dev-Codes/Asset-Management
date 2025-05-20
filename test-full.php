<?php

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test basic PHP functionality
echo "Testing PHP environment...\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n\n";

// Test if Composer autoloader exists
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("✗ Composer autoloader not found. Please run 'composer install' first.\n");
}

// Load the Composer autoloader
require $autoloadPath;
echo "✓ Composer autoloader loaded\n";

// Test if Laravel's bootstrap file exists
$bootstrapPath = __DIR__ . '/bootstrap/app.php';
if (!file_exists($bootstrapPath)) {
    die("✗ Laravel bootstrap file not found.\n");
}

// Create the application
$app = require_once $bootstrapPath;
echo "✓ Laravel application created\n";

// Bootstrap the application
try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    echo "✓ Application bootstrapped successfully\n";
} catch (\Exception $e) {
    die("✗ Failed to bootstrap application: " . $e->getMessage() . "\n");
}

// Test HTTP kernel
try {
    $httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✓ HTTP Kernel resolved\n";
} catch (\Exception $e) {
    echo "✗ Failed to resolve HTTP Kernel: " . $e->getMessage() . "\n";
}

// Test database connection if enabled
try {
    if (class_exists('Illuminate\Support\Facades\DB')) {
        echo "\nTesting database connection...\n";
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        echo "✓ Database connection successful\n";
    } else {
        echo "\n✗ Database facade not available\n";
    }
} catch (\Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Test cache if enabled
try {
    if (class_exists('Illuminate\Support\Facades\Cache')) {
        $cacheKey = 'test_cache_' . time();
        echo "\nTesting cache...\n";
        \Illuminate\Support\Facades\Cache::put($cacheKey, 'test_value', 1);
        $value = \Illuminate\Support\Facades\Cache::get($cacheKey);
        echo $value === 'test_value' ? "✓ Cache test passed\n" : "✗ Cache test failed\n";
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
    } else {
        echo "\n✗ Cache facade not available\n";
    }
} catch (\Exception $e) {
    echo "✗ Cache test failed: " . $e->getMessage() . "\n";
}

// Test environment
$environment = $app->environment();
echo "\nApplication Environment: " . $environment . "\n";

// Test configuration
$appName = config('app.name', 'Not set');
echo "Application Name: " . $appName . "\n";

// Memory usage
echo "\nMemory Usage: " . number_format(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";
echo "Peak Memory Usage: " . number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";

echo "\nApplication test completed!\n";
