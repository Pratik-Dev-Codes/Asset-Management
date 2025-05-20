<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ReportPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Report permissions
        $permissions = [
            // Report permissions
            'view reports',
            'create reports',
            'edit reports',
            'delete reports',
            'generate reports',
            'schedule reports',

            // Report file permissions
            'view report files',
            'download report files',
            'delete report files',
            'cleanup report files',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $managerRole = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $auditorRole = Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web']);

        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Manager permissions
        $managerRole->givePermissionTo([
            'view reports',
            'create reports',
            'edit reports',
            'delete reports',
            'generate reports',
            'schedule reports',
            'view report files',
            'download report files',
        ]);

        // Auditor permissions (view and download only)
        $auditorRole->givePermissionTo([
            'view reports',
            'view report files',
            'download report files',
        ]);

        // Regular user permissions (limited)
        $userRole->givePermissionTo([
            'view reports',
            'view report files',
            'download report files',
        ]);

        $this->command->info('Report permissions seeded successfully.');
    }
}
