<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin user with all permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Create or get the admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin'], [
            'guard_name' => 'web',
            'description' => 'Administrator with all permissions',
        ]);

        // Create a new user
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@neepco.com',
            'password' => Hash::make('password'),
            'department' => 'Administration',
            'designation' => 'System Administrator',
            'email_verified_at' => now(),
        ]);

        // Assign admin role to the user
        $user->assignRole($adminRole);

        $this->info('Admin user created successfully!');
        $this->line('Email: admin@neepco.com');
        $this->line('Password: password');
        $this->newLine();
        $this->warn('Please change the password after first login!');
    }
}
