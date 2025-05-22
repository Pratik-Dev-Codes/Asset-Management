<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmablePasswordController extends Controller
{
    /**
     * The number of seconds to keep the password confirmation timeout.
     *
     * @var int
     */
    protected $passwordTimeout = 10800; // 3 hours

    /**
     * Show the confirm password view.
     *
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function show(Request $request)
    {
        // If the password was recently confirmed, redirect to the intended URL
        if ($this->isPasswordConfirmed($request)) {
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return Inertia::render('Auth/ConfirmPassword', [
            'passwordTimeout' => $this->passwordTimeout,
        ]);
    }

    /**
     * Confirm the user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Validate the request
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        try {
            // Check if the password is valid
            if (!Hash::check($request->password, $user->password)) {
                $this->handleFailedPasswordConfirmation($request);
            }

            // Store the confirmation timestamp in the session
            $request->session()->put([
                'auth.password_confirmed_at' => time(),
                'auth.password_confirmed_ip' => $request->ip(),
                'auth.password_confirmed_user_agent' => $request->userAgent(),
            ]);

            // Log the successful password confirmation
            Log::info('Password confirmed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Return appropriate response
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password confirmed successfully.',
                    'redirect' => session('url.intended', RouteServiceProvider::HOME),
                ]);
            }

            return redirect()->intended(RouteServiceProvider::HOME);

        } catch (\Exception $e) {
            Log::error('Password confirmation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password confirmation failed. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }

            throw $e;
        }
    }

    /**
     * Handle a failed password confirmation attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function handleFailedPasswordConfirmation(Request $request)
    {
        $user = $request->user();
        
        // Log the failed attempt
        Log::warning('Failed password confirmation attempt', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Throw validation exception
        throw ValidationException::withMessages([
            'password' => __('auth.password'),
        ]);
    }

    /**
     * Check if the password has been recently confirmed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isPasswordConfirmed(Request $request): bool
    {
        $confirmedAt = $request->session()->get('auth.password_confirmed_at', 0);
        
        // Check if the password was confirmed within the timeout period
        $isConfirmed = time() - $confirmedAt < $this->passwordTimeout;
        
        // If the IP or user agent has changed, require re-confirmation
        if ($isConfirmed) {
            $savedIp = $request->session()->get('auth.password_confirmed_ip');
            $savedUserAgent = $request->session()->get('auth.password_confirmed_user_agent');
            
            if ($savedIp !== $request->ip() || $savedUserAgent !== $request->userAgent()) {
                return false;
            }
        }
        
        return $isConfirmed;
    }
}
