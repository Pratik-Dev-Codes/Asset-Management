<?php

namespace App\Http\Middleware;

use App\Traits\MonitorsMemoryUsage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonitorMemoryUsage
{
    use MonitorsMemoryUsage;

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (! config('app.debug', false) && ! config('monitor.memory.enabled', true)) {
            return $next($request);
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;

        $memoryLimit = $this->getMemoryLimitInBytes();
        $memoryPercent = ($peakMemory / $memoryLimit) * 100;

        $threshold = config('monitor.memory.threshold', 80);

        $logData = [
            'path' => $request->path(),
            'method' => $request->method(),
            'execution_time' => round($executionTime, 4).'s',
            'memory_used' => $this->formatBytes($memoryUsed),
            'peak_memory' => $this->formatBytes($peakMemory),
            'memory_limit' => $this->formatBytes($memoryLimit),
            'memory_percent' => round($memoryPercent, 2).'%',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        if ($memoryPercent > $threshold) {
            Log::warning('High memory usage detected', $logData);
        } elseif (config('monitor.memory.log_all', false)) {
            Log::info('Memory usage', $logData);
        }

        return $response;
    }
}
