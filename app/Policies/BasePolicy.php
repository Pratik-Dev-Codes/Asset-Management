<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BasePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny '.$this->getModelName());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, $model): bool
    {
        // Allow users to view their own models
        if (method_exists($model, 'user_id') && $model->user_id === $user->id) {
            return true;
        }

        return $user->can('view '.$this->getModelName());
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create '.$this->getModelName());
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $model): bool
    {
        // Allow users to update their own models
        if (method_exists($model, 'user_id') && $model->user_id === $user->id) {
            return true;
        }

        return $user->can('update '.$this->getModelName());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, $model): bool
    {
        // Prevent users from deleting their own account
        if ($model instanceof User && $model->id === $user->id) {
            return false;
        }

        // Allow users to delete their own models
        if (method_exists($model, 'user_id') && $model->user_id === $user->id) {
            return $user->can('delete own '.$this->getModelName());
        }

        return $user->can('delete '.$this->getModelName());
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, $model): bool
    {
        return $user->can('restore '.$this->getModelName());
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, $model): bool
    {
        return $user->hasRole('super-admin');
    }

    /**
     * Get the model name in kebab case.
     */
    protected function getModelName(): string
    {
        return strtolower(class_basename(get_class($this)));
    }
}
