<?php

declare(strict_types=1);

use App\Http\Controllers\API\AssetController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
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
