<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SetupAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up the admin user with all necessary permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting admin user setup...');

        DB::beginTransaction();

        try {
            // Create admin user
            $admin = User::firstOrCreate(
                ['email' => 'admin@neepco.com'],
                [
                    'name' => 'Admin User',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $this->info('Admin user created/retrieved.');

            // Create admin role if it doesn't exist
            $adminRole = Role::firstOrCreate(
                ['name' => 'admin'],
                ['guard_name' => 'web']
            );

            $this->info('Admin role created/retrieved.');

            // Assign admin role to the user
            if (!$admin->hasRole('admin')) {
                $admin->assignRole($adminRole);
                $this->info('Admin role assigned to user.');
            }

            // Create basic permissions
            $permissions = [
                'view assets', 'create assets', 'edit assets', 'delete assets',
                'view users', 'create users', 'edit users', 'delete users',
                'view roles', 'create roles', 'edit roles', 'delete roles',
                'view departments', 'create departments', 'edit departments', 'delete departments',
                'view locations', 'create locations', 'edit locations', 'delete locations',
                'view maintenance', 'create maintenance', 'edit maintenance', 'delete maintenance',
                'view reports', 'generate reports', 'export reports',
            ];

            $createdPermissions = [];
            foreach ($permissions as $permission) {
                $createdPermissions[] = Permission::firstOrCreate(
                    ['name' => $permission],
                    ['guard_name' => 'web']
                )->name;
            }

            $this->info('Created permissions: ' . implode(', ', $createdPermissions));

            // Assign all permissions to admin role
            $adminRole->syncPermissions($permissions);
            $this->info('All permissions assigned to admin role.');

            DB::commit();

            $this->info('Admin user setup completed successfully!');
            $this->info('Email: admin@neepco.com');
            $this->info('Password: password');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error setting up admin user: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
