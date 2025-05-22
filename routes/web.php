<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\API\ReportApiController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use OpenApi\Generator;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->middleware('web');

// Favicon route
Route::get('/favicon.ico', function () {
    return response()->file(public_path('favicon.ico'));
})->name('favicon');

// Include memory routes
require __DIR__.'/memory.php';

// Test route to verify session and authentication
Route::get('/test-auth', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user' => auth()->user(),
        'session_id' => session()->getId(),
    ]);
})->middleware('web');

// Dynamic JavaScript asset
Route::get('/js/laravel-data.js', [\App\Http\Controllers\JsAssetController::class, 'laravelData'])
    ->name('laravel.data.js');

// Dashboard Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Theme Toggle
    Route::post('/theme/toggle', [ThemeController::class, 'toggle'])->name('theme.toggle');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Asset Management
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::resource('/', AssetController::class);
        Route::get('{asset}/history', [AssetController::class, 'history'])->name('history');
        Route::post('{asset}/checkout', [AssetController::class, 'checkout'])->name('checkout');
        Route::post('{asset}/checkin', [AssetController::class, 'checkin'])->name('checkin');
        Route::post('upload', [AssetController::class, 'upload'])->name('upload');
        Route::post('delete-upload', [AssetController::class, 'deleteUpload'])->name('delete-upload');
    });

    // Maintenance
    Route::resource('maintenance', MaintenanceController::class);
    Route::get('maintenance/scheduled', [MaintenanceController::class, 'scheduled'])->name('maintenance.scheduled');
    Route::get('maintenance/history', [MaintenanceController::class, 'history'])->name('maintenance.history');
    Route::get('maintenance/checklists', [MaintenanceController::class, 'checklists'])->name('maintenance.checklists');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/assets', [ReportController::class, 'assets'])->name('assets');
        Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
        Route::get('/compliance', [ReportController::class, 'compliance'])->name('compliance');
        Route::get('/custom', [ReportController::class, 'custom'])->name('custom');
        Route::post('/custom', [ReportController::class, 'generateCustom'])->name('generate-custom');
        Route::get('/export/{id}/{format?}', [ReportController::class, 'export'])->name('export');

        // API Endpoints for Reports (moved to routes/api.php)
        // Route::prefix('api')->name('api.')->group(function () {
        //     Route::get('/assets', [ReportApiController::class, 'assets'])->name('assets');
        //     Route::get('/financial', [ReportApiController::class, 'financial'])->name('financial');
        //     Route::get('/compliance', [ReportApiController::class, 'compliance'])->name('compliance');
        //     Route::get('/export/{format}', [ReportApiController::class, 'export'])->name('export');
        // });
    });

    // Admin Routes
    Route::prefix('admin')->name('admin.')->middleware(['role:admin'])->group(function () {
        // Users
        Route::resource('users', UserController::class);
        Route::post('users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk-action');

        // User Export Routes
        Route::prefix('users')->group(function () {
            // User Profile Routes
            Route::get('{user}/profile', [UserController::class, 'show'])->name('users.profile');
            Route::get('{user}/activity', [UserController::class, 'activity'])->name('users.activity');
            Route::get('{user}/assets', [UserController::class, 'assets'])->name('users.assets');

            // Export Routes
            Route::get('{user}/export/pdf', [UserController::class, 'exportPdf'])->name('users.export.pdf');
            Route::get('export/csv', [UserController::class, 'exportCsv'])->name('users.export.csv');
            Route::get('{user}/export/activity/csv', [UserController::class, 'exportActivityCsv'])->name('users.export.activity.csv');
            Route::get('{user}/export/assets/csv', [UserController::class, 'exportAssetsCsv'])->name('users.export.assets.csv');
        });

        // Role & Permission Management
        Route::resource('roles', RoleController::class);
        Route::resource('permissions', PermissionController::class);

        // System Settings
        Route::resource('locations', LocationController::class);
        Route::resource('departments', DepartmentController::class);
    });

    // Asset Categories
    Route::resource('asset-categories', \App\Http\Controllers\AssetCategoryController::class)->except(['show']);
});

// Authentication Routes
require __DIR__.'/auth.php';

// Home Route
Route::get('/', function () {
    return redirect()->route('dashboard');
});
