<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Add indexes for commonly filtered columns
            $table->index('status');
            $table->index('category_id');
            $table->index('location_id');
            $table->index('department_id');
            $table->index('assigned_to');
            $table->index('purchase_date');
            $table->index('created_at');
            $table->index('updated_at');
            
            // Add composite indexes for common query patterns
            $table->index(['status', 'category_id']);
            $table->index(['status', 'location_id']);
            $table->index(['status', 'department_id']);
            $table->index(['category_id', 'location_id']);
            $table->index(['category_id', 'department_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Remove indexes
            $table->dropIndex(['status']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['location_id']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['purchase_date']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
            
            // Remove composite indexes
            $table->dropIndex(['status', 'category_id']);
            $table->dropIndex(['status', 'location_id']);
            $table->dropIndex(['status', 'department_id']);
            $table->dropIndex(['category_id', 'location_id']);
            $table->dropIndex(['category_id', 'department_id']);
        });
    }
}; 