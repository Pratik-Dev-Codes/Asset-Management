<?php

namespace App\Providers;

use App\Console\Commands\Monitor\CheckDiskSpace;
use App\Console\Commands\Monitor\CheckQueueHealth;
use App\Console\Commands\Monitor\CheckScheduledTasks;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

class MonitoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register monitoring commands
        $this->commands([
            CheckDiskSpace::class,
            CheckScheduledTasks::class,
            CheckQueueHealth::class,
        ]);

        // Merge monitoring config
        $this->mergeConfigFrom(
            __DIR__.'/../../config/monitoring.php', 'monitoring'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../../config/monitoring.php' => config_path('monitoring.php'),
        ], 'monitoring-config');

        // Register monitoring routes
        $this->registerRoutes();

        // Schedule monitoring tasks
        $this->scheduleTasks();
    }

    /**
     * Register monitoring routes.
     */
    protected function registerRoutes(): void
    {
        if (! config('monitoring.health_checks.enabled')) {
            return;
        }

        Route::prefix('api/monitor')
            ->middleware(['api'])
            ->group(function () {
                Route::get('health', 'App\Http\Controllers\Api\HealthCheckController');

                if (config('monitoring.metrics.route.enabled')) {
                    Route::get('metrics', function () {
                        // TODO: Implement metrics endpoint
                        return response()->json(['status' => 'ok']);
                    })->middleware(config('monitoring.metrics.route.middleware', ['auth:api']));
                }
            });
    }

    /**
     * Schedule monitoring tasks.
     */
    protected function scheduleTasks(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Schedule disk space check
        Schedule::command('monitor:disk-space')
            ->hourly()
            ->environments(['production', 'staging']);

        // Schedule scheduled tasks health check
        Schedule::command('monitor:scheduled-tasks')
            ->everyFiveMinutes()
            ->environments(['production', 'staging']);

        // Schedule queue health check
        Schedule::command('monitor:queue-health')
            ->everyFiveMinutes()
            ->environments(['production', 'staging']);
    }
}
