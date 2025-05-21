<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LocationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any locations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view locations');
    }

    /**
     * Determine whether the user can view the location.
     */
    public function view(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('view locations');
    }

    /**
     * Determine whether the user can create locations.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create locations');
    }

    /**
     * Determine whether the user can update the location.
     */
    public function update(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('edit locations');
    }

    /**
     * Determine whether the user can delete the location.
     */
    public function delete(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('delete locations');
    }

    /**
     * Determine whether the user can restore the location.
     */
    public function restore(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('restore locations');
    }

    /**
     * Determine whether the user can permanently delete the location.
     */
    public function forceDelete(User $user, Location $location): bool
    {
        return $user->hasPermissionTo('force delete locations');
    }
} 