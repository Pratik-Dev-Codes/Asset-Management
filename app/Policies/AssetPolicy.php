<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any assets.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view assets');
    }

    /**
     * Determine whether the user can view the asset.
     */
    public function view(User $user, Asset $asset): bool
    {
        return $user->hasPermissionTo('view assets') ||
            $user->id === $asset->assigned_to;
    }

    /**
     * Determine whether the user can create assets.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create assets');
    }

    /**
     * Determine whether the user can update the asset.
     */
    public function update(User $user, Asset $asset): bool
    {
        return $user->hasPermissionTo('edit assets');
    }

    /**
     * Determine whether the user can delete the asset.
     */
    public function delete(User $user, Asset $asset): bool
    {
        return $user->hasPermissionTo('delete assets');
    }

    /**
     * Determine whether the user can restore the asset.
     */
    public function restore(User $user, Asset $asset): bool
    {
        return $user->hasPermissionTo('restore assets');
    }

    /**
     * Determine whether the user can permanently delete the asset.
     */
    public function forceDelete(User $user, Asset $asset): bool
    {
        return $user->hasPermissionTo('force delete assets');
    }

    /**
     * Determine whether the user can check in the asset.
     */
    public function checkIn(User $user, Asset $asset): bool
    {
        return $user->can('checkin asset') ||
               $user->can('checkin any asset') ||
               $asset->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can check out the asset.
     */
    public function checkOut(User $user, Asset $asset): bool
    {
        return $user->can('checkout asset') ||
               $user->can('checkout any asset');
    }

    /**
     * Determine whether the user can request maintenance for the asset.
     */
    public function requestMaintenance(User $user, Asset $asset): bool
    {
        return $user->can('request maintenance') ||
               $asset->assigned_to === $user->id;
    }
}
