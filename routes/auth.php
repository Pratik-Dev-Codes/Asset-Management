<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle the web authentication flow for the application.
| They are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

// Guest routes (only accessible when not authenticated)
Route::middleware('guest')->group(function () {
    // Registration routes
    if (config('auth.registration_enabled', true)) {
        Route::get('register', [RegisteredUserController::class, 'create'])
            ->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);
    }

    // Login routes
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.attempt');

    // Password reset routes
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Email verification routes
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Resend verification email
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
        
    // API endpoint to resend verification email
    Route::post('email/resend-verification', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.resend');

    // Password confirmation routes
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Password update route
    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');

    // Logout route
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
        
    // Get current user
    Route::get('auth/me', [AuthenticatedSessionController::class, 'me'])
        ->name('auth.me');
});

/*
|--------------------------------------------------------------------------
| API Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle the API authentication flow for the application.
| They are loaded by the RouteServiceProvider within a group which
| contains the "api" middleware group.
|
*/

// API authentication routes
Route::prefix('api')->middleware('api')->group(function () {
    // Authentication routes
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('api.login');
        
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:sanctum')
        ->name('api.logout');
        
    // Get current user
    Route::get('/me', [AuthenticatedSessionController::class, 'me'])
        ->middleware('auth:sanctum')
        ->name('api.me');
    
    // Refresh token
    Route::post('/refresh', [AuthenticatedSessionController::class, 'refresh'])
        ->middleware('auth:sanctum')
        ->name('api.refresh');
});
