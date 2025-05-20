<?php

// Print initial memory usage
printf("Initial memory usage: %.2f MB\n", memory_get_usage(true) / 1048576);

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
printf("After bootstrap: %.2f MB\n", memory_get_usage(true) / 1048576);

// Run the route:list command
use Illuminate\Contracts\Console\Kernel;
$kernel = $app->make(Kernel::class);

ob_start();
$status = $kernel->call('route:list');
$output = ob_get_clean();
printf("After route:list: %.2f MB\n", memory_get_usage(true) / 1048576);

// Print a summary
if ($status === 0) {
    echo "route:list executed successfully.\n";
} else {
    echo "route:list failed with status $status.\n";
} 