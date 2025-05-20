<?php

namespace App\Providers;

use App\Providers\AuthServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\SecurityServiceProvider;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(AuthServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(\Spatie\Permission\PermissionServiceProvider::class);

        // Register the QueryOptimizationServiceProvider
        if ($this->app->environment() !== 'production') {
            $this->app->register(\App\Providers\QueryOptimizationServiceProvider::class);
        }

        // Register the RepositoryServiceProvider
        $this->app->register(\App\Providers\RepositoryServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for MySQL
        Schema::defaultStringLength(191);

        // Configure rate limiting
        $this->configureRateLimiting();

        // Only register view composers if the view service is available
        if ($this->app->bound('view')) {
            // Register view composers for cached data
            $this->registerViewComposers();
        }

        // Security headers
        if (config('app.env') === 'production' && $this->app->bound('router')) {
            $router = $this->app->make('router');
            if (isset($router->getMiddlewareGroups()['web'])) {
                $router->middlewareGroup('web', [
                    \App\Http\Middleware\SecurityHeaders::class,
                    ...$router->getMiddlewareGroups()['web'],
                ]);
            }
        }
    }

    /**
     * Register view composers that use cached data.
     */
    protected function registerViewComposers(): void
    {
        // Cache duration in minutes
        $cacheDuration = config('cache.duration', 60);

        // Share cached data with all views
        View::composer('*', function ($view) use ($cacheDuration) {
            // Share user roles and permissions with all views
            if (auth()->check()) {
                $user = auth()->user();
                $view->with('userPermissions', $user->permission_names);
                $view->with('userRoles', $user->role_names);
            }

            // Cache navigation menu items
            $menuCacheKey = 'navigation.menu';
            $menuItems = Cache::remember($menuCacheKey, $cacheDuration * 24, function () {
                return $this->getNavigationMenu();
            });

            $view->with('cachedMenu', $menuItems);
        });
    }

    /**
     * Get the navigation menu items with permissions check.
     */
    protected function getNavigationMenu(): array
    {
        return [
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'home',
                'permission' => 'viewAny dashboard',
            ],
            [
                'title' => 'Assets',
                'route' => 'assets.index',
                'icon' => 'server',
                'permission' => 'viewAny assets',
            ],
            [
                'title' => 'Reports',
                'route' => 'reports.index',
                'icon' => 'file-text',
                'permission' => 'viewAny reports',
            ],
            // Add more menu items as needed
        ];
    }

    /**
     * Configure security settings.
     */
    protected function configureSecurity(): void
    {
        // Set secure session configuration
        Config::set('session.secure', $this->app->environment('production'));
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'lax');

        // Disable X-Powered-By header
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        // Set secure cookie settings
        Config::set('session.secure', $this->app->environment('production'));
        Config::set('session.same_site', 'lax');

        // Disable XSRF-TOKEN cookie in API responses
        if (request()->is('api/*')) {
            Config::set('session.driver', 'array');
        }
    }

    /**
     * Configure rate limiting for the application.
     */
    protected function configureRateLimiting(): void
    {
        // API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });

        // Login rate limiting
        RateLimiter::for('login', function (Request $request) {
            $maxAttempts = config('security.rate_limiting.login.max_attempts', 5);
            $decayMinutes = config('security.rate_limiting.login.decay_minutes', 15);

            return [
                Limit::perMinutes($decayMinutes, $maxAttempts)
                    ->by($request->input('email').'|'.$request->ip()),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });

        // Global rate limiting for all requests
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(1000)->by($request->ip());
        });

        // Password reset rate limiting
        RateLimiter::for('password-reset', function (Request $request) {
            return [
                Limit::perMinute(3)->by($request->input('email').$request->ip()),
            ];
        });

        // Exports rate limiting
        RateLimiter::for('exports', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(10)->by($request->user()->id)
                : Limit::perMinute(3)->by($request->ip());
        });

        // Cache clearing rate limiting
        RateLimiter::for('cache-clear', function (Request $request) {
            return [
                Limit::perMinute(1)->by($request->ip()),
            ];
        });
    }
}
