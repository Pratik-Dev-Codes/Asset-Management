<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Check if user is already verified
        if ($user->hasVerifiedEmail()) {
            $message = 'Your email is already verified.';
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }
            
            return redirect()
                ->intended(RouteServiceProvider::HOME)
                ->with('status', $message);
        }

        try {
            // Send verification notification
            $user->sendEmailVerificationNotification();

            // Log the verification email sent
            Log::info('Verification email sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            $message = 'A fresh verification link has been sent to your email address.';
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }
            
            return back()->with('status', $message);
            
        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = 'Failed to send verification email. Please try again later.';
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }
            
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => $message]);
        }
    }
}
