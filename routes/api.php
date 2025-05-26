<?php

use App\Http\Controllers\API\AssetController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\MonitoringController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ReportApiController as ReportController;
use App\Http\Controllers\API\SystemSettingController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserProfileController;
use App\Http\Controllers\FileUploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
|
| This is the main entry point for all API requests.
| All routes are loaded by the RouteServiceProvider and are assigned
| to the "api" middleware group. The routes are versioned (v1) for
| better API maintenance and backward compatibility.
|
*/

// API Information Endpoint
Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'version' => '1.0.0',
        'status' => 'operational',
        'environment' => config('app.env'),
        'documentation' => url('/api/documentation'),
        'timestamp' => now()->toIso8601String(),
        'timezone' => config('app.timezone'),
    ]);
})->name('api.info');

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Version endpoint
Route::get('/version', function () {
    return response()->json([
        'version' => '1.0.0',
        'laravel' => app()->version(),
        'php' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
| These routes are accessible without authentication.
*/

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.password.forgot');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.password.reset');
});

// Public assets
Route::get('/assets/public/{asset}', [AssetController::class, 'showPublic'])->name('assets.public.show');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
| These routes require authentication.
*/
Route::middleware(['auth:api'])->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/auth/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::put('/auth/profile', [AuthController::class, 'updateProfile'])->name('auth.profile.update');
    Route::put('/auth/password', [AuthController::class, 'changePassword'])->name('auth.password.change');

    // User routes
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/avatar', [UserController::class, 'uploadAvatar'])->name('users.avatar.upload');
    Route::delete('users/{user}/avatar', [UserController::class, 'deleteAvatar'])->name('users.avatar.delete');

    // Asset routes
    Route::apiResource('assets', AssetController::class);
    Route::get('assets/statistics', [AssetController::class, 'statistics'])->name('assets.statistics');
    Route::post('assets/{asset}/checkout', [AssetController::class, 'checkout'])->name('assets.checkout');
    Route::post('assets/{asset}/checkin', [AssetController::class, 'checkin'])->name('assets.checkin');
    Route::post('assets/{asset}/maintenance', [AssetController::class, 'requestMaintenance'])->name('assets.maintenance.request');
    Route::get('assets/{asset}/history', [AssetController::class, 'history'])->name('assets.history');

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('assets', [ReportController::class, 'assets'])->name('reports.assets');
        Route::get('maintenance', [ReportController::class, 'maintenance'])->name('reports.maintenance');
        Route::get('depreciation', [ReportController::class, 'depreciation'])->name('reports.depreciation');
        Route::get('audit', [ReportController::class, 'audit'])->name('reports.audit');
    });

    // System settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SystemSettingController::class, 'index'])->name('settings.index');
        Route::put('/', [SystemSettingController::class, 'update'])->name('settings.update');
        Route::get('/backup', [SystemSettingController::class, 'backup'])->name('settings.backup');
        Route::post('/restore', [SystemSettingController::class, 'restore'])->name('settings.restore');
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
        Route::put('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/activity', [DashboardController::class, 'activity'])->name('dashboard.activity');

    // Monitoring
    Route::prefix('monitoring')->group(function () {
        Route::get('status', [MonitoringController::class, 'status'])->name('monitoring.status');
        Route::get('queue', [MonitoringController::class, 'queue'])->name('monitoring.queue');
        Route::get('storage', [MonitoringController::class, 'storage'])->name('monitoring.storage');
    });

    // File uploads
    Route::post('/upload', [FileUploadController::class, 'upload'])->name('upload');
    Route::delete('/upload/{file}', [FileUploadController::class, 'delete'])->name('upload.delete');
});

// Fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'message' => 'Endpoint not found.',
        'documentation' => url('/api/documentation')
    ], 404);
});
Route::prefix('auth')
    ->name('auth.')
    ->middleware(['throttle:10,1']) // 10 requests per minute
    ->group(function () {
        // User login
        Route::post('login', [AuthController::class, 'login'])
            ->name('login');

        // Refresh access token
        Route::post('refresh', [AuthController::class, 'refresh'])
            ->name('refresh');

        // User registration
        Route::post('register', [AuthController::class, 'register'])
            ->name('register');

        // Password reset
        Route::post('password/email', [AuthController::class, 'sendResetLinkEmail'])
            ->name('password.email');
            
        Route::post('password/reset', [AuthController::class, 'reset'])
            ->name('password.reset');
    });

// Public assets (if any)
Route::get('public/assets/{filename}', [FileUploadController::class, 'show'])
    ->name('public.asset');

// Email verification
Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
    ->middleware(['throttle:6,1'])
    ->name('verification.send');

// Password reset with rate limiting
Route::middleware('throttle:password-reset')->group(function () {
    Route::post('/password/email', [AuthController::class, 'forgotPassword'])
        ->name('password.email');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])
        ->name('password.reset');
});

// Email verification link
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Authentication)
|--------------------------------------------------------------------------
| These routes require a valid authentication token.
*/
/*
|--------------------------------------------------------------------------
| Protected Routes (Authentication Required)
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::post('/auth/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

    // File attachments
    Route::delete('/attachments/{attachment}', [FileUploadController::class, 'destroy'])->name('attachments.destroy');

    // User profile routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [UserProfileController::class, 'show'])->name('show');
        Route::put('/', [UserProfileController::class, 'update'])->name('update');
        Route::post('/password', [UserProfileController::class, 'updatePassword'])->name('password.update');
        Route::post('/avatar', [UserProfileController::class, 'updateProfilePicture'])->name('avatar.update');
        Route::delete('/avatar', [UserProfileController::class, 'removeProfilePicture'])->name('avatar.destroy');
    });

    // Users management routes (admin only)
    Route::middleware(['role:admin'])->prefix('users')->name('users.')->group(function () {
        Route::resource('/', UserController::class);
        Route::put('/{user}/status', [UserController::class, 'updateStatus'])->name('status.update');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('password.reset');
    });

    // System settings routes (admin only)
    Route::middleware(['role:admin'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SystemSettingController::class, 'index'])->name('index');
        Route::get('/{group}', [SystemSettingController::class, 'getByGroup'])->name('group');
        Route::get('/key/{key}', [SystemSettingController::class, 'show'])->name('show');
        Route::put('/', [SystemSettingController::class, 'update'])->name('update');
        Route::get('/system/info', [SystemSettingController::class, 'systemInfo'])->name('system.info');
    });

    // Dashboard API
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/statistics', [DashboardController::class, 'statistics'])->name('statistics');
    });

    // Notification routes
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/', [NotificationController::class, 'clearAll'])->name('clear-all');
    });

    // Asset file attachments
    Route::prefix('assets/{asset}/attachments')->name('assets.attachments.')->group(function () {
        Route::get('/', [FileUploadController::class, 'index'])->name('index');
        Route::post('/', [FileUploadController::class, 'store'])->name('store');

        // Chunked upload endpoints
        Route::prefix('chunked')->name('chunked.')->group(function () {
            Route::post('/init', [FileUploadController::class, 'initiateChunkedUpload'])->name('init');
            Route::post('/{uploadId}', [FileUploadController::class, 'uploadChunk'])->name('upload');
            Route::post('/{uploadId}/complete', [FileUploadController::class, 'completeChunkedUpload'])->name('complete');
            Route::get('/{uploadId}/progress', [FileUploadController::class, 'getUploadProgress'])->name('progress');
        });
    });

    // Asset routes
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/statistics', [AssetController::class, 'statistics'])->name('statistics');
        
        // Explicitly define resource routes
        Route::get('', [AssetController::class, 'index'])->name('index');
        Route::post('', [AssetController::class, 'store'])->name('store');
        Route::get('{asset}', [AssetController::class, 'show'])->name('show')->where('asset', '[0-9]+');
        Route::put('{asset}', [AssetController::class, 'update'])->name('update')->where('asset', '[0-9]+');
        Route::delete('{asset}', [AssetController::class, 'destroy'])->name('destroy')->where('asset', '[0-9]+');
        
        // Additional routes
        Route::post('/{asset}/checkout', [AssetController::class, 'checkout'])->name('checkout')->where('asset', '[0-9]+');
        Route::post('/{asset}/checkin', [AssetController::class, 'checkin'])->name('checkin')->where('asset', '[0-9]+');

        // Bulk operations
        Route::prefix('bulk')->name('bulk.')->group(function () {
            Route::post('/update', [AssetController::class, 'bulkUpdate'])->name('update');
            Route::post('/delete', [AssetController::class, 'bulkDelete'])->name('delete');
            Route::post('/status', [AssetController::class, 'bulkStatusUpdate'])->name('status');
            Route::get('/batch/{batchId}', [AssetController::class, 'batchStatus'])->name('batch-status');
        });

        // Image handling
        Route::post('/{asset}/upload-image', [AssetController::class, 'uploadImage'])->name('upload-image')->where('asset', '[0-9]+');

        // Import/Export
        Route::get('/export/{type?}', [AssetController::class, 'export'])->name('export');
        Route::post('/import', [AssetController::class, 'import'])->name('import');
    });
});

// Fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json([
        'message' => 'Not Found',
        'documentation' => url('/api/documentation'),
    ], 404);
});

// API Documentation
Route::get('/documentation', function () {
    return response()->json([
        'message' => 'Asset Management API Documentation',
        'version' => '1.0.0',
        'endpoints' => [
            // Authentication
            'GET /api/status' => 'Check API status',
            'POST /api/auth/login' => 'Authenticate user',
            'POST /api/auth/register' => 'Register new user',
            'POST /api/auth/refresh' => 'Refresh authentication token',
            'POST /api/auth/password/email' => 'Request password reset',
            'POST /api/auth/password/reset' => 'Reset password',
            'POST /api/auth/logout' => 'Logout user (requires auth)',
            'GET /api/auth/me' => 'Get current user (requires auth)',

            // User Profile
            'GET /api/profile' => 'Get user profile (requires auth)',
            'PUT /api/profile' => 'Update user profile (requires auth)',
            'POST /api/profile/password' => 'Update password (requires auth)',
            'POST /api/profile/avatar' => 'Upload profile picture (requires auth)',
            'DELETE /api/profile/avatar' => 'Remove profile picture (requires auth)',

            // Users Management (Admin only)
            'GET /api/users' => 'List all users (requires admin)',
            'POST /api/users' => 'Create new user (requires admin)',
            'GET /api/users/{id}' => 'Get user by ID (requires admin)',
            'PUT /api/users/{id}' => 'Update user (requires admin)',
            'DELETE /api/users/{id}' => 'Delete user (requires admin)',
            'PUT /api/users/{id}/status' => 'Update user status (requires admin)',
            'POST /api/users/{id}/reset-password' => 'Reset user password (requires admin)',

            // System Settings (Admin only)
            'GET /api/settings' => 'Get all settings (requires admin)',
            'GET /api/settings/{group}' => 'Get settings by group (requires admin)',
            'GET /api/settings/key/{key}' => 'Get setting by key (requires admin)',
            'PUT /api/settings' => 'Update settings (requires admin)',
            'GET /api/settings/system/info' => 'Get system information (requires admin)',

            // Assets
            'GET /api/assets' => 'List all assets (requires auth)',
            'POST /api/assets' => 'Create a new asset (requires auth)',
            'GET /api/assets/{id}' => 'Get a specific asset (requires auth)',
            'PUT /api/assets/{id}' => 'Update an asset (requires auth)',
            'DELETE /api/assets/{id}' => 'Delete an asset (requires auth)',
        ],
    ]);
})->name('api.docs');
