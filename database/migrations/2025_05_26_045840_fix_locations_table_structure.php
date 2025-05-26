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
        Schema::table('locations', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('locations', 'code')) {
                $table->string('code')->after('name')->nullable();
            }
            if (!Schema::hasColumn('locations', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('zip_code');
            }
            if (!Schema::hasColumn('locations', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('locations', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('locations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
