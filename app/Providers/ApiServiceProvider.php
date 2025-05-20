<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();
        $this->mapApiRoutes();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register API specific services
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        $rateLimiting = config('api.rate_limiting', [
            'enabled' => true,
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ]);

        if ($rateLimiting['enabled']) {
            Route::middleware('api')
                ->prefix('api')
                ->group(function () use ($rateLimiting) {
                    Route::middleware(
                        'throttle:api,' . $rateLimiting['max_attempts'] . ',' . $rateLimiting['decay_minutes']
                    )->group(function () {
                        require base_path('routes/api.php');
                    });
                });
        }
    }

    /**
     * Define the API routes.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->app->getNamespace().'Http\Controllers\Api\V1')
            ->group(base_path('routes/api.php'));
    }
}
