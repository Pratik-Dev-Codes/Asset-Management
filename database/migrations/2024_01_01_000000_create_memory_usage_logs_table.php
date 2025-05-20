<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable query logging to save memory
        DB::connection()->disableQueryLog();
        
        // Use raw SQL for better performance
        DB::statement('
            CREATE TABLE memory_usage_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                memory_used DOUBLE(10,2) NOT NULL,
                memory_peak DOUBLE(10,2) NOT NULL,
                cpu_usage DOUBLE(5,2) NOT NULL,
                disk_usage DOUBLE(5,2) NOT NULL,
                queue_size INT UNSIGNED NOT NULL,
                active_workers INT UNSIGNED NOT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                INDEX created_at_index (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memory_usage_logs');
    }
};
