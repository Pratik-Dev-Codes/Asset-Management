<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Create admin user first
        $this->call([
            AdminUserSeeder::class,
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
