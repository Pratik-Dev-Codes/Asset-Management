<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny user');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $model): bool
    {
        // Users can view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('view user') ||
               $user->can('view any user');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create user');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $model): bool
    {
        // Users can update their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Prevent updating super admin users unless you're a super admin
        if ($model->hasRole('super-admin') && ! $user->hasRole('super-admin')) {
            return false;
        }

        return $user->can('update user') ||
               $user->can('update any user');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $model): bool
    {
        // Prevent users from deleting themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Prevent deleting super admin users unless you're a super admin
        if ($model->hasRole('super-admin') && ! $user->hasRole('super-admin')) {
            return false;
        }

        return $user->can('delete user');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, $model): bool
    {
        return $user->can('restore user');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, $model): bool
    {
        // Only super admins can force delete users
        return $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can update the user's roles.
     */
    public function updateRoles(User $user, User $model): bool
    {
        // Prevent users from updating their own roles
        if ($user->id === $model->id) {
            return false;
        }

        // Only super admins can update admin roles
        if ($model->hasRole('super-admin') || $model->hasRole('admin')) {
            return $user->hasRole('super-admin');
        }

        return $user->can('update user roles');
    }

    /**
     * Determine whether the user can update the user's permissions.
     */
    public function updatePermissions(User $user, User $model): bool
    {
        // Prevent users from updating their own permissions
        if ($user->id === $model->id) {
            return false;
        }

        // Only super admins can update admin permissions
        if ($model->hasRole('super-admin') || $model->hasRole('admin')) {
            return $user->hasRole('super-admin');
        }

        return $user->can('update user permissions');
    }

    /**
     * Determine whether the user can enable/disable MFA for the user.
     */
    public function toggleMfa(User $user, User $model): bool
    {
        // Users can only manage their own MFA settings
        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('manage user mfa');
    }
}
