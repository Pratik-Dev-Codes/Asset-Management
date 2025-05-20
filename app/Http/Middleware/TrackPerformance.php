<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackPerformance
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! config('monitoring.performance.enabled')) {
            return $next($request);
        }

        // Start timing
        $start = microtime(true);

        // Process the request
        $response = $next($request);

        // Calculate execution time
        $executionTime = microtime(true) - $start;

        // Log slow requests
        $this->logRequest($request, $executionTime, $response->getStatusCode());

        // Add server timing header
        if (method_exists($response, 'header')) {
            $response->header('Server-Timing', 'total;dur='.($executionTime * 1000));
        }

        return $response;
    }

    /**
     * Log request details if it exceeds the threshold.
     */
    protected function logRequest(Request $request, float $executionTime, int $statusCode): void
    {
        $slowThreshold = config('monitoring.performance.slow_threshold', 1.0); // seconds

        if ($executionTime >= $slowThreshold) {
            Log::warning('Slow request detected', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'execution_time' => round($executionTime, 4).'s',
                'status_code' => $statusCode,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => optional($request->user())->id,
            ]);
        }
    }
}
