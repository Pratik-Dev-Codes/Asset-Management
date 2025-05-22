<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin'], [
            'guard_name' => 'web',
            'description' => 'Administrator with full access',
        ]);

        // Create permissions if they don't exist
        $permissions = [
            'view users', 'create users', 'edit users', 'delete users',
            'view roles', 'create roles', 'edit roles', 'delete roles',
            'view permissions', 'create permissions', 'edit permissions', 'delete permissions',
            'view assets', 'create assets', 'edit assets', 'delete assets',
            'view departments', 'create departments', 'edit departments', 'delete departments',
            'view locations', 'create locations', 'edit locations', 'delete locations',
            'view maintenance', 'create maintenance', 'edit maintenance', 'delete maintenance',
            'view reports', 'generate reports', 'export reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission], [
                'guard_name' => 'web',
                'description' => ucfirst(str_replace('_', ' ', $permission)),
            ]);
        }

        // Assign all permissions to admin role
        $adminRole->syncPermissions(Permission::all());

        // Create admin user if it doesn't exist
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Assign admin role to the user
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
    }
}
