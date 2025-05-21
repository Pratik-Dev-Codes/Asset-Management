<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Schema;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Disable model events to prevent cache issues
            $dispatcher = User::getEventDispatcher();
            User::unsetEventDispatcher();
            
            // Disable query log to save memory
            DB::disableQueryLog();
            
            $this->command->info('Starting admin user setup...');
            
            // Ensure the users table exists
            if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
                $this->command->error('Users table does not exist. Please run migrations first.');
                return;
            }
            // Create or get the admin user
            $admin = User::firstOrCreate(
                ['email' => 'admin@neepco.com'],
                [
                    'name' => 'Admin User',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            $this->command->info('Admin user created/retrieved.');

            // Directly insert the admin role if it doesn't exist
            $roleId = DB::table('roles')
                ->where('name', 'admin')
                ->value('id');

            if (!$roleId) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => 'admin',
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info('Admin role created.');
            } else {
                $this->command->info('Admin role already exists.');
            }

            // Assign role to user directly in the database
            $existingRole = DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->where('model_id', $admin->id)
                ->where('role_id', $roleId)
                ->exists();

            if (!$existingRole) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $roleId,
                    'model_type' => User::class,
                    'model_id' => $admin->id,
                ]);
                $this->command->info('Admin role assigned to user.');
            } else {
                $this->command->info('User already has admin role.');
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
                $permissionId = DB::table('permissions')
                    ->where('name', $permission)
                    ->value('id');

                if (!$permissionId) {
                    $permissionId = DB::table('permissions')->insertGetId([
                        'name' => $permission,
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $createdPermissions[] = $permission;
                }
            }

            if (!empty($createdPermissions)) {
                $this->command->info('Created permissions: ' . implode(', ', $createdPermissions));
            } else {
                $this->command->info('All permissions already exist.');
            }

            // Assign all permissions to admin role
            $existingPermissions = DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->pluck('permission_id')
                ->toArray();

            $newPermissions = DB::table('permissions')
                ->whereIn('name', $permissions)
                ->whereNotIn('id', $existingPermissions)
                ->pluck('id')
                ->toArray();

            if (!empty($newPermissions)) {
                $rolePermissions = [];
                foreach ($newPermissions as $permissionId) {
                    $rolePermissions[] = [
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ];
                }
                
                DB::table('role_has_permissions')->insert($rolePermissions);
                $this->command->info('Assigned ' . count($newPermissions) . ' permissions to admin role.');
            } else {
                $this->command->info('All permissions already assigned to admin role.');
            }

            $this->command->info('Admin user setup completed successfully!');
            $this->command->info('Email: admin@neepco.com');
            $this->command->info('Password: password');
            
        } catch (\Exception $e) {
            $this->command->error('Error in AdminUserSeeder: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        } finally {
            // Re-enable model events
            User::setEventDispatcher($dispatcher);
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            // Re-enable query log
            DB::enableQueryLog();
        }
    }
}
