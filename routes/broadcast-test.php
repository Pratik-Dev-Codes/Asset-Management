<?php

use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::get('/broadcast-test', function () {
    try {
        // Get the first user or create a test user if none exists
        $user = User::first();

        if (! $user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Test broadcasting
        $notification = new TestNotification([
            'title' => 'Broadcast Test',
            'message' => 'This is a test broadcast notification',
            'url' => url('/'),
        ]);

        // Send notification to trigger broadcast
        $user->notify($notification);

        return response()->json([
            'status' => 'success',
            'message' => 'Test broadcast sent to user: '.$user->email,
            'user_id' => $user->id,
            'channels' => ['private-user.'.$user->id],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send test broadcast: '.$e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ], 500);
    }
});
