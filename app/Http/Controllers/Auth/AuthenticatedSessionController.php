<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'canRegister' => Route::has('register'),
            'intended' => url()->previous() === url()->current() ? null : url()->previous(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(LoginRequest $request)
    {
        // Authenticate the user
        $request->authenticate();

        // Regenerate the session
        $request->session()->regenerate();

        // If the request is an API request, return a token
        if ($request->wantsJson() || $request->is('api/*')) {
            $user = User::where('email', $request->email)->firstOrFail();
            
            // Revoke existing tokens if needed
            // $user->tokens()->delete();
            
            // Create a new token
            $token = $user->createToken('auth-token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load('roles', 'permissions'),
            ]);
        }

        // For web requests, redirect to the intended URL
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();
        
        // If it's an API request, revoke the current token
        if ($request->wantsJson() || $request->is('api/*')) {
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);
        }
        
        // For web requests, log the user out and invalidate the session
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
    
    /**
     * Get the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }
        
        return response()->json([
            'success' => true,
            'user' => $user->load('roles', 'permissions'),
        ]);
    }
}
