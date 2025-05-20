<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\SystemSettingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\Api\MonitoringController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Public routes (no authentication required)
Route::get('/status', function () {
    return response()->json([
        'status' => 'operational',
        'version' => '1.0.0',
        'timestamp' => now()->toDateTimeString(),
    ]);
})->name('status');

// Authentication routes
Route::prefix('auth')->name('auth.')->group(function () {
    // Login with rate limiting
    Route::middleware('throttle:login')
        ->post('/login', [AuthController::class, 'login'])
        ->name('login');
        
    // Refresh token
    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->name('refresh');

    // Registration with rate limiting
    Route::middleware('throttle:api')
        ->post('/register', [AuthController::class, 'register'])
        ->name('register');

    // Password reset with rate limiting
    Route::middleware('throttle:password-reset')->group(function () {
        Route::post('/password/email', [AuthController::class, 'forgotPassword'])
            ->name('password.email');
        Route::post('/password/reset', [AuthController::class, 'resetPassword'])
            ->name('password.reset');
    });
});

// Protected routes with authentication
Route::middleware(['auth:api'])->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
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
        Route::resource('/', AssetController::class);
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
        'documentation' => url('/api/documentation')
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
        'DELETE /api/assets/{id}' => 'Delete an asset (requires auth)'
        ]
    ]);
})->name('api.docs');
