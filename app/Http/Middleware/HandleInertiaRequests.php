<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  callable  $next
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, $next)
    {
        // Set the app name in the session if not already set
        if (!session()->has('app_name')) {
            session(['app_name' => config('app.name')]);
        }

        return parent::handle($request, $next);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Get the authenticated user with roles and permissions
        $user = $request->user();
        $userData = null;
        
        if ($user) {
            $userData = array_merge(
                $user->toArray(),
                [
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                ]
            );
        }

        return array_merge(parent::share($request), [
            // Application data
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'url' => config('app.url'),
                'timezone' => config('app.timezone'),
                'locale' => app()->getLocale(),
                'fallback_locale' => config('app.fallback_locale'),
                'debug' => config('app.debug'),
            ],
            
            // Authentication data
            'auth' => [
                'user' => $userData,
                'check' => (bool) $user,
            ],
            
            // Flash messages
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            
            // CSRF token
            'csrf_token' => csrf_token(),
            
            // Ziggy routes
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                    'query' => $request->query(),
                ]);
            },
            
            // Application version (useful for cache busting)
            'version' => config('app.version', '1.0.0'),
        ]);
    }
}
