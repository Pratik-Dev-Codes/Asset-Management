<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ThemeController extends Controller
{
    /**
     * Toggle dark mode
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Toggle the application's dark mode.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Request $request)
    {
        try {
            $request->validate([
                'dark_mode' => 'required|boolean',
            ]);

            $darkMode = $request->boolean('dark_mode');

            // Update session
            session(['dark_mode' => $darkMode]);

            // Update user preference if authenticated
            if (Auth::check()) {
                $user = User::find(Auth::id());
                if ($user && $user->dark_mode !== $darkMode) {
                    $user->dark_mode = $darkMode;
                    $user->save();

                    Log::info("User {$user->id} updated dark mode preference to: ".($darkMode ? 'dark' : 'light'));
                }
            }

            return response()->json([
                'success' => true,
                'dark_mode' => $darkMode,
                'message' => 'Theme preference updated successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating theme preference: '.$e->getMessage(), [
                'exception' => $e,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update theme preference. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
