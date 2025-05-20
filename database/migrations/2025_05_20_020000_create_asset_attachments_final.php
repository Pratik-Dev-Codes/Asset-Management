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
        if (!Schema::hasTable('asset_attachments')) {
            Schema::create('asset_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('filename');
                $table->string('storage_path');
                $table->string('mime_type');
                $table->unsignedBigInteger('size');
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamps();
                
                // Add indexes for better performance
                $table->index(['asset_id', 'user_id']);
            });
            
            // Add foreign key constraints separately to avoid issues
            Schema::table('asset_attachments', function (Blueprint $table) {
                $table->foreign('asset_id')
                    ->references('id')
                    ->on('assets')
                    ->onDelete('cascade');
                    
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys first to avoid constraint errors
        Schema::table('asset_attachments', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->dropForeign(['user_id']);
        });
        
        Schema::dropIfExists('asset_attachments');
    }
};
