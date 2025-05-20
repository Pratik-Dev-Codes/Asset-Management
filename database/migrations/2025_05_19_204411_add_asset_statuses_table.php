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
        Schema::create('asset_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('color', 20)->default('#777777');
            $table->text('notes')->nullable();
            $table->boolean('show_in_nav')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Insert default statuses
        $statuses = [
            [
                'id' => 1,
                'name' => 'Deployed',
                'type' => 'deployed',
                'color' => '#2ecc71',
                'show_in_nav' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Ready to Deploy',
                'type' => 'pending',
                'color' => '#3498db',
                'show_in_nav' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Pending',
                'type' => 'pending',
                'color' => '#f39c12',
                'show_in_nav' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Archived',
                'type' => 'archived',
                'color' => '#95a5a6',
                'show_in_nav' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'Out for Repair',
                'type' => 'maintenance',
                'color' => '#e74c3c',
                'show_in_nav' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'name' => 'Broken',
                'type' => 'maintenance',
                'color' => '#8e44ad',
                'show_in_nav' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'name' => 'Lost/Stolen',
                'type' => 'archived',
                'color' => '#2c3e50',
                'show_in_nav' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('asset_statuses')->insert($statuses);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_statuses');
    }
};
