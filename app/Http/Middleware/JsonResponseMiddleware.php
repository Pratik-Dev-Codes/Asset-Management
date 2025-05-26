<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // If the response is already a JSON response, return it as is
        if ($response instanceof JsonResponse) {
            return $response;
        }

        // If the response is a redirect, return it as is
        if ($response->isRedirection()) {
            return $response;
        }

        // If the response is already in the correct format, return it
        $content = $response->getContent();
        if (is_array($content) || is_object($content)) {
            return $response;
        }

        // Convert the response to JSON format
        $data = [
            'success' => $response->isSuccessful(),
            'data' => $content,
            'status' => $response->status(),
        ];

        return response()->json($data, $response->status());
    }
}
