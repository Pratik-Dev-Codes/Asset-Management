<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TestRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test role and permission functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Create a test role if it doesn't exist
        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->info('Role "admin" created or already exists.');

        // Create a test user if it doesn't exist
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );
        $this->info('User created or already exists.');

        // Assign the admin role to the user
        $user->assignRole('admin');
        $this->info('Assigned "admin" role to test user.');

        // Test if the user has the admin role
        if ($user->hasRole('admin')) {
            $this->info('Success! The user has the admin role.');
        } else {
            $this->error('Error: The user does not have the admin role.');
        }
    }
}
