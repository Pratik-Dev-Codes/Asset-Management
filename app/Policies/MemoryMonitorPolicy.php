<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class MemoryMonitorPolicy
{
    /**
     * Determine whether the user can view the memory monitor.
     */
    public function view(User $user): Response
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('You do not have permission to view the memory monitor.');
    }
}
