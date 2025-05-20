<?php

use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Support\Facades\Route;

Route::get('/test-notification', function () {
    try {
        // Get the first user or create a test user if none exists
        $user = User::first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }
        
        // Send test notification
        $user->notify(new TestNotification([
            'title' => 'Test Notification',
            'message' => 'This is a test notification sent at ' . now()->toDateTimeString(),
            'url' => url('/'),
        ]));
        
        return response()->json([
            'status' => 'success',
            'message' => 'Test notification sent to user: ' . $user->email,
            'user_id' => $user->id,
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send test notification: ' . $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ], 500);
    }
});
