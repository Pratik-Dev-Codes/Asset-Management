<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check roles table
if (Schema::hasTable('roles')) {
    echo "Roles table columns:\n";
    $columns = Schema::getColumnListing('roles');
    print_r($columns);
    
    // Check if description column exists
    if (in_array('description', $columns)) {
        echo "\n✅ Description column exists in roles table\n";
    } else {
        echo "\n❌ Description column is missing from roles table\n";
    }
} else {
    echo "Roles table does not exist\n";
}

// Check permissions table
if (Schema::hasTable('permissions')) {
    echo "\nPermissions table columns:\n";
    $columns = Schema::getColumnListing('permissions');
    print_r($columns);
    
    // Check if description column exists
    if (in_array('description', $columns)) {
        echo "\n✅ Description column exists in permissions table\n";
    } else {
        echo "\n❌ Description column is missing from permissions table\n";
    }
} else {
    echo "\nPermissions table does not exist\n";
}
