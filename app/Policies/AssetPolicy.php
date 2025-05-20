<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny asset') || 
               $user->can('view own assets');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $asset): bool
    {
        // Allow users to view their own assets
        if ($asset->assigned_to === $user->id) {
            return true;
        }
        
        return $user->can('view asset') || 
               $user->can('view any asset');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create asset');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $asset): bool
    {
        // Allow users to update their own assets
        if ($asset->assigned_to === $user->id) {
            return $user->can('update own asset');
        }
        
        return $user->can('update asset') || 
               $user->can('update any asset');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $asset): bool
    {
        // Prevent deletion of critical assets
        if ($asset->is_critical) {
            return $user->hasRole('super-admin');
        }
        
        // Allow users to delete their own assets
        if ($asset->assigned_to === $user->id) {
            return $user->can('delete own asset');
        }
        
        return $user->can('delete asset') || 
               $user->can('delete any asset');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, $asset): bool
    {
        return $user->can('restore asset');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, $asset): bool
    {
        return $user->hasRole('super-admin');
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
