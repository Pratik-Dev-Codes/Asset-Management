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
        // Drop existing foreign key constraints that reference the locations table
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
        });
        
        // Drop the existing locations table
        Schema::dropIfExists('locations');
        
        // Recreate the locations table with the correct structure
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');
            $table->string('zip_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Recreate the foreign key constraint
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a destructive operation, so we'll leave this empty
        // to prevent accidental data loss in production
    }
};
