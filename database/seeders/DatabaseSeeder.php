<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Create roles and permissions
        $this->call([
            ReportSeeder::class,
            RoleAndPermissionSeeder::class,
            ReportPermissionsSeeder::class,
            // ReportTestDataSeeder::class, // Uncomment for testing
        ]);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role to the admin user
        $admin->assignRole('admin');

        // Create regular users if needed
        // User::factory(10)->create()->each(function ($user) {
        //     $user->assignRole('user');
        // });
    }
}
