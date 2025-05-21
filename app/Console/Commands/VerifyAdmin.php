<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyAdmin extends Command
{
    protected $signature = 'app:verify-admin';
    protected $description = 'Verify admin user and permissions';

    public function handle()
    {
        // Check if admin user exists
        $admin = User::where('email', 'admin@neepco.com')->first();
        
        if (!$admin) {
            $this->error('Admin user not found!');
            return 1;
        }
        
        $this->info('Admin user exists:');
        $this->info('- Name: ' . $admin->name);
        $this->info('- Email: ' . $admin->email);
        $this->info('- Active: ' . ($admin->is_active ? 'Yes' : 'No'));
        
        // Check roles
        $roles = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $admin->id)
            ->pluck('roles.name')
            ->toArray();
            
        $this->info('\nRoles:');
        foreach ($roles as $role) {
            $this->info('- ' . $role);
        }
        
        // Check permissions
        $permissions = DB::table('role_has_permissions')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->join('model_has_roles', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $admin->id)
            ->pluck('permissions.name')
            ->toArray();
            
        $this->info('\nPermissions:');
        foreach (array_chunk($permissions, 4) as $chunk) {
            $this->info('- ' . implode(', ', $chunk));
        }
        
        $this->info('\nVerification complete!');
        return 0;
    }
}
