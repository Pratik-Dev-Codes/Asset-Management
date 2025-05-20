<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiVersion
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $version)
    {
        // Set the API version in the request for later use
        $request->merge(['api_version' => $version]);

        // Set the API version in the config
        config(['app.api_version' => $version]);

        return $next($request);
    }
}
