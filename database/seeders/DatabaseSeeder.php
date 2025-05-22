<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Create admin user and roles
        $this->call([
            UserSeeder::class,
        ]);
        
        // Uncomment these once the database is stable
        /*
        $this->call([
            RoleAndPermissionSeeder::class,
            ReportPermissionsSeeder::class,
            ReportSeeder::class,
            // ReportTestDataSeeder::class, // Uncomment for testing
        ]);
        
        // Create regular users if needed
        User::factory(10)->create()->each(function ($user) {
            $user->assignRole('user');
        });
        */
    }
}
