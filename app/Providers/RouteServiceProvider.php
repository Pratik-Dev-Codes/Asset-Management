<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * This is used by Laravel's authentication to redirect users after login.
     * For API-only applications, we don't need a home route.
     *
     * @var string
     */
    public const HOME = '/api';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Configure rate limiting for API
        $this->configureRateLimiting();

        $this->routes(function () {
<<<<<<< HEAD
            // API Routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        });

        // Set the API as the default guard
        config(['auth.defaults.guard' => 'api']);
=======
            // API Routes (versioned)
            Route::middleware(['api', 'json.response'])
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Web routes - only for API documentation and health checks
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
        
        // Remove any default web middleware
        $this->removeWebMiddleware();
    }
    
    /**
     * Remove web middleware group from the application
     */
    protected function removeWebMiddleware(): void
    {
        $this->app['router']->middlewareGroup('web', []);
>>>>>>> main
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        // Global API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // Authentication rate limiting
        RateLimiter::for('auth', function (Request $request) {
            $key = $request->input('email') ? 
                Str::transliterate(Str::lower($request->email).'|'.$request->ip()) : 
                $request->ip();
                
            return [
                Limit::perMinute(5)->by($key),
                Limit::perHour(20)->by($key),
            ];
        });

        // Public API endpoints rate limiting
        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Protected API endpoints rate limiting
        RateLimiter::for('protected', function (Request $request) {
            return Limit::perMinute(120)->by(optional($request->user())->id ?: $request->ip());
        });
        
        // File uploads rate limiting
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by(optional($request->user())->id ?: $request->ip());
        });
        
        // Password reset rate limiting
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });
    }
}
