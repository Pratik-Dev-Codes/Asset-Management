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
        // Add description to roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->text('description')->nullable()->after('guard_name');
        });

        // Add description to permissions table
        Schema::table('permissions', function (Blueprint $table) {
            $table->text('description')->nullable()->after('guard_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove description from roles table
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        // Remove description from permissions table
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
