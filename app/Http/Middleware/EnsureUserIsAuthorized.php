<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAuthorized
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability, ...$models): Response
    {
        // Skip authorization for these methods
        $skipMethods = ['OPTIONS', 'HEAD'];
        if (in_array($request->method(), $skipMethods)) {
            return $next($request);
        }

        // Skip authorization for these routes
        $skipRoutes = [
            'login',
            'register',
            'password.request',
            'password.email',
            'password.reset',
            'verification.notice',
            'verification.verify',
            'verification.resend',
            'password.confirm',
            'password.update',
        ];

        if (in_array($request->route()?->getName(), $skipRoutes)) {
            return $next($request);
        }

        // Get the authenticated user
        $user = $request->user();
        
        // If no user is authenticated, return 401
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Check if the user has the required ability
        $authorized = false;
        
        foreach ($models as $model) {
            $modelInstance = $request->route($model);
            
            if ($modelInstance && $user->can($ability, $modelInstance)) {
                $authorized = true;
                break;
            }
        }

        // If no specific model authorization is required, check the general ability
        if (empty($models) && $user->can($ability)) {
            $authorized = true;
        }

        if (!$authorized) {
            return response()->json([
                'message' => 'This action is unauthorized.'
            ], 403);
        }

        return $next($request);
    }
}
