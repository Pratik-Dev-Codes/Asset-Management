<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run()
    {
        // Create some sample reports
        $user = User::first() ?? User::factory()->create();

        // Public reports
        Report::factory()
            ->count(5)
            ->public()
            ->create(['created_by' => $user->id]);

        // Private reports
        Report::factory()
            ->count(5)
            ->private()
            ->create(['created_by' => $user->id]);

        // Reports by type
        Report::factory()
            ->count(3)
            ->assetsType()
            ->create(['created_by' => $user->id]);

        Report::factory()
            ->count(3)
            ->maintenanceType()
            ->create(['created_by' => $user->id]);

        Report::factory()
            ->count(3)
            ->depreciationType()
            ->create(['created_by' => $user->id]);
    }
}
