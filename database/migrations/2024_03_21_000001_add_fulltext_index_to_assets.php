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
        // Add full-text search index
        DB::statement('ALTER TABLE assets ADD FULLTEXT assets_search_idx (name, asset_code, serial_number, model, manufacturer, notes)');
        
        // Add composite indexes for common search patterns
        Schema::table('assets', function (Blueprint $table) {
            $table->index(['status', 'purchase_date']);
            $table->index(['category_id', 'purchase_date']);
            $table->index(['location_id', 'purchase_date']);
            $table->index(['department_id', 'purchase_date']);
            $table->index(['assigned_to', 'purchase_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove full-text search index
        DB::statement('ALTER TABLE assets DROP INDEX assets_search_idx');
        
        // Remove composite indexes
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['status', 'purchase_date']);
            $table->dropIndex(['category_id', 'purchase_date']);
            $table->dropIndex(['location_id', 'purchase_date']);
            $table->dropIndex(['department_id', 'purchase_date']);
            $table->dropIndex(['assigned_to', 'purchase_date']);
        });
    }
}; 