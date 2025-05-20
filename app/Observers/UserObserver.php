<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        Log::info('New user registered', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Log sensitive field changes
        $sensitiveFields = ['email', 'password', 'mfa_secret', 'is_active'];
        $changes = [];

        foreach ($sensitiveFields as $field) {
            if ($user->isDirty($field)) {
                $changes[$field] = [
                    'old' => $user->getOriginal($field),
                    'new' => $field === 'password' ? '***' : $user->$field,
                ];
            }
        }

        if (! empty($changes)) {
            Log::info('User updated - sensitive changes', [
                'user_id' => $user->id,
                'changes' => $changes,
                'updated_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        Log::warning('User deleted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'deleted_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        Log::info('User restored', [
            'user_id' => $user->id,
            'email' => $user->email,
            'restored_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        Log::warning('User force deleted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'deleted_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the User "login" event.
     */
    public function login(User $user): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);

        Log::info('User logged in', [
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle the User "logout" event.
     */
    public function logout(User $user): void
    {
        Log::info('User logged out', [
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
        ]);
    }
}
