<?php

declare(strict_types=1);

use App\Http\Controllers\API\AssetController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
|
<<<<<<< HEAD
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// API Versioning
$apiVersion = 'v1';

// API Status and Version
Route::get('/status', function () use ($apiVersion) {
    return response()->json([
        'status' => 'operational',
        'name' => 'Asset Management API',
        'version' => $apiVersion,
        'timestamp' => now()->toIso8601String(),
        'environment' => config('app.env'),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
    ]);
})->name('api.status');

// API Routes Group with Versioning
Route::prefix($apiVersion)
    ->name("api.{$apiVersion}.")
    ->group(function () {
        // Public Routes
        Route::prefix('auth')->name('auth.')->group(function () {
=======
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
>>>>>>> main
            // Authentication
            Route::post('login', [AuthController::class, 'login'])->name('login');
            Route::post('register', [AuthController::class, 'register'])->name('register');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
            
            // Password Reset
            Route::post('password/email', [AuthController::class, 'forgotPassword'])
                ->name('password.email');
            Route::post('password/reset', [AuthController::class, 'resetPassword'])
                ->name('password.reset');
        });

        // Protected Routes (Require Authentication)
        Route::middleware(['auth:api'])->group(function () {
            // Authentication
            Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

            // User Profile
            Route::prefix('profile')->name('profile.')->group(function () {
                Route::get('/', [UserProfileController::class, 'show'])->name('show');
                Route::put('/', [UserProfileController::class, 'update'])->name('update');
                Route::post('/password', [UserProfileController::class, 'updatePassword'])
                    ->name('password.update');
            });

            // Admin Only Routes
            Route::middleware(['role:admin'])->group(function () {
                // Users Management
                Route::apiResource('users', UserController::class)
                    ->except(['create', 'edit']);
                
                Route::prefix('users/{user}')->name('users.')->group(function () {
                    Route::put('status', [UserController::class, 'updateStatus'])
                        ->name('status.update');
                    Route::post('reset-password', [UserController::class, 'resetPassword'])
                        ->name('password.reset');
                });

                // Assets Management
                Route::apiResource('assets', AssetController::class)
                    ->except(['create', 'edit']);
                
                Route::prefix('assets/{asset}')->name('assets.')->group(function () {
                    Route::post('checkout', [AssetController::class, 'checkout'])
                        ->name('checkout');
                    Route::post('checkin', [AssetController::class, 'checkin'])
                        ->name('checkin');
                    Route::get('history', [AssetController::class, 'history'])
                        ->name('history');
                });
            });

            // Dashboard
            Route::prefix('dashboard')->name('dashboard.')->group(function () {
                Route::get('/', [DashboardController::class, 'index'])
                    ->name('index');
                Route::get('statistics', [DashboardController::class, 'statistics'])
                    ->name('statistics');
            });
        });
    });

// API Documentation
Route::get('documentation', function () use ($apiVersion) {
    $baseUrl = config('app.url') . '/api/' . $apiVersion;
    
    return response()->json([
        'name' => 'Asset Management API',
        'version' => $apiVersion,
        'documentation' => [
            'authentication' => [
                'login' => [
                    'method' => 'POST',
                    'endpoint' => "{$baseUrl}/auth/login",
                    'description' => 'Authenticate user and retrieve access token',
                    'parameters' => [
                        'email' => 'string|required|email',
                        'password' => 'string|required',
                    ]
                ],
                'register' => [
                    'method' => 'POST',
                    'endpoint' => "{$baseUrl}/auth/register",
                    'description' => 'Register a new user account',
                    'parameters' => [
                        'name' => 'string|required|max:255',
                        'email' => 'string|required|email|unique:users',
                        'password' => 'string|required|min:8|confirmed',
                    ]
                ],
                'refresh' => [
                    'method' => 'POST',
                    'endpoint' => "{$baseUrl}/auth/refresh",
                    'description' => 'Refresh the authentication token',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'logout' => [
                    'method' => 'POST',
                    'endpoint' => "{$baseUrl}/auth/logout",
                    'description' => 'Invalidate the authentication token',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'me' => [
                    'method' => 'GET',
                    'endpoint' => "{$baseUrl}/auth/me",
                    'description' => 'Get the authenticated user',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ]
            ],
            'profile' => [
                'show' => [
                    'method' => 'GET',
                    'endpoint' => "{$baseUrl}/profile",
                    'description' => 'Get the authenticated user profile',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'update' => [
                    'method' => 'PUT',
                    'endpoint' => "{$baseUrl}/profile",
                    'description' => 'Update the authenticated user profile',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'update_password' => [
                    'method' => 'POST',
                    'endpoint' => "{$baseUrl}/profile/password",
                    'description' => 'Update the authenticated user password',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ],
                    'parameters' => [
                        'current_password' => 'string|required',
                        'new_password' => 'string|required|min:8|confirmed',
                    ]
                ]
            ],
            'users' => [
                'index' => [
                    'method' => 'GET',
                    'endpoint' => "{$baseUrl}/users",
                    'description' => 'Get all users (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'store' => [
                    'method' => 'POST',
                    'endpoint' => "{$baseUrl}/users",
                    'description' => 'Create a new user (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}',
                        'Content-Type' => 'application/json'
                    ],
                    'parameters' => [
                        'name' => 'string|required|max:255',
                        'email' => 'string|required|email|unique:users',
                        'password' => 'string|required|min:8|confirmed',
                        'role' => 'string|in:admin,user'
                    ]
                ],
                'show' => [
                    'method' => 'GET',
                    'endpoint' => "{$baseUrl}/users/{user}",
                    'description' => 'Get a specific user (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'update' => [
                    'method' => 'PUT',
                    'endpoint' => "{$baseUrl}/users/{user}",
                    'description' => 'Update a user (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}',
                        'Content-Type' => 'application/json'
                    ]
                ],
                'destroy' => [
                    'method' => 'DELETE',
                    'endpoint' => "{$baseUrl}/users/{user}",
                    'description' => 'Delete a user (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'update_status' => [
                    'method' => 'PUT',
                    'endpoint' => "{$baseUrl}/users/{user}/status",
                    'description' => 'Update user status (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}',
                        'Content-Type' => 'application/json'
                    ],
                    'parameters' => [
                        'status' => 'required|in:active,inactive,suspended'
                    ]
                ],
                'reset_password' => [
                    'method' => 'POST',
                    'endpoint' => "{$baseUrl}/users/{user}/reset-password",
                    'description' => 'Reset user password (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}',
                        'Content-Type' => 'application/json'
                    ],
                    'parameters' => [
                        'password' => 'required|string|min:8|confirmed'
                    ]
                ]
            ],
            'assets' => [
                'index' => [
                    'method' => 'GET',
                    'endpoint' => "{$baseUrl}/assets",
                    'description' => 'Get all assets (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'store' => [
                    'method' => 'POST',
                    'endpoint' => "{$baseUrl}/assets",
                    'description' => 'Create a new asset (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}',
                        'Content-Type' => 'application/json'
                    ]
                ],
                'show' => [
                    'method' => 'GET',
                    'endpoint' => "{$baseUrl}/assets/{asset}",
                    'description' => 'Get a specific asset (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'update' => [
                    'method' => 'PUT',
                    'endpoint' => "{$baseUrl}/assets/{asset}",
                    'description' => 'Update an asset (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}',
                        'Content-Type' => 'application/json'
                    ]
                ],
                'destroy' => [
                    'method' => 'DELETE',
                    'endpoint' => "{$baseUrl}/assets/{asset}",
                    'description' => 'Delete an asset (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'checkout' => [
                    'method' => 'POST',
                    'endpoint' => "{$baseUrl}/assets/{asset}/checkout",
                    'description' => 'Checkout an asset to a user (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}',
                        'Content-Type' => 'application/json'
                    ]
                ],
                'checkin' => [
                    'method' => 'POST',
                    'endpoint' => "{$baseUrl}/assets/{asset}/checkin",
                    'description' => 'Checkin an asset (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'history' => [
                    'method' => 'GET',
                    'endpoint' => "{$baseUrl}/assets/{asset}/history",
                    'description' => 'Get asset history (Admin only)',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ]
            ],
            'dashboard' => [
                'index' => [
                    'method' => 'GET',
                    'endpoint' => "{$baseUrl}/dashboard",
                    'description' => 'Get dashboard overview',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ],
                'statistics' => [
                    'method' => 'GET',
                    'endpoint' => "{$baseUrl}/dashboard/statistics",
                    'description' => 'Get dashboard statistics',
                    'headers' => [
                        'Authorization' => 'Bearer {token}'
                    ]
                ]
            ]
        ],
        'authentication' => [
            'type' => 'bearer',
            'header' => 'Authorization: Bearer {token}'
        ],
        'rate_limiting' => [
            'max_attempts' => 60,
            'decay_minutes' => 1
        ],
        'response_format' => [
            'success' => 'boolean',
            'message' => 'string',
            'data' => 'mixed',
            'errors' => 'array|null',
            'meta' => 'object|null',
            'links' => 'object|null'
        ],
        'error_codes' => [
            '400' => 'Bad Request',
            '401' => 'Unauthenticated',
            '403' => 'Forbidden',
            '404' => 'Not Found',
            '405' => 'Method Not Allowed',
            '422' => 'Validation Error',
            '429' => 'Too Many Requests',
            '500' => 'Internal Server Error',
            '503' => 'Service Unavailable'
        ]
    ]);
})->name('api.documentation');

// 404 Handler for Undefined Routes
Route::fallback(function () use ($apiVersion) {
    return response()->json([
        'success' => false,
        'message' => 'Endpoint not found. The requested resource could not be found.',
        'documentation' => url("/api/{$apiVersion}/documentation"),
        'status_code' => 404
    ], 404);
})->name('api.fallback');
