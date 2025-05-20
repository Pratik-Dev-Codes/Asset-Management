<?php

namespace App\Traits;

use App\Models\User;

trait HasAuthorization
{
    /**
     * Check if the model is accessible by the given user.
     *
     * @param  \App\Models\User|null  $user
     * @return bool
     */
    public function isAccessibleBy($user = null)
    {
        // Public models are accessible by anyone
        if (property_exists($this, 'is_public') && $this->is_public) {
            return true;
        }
        
        // If no user is provided, check the authenticated user
        if (!$user) {
            $user = auth()->user();
        }
        
        // If still no user, access is denied
        if (!$user) {
            return false;
        }
        
        // Check if user is the creator (if model has created_by)
        if (property_exists($this, 'created_by') && $this->created_by === $user->id) {
            return true;
        }
        
        // Check for admin role if available
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }
        
        // Check for specific permission if available
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('view all reports');
        }
        
        return false;
    }
}
