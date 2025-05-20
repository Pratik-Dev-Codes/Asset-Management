<?php

use App\Http\Controllers\MemoryMonitorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Memory Monitor Routes
|--------------------------------------------------------------------------
|
| These routes are used by the memory monitoring dashboard.
|
*/

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Memory status API endpoint
    Route::get('/api/memory/status', [MemoryMonitorController::class, 'index'])
        ->name('memory.status')
        ->middleware('can:view,memory-monitor');

    // Memory monitor dashboard
    Route::get('/memory-monitor', function () {
        return view('memory-monitor.index');
    })->name('memory.monitor')
        ->middleware('can:view,memory-monitor');
});
