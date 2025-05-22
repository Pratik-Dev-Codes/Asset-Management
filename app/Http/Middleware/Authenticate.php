<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            $this->authenticate($request, $guards);
        } catch (AuthenticationException $e) {
            if ($this->shouldReturnJson($request, $guards)) {
                return $this->handleApiUnauthenticated($request, $e);
            }
            
            throw $e;
        }

        return $next($request);
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards)
    {
        if ($this->shouldReturnJson($request, $guards)) {
            return $this->handleApiUnauthenticated($request, new AuthenticationException(
                'Unauthenticated.', $guards, $this->redirectTo($request)
            ));
        }

        parent::unauthenticated($request, $guards);
    }

    /**
     * Handle an unauthenticated API request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiUnauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthenticated.',
            'error' => 'authentication_required',
        ], 401, [
            'WWW-Authenticate' => 'Bearer',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
    }

    /**
     * Determine if the request should return JSON.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return bool
     */
    protected function shouldReturnJson($request, array $guards)
    {
        return $request->expectsJson() || 
               $request->is('api/*') || 
               in_array('api', $guards) ||
               $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $this->shouldReturnJson($request, [])) {
            return route('login');
        }
    }
}
