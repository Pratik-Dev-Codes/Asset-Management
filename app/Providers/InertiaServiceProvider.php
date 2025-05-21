<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class InertiaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Inertia::version(fn () => md5_file(public_path('mix-manifest.json')));

        Inertia::share([
            'app' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
                'env' => config('app.env'),
                'debug' => config('app.debug'),
            ],
            'auth' => function () {
                return [
                    'user' => auth()->user() ? [
                        'id' => auth()->user()->id,
                        'name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                        'roles' => auth()->user()->getRoleNames(),
                        'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
                    ] : null,
                ];
            },
            'flash' => function () {
                return [
                    'success' => session('success'),
                    'error' => session('error'),
                    'warning' => session('warning'),
                    'info' => session('info'),
                ];
            },
        ]);
    }
}
