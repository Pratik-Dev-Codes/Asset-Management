<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY') ?? $request->query('api_key');
        
        if ($apiKey !== config('app.api_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API Key',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
