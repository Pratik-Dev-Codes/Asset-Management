<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthCheckController extends Controller
{
    /**
     * Health check endpoint
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $checks = [
            'application' => $this->checkApplication(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        $isHealthy = !in_array(false, array_column($checks, 'healthy'), true);
        $status = $isHealthy ? 200 : 503;

        return response()->json([
            'status' => $isHealthy ? 'ok' : 'error',
            'timestamp' => now()->toDateTimeString(),
            'checks' => $checks,
        ], $status);
    }

    /**
     * Check application status
     *
     * @return array
     */
    protected function checkApplication(): array
    {
        return [
            'name' => config('app.name'),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
            'url' => config('app.url'),
            'timezone' => config('app.timezone'),
            'version' => config('app.version', '1.0.0'),
            'healthy' => true,
        ];
    }

    /**
     * Check database connection
     *
     * @return array
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            
            return [
                'connection' => config('database.default'),
                'status' => 'connected',
                'healthy' => true,
            ];
        } catch (\Exception $e) {
            return [
                'connection' => config('database.default'),
                'status' => 'disconnected',
                'error' => $e->getMessage(),
                'healthy' => false,
            ];
        }
    }

    /**
     * Check cache status
     *
     * @return array
     */
    protected function checkCache(): array
    {
        try {
            $key = 'health:check:' . time();
            $value = 'test';
            
            Cache::put($key, $value, now()->addMinute());
            $cached = Cache::get($key);
            
            return [
                'driver' => config('cache.default'),
                'status' => $cached === $value ? 'working' : 'failed',
                'healthy' => $cached === $value,
            ];
        } catch (\Exception $e) {
            return [
                'driver' => config('cache.default'),
                'status' => 'error',
                'error' => $e->getMessage(),
                'healthy' => false,
            ];
        }
    }

    /**
     * Check storage status
     *
     * @return array
     */
    protected function checkStorage(): array
    {
        try {
            $disk = config('filesystems.default');
            $path = 'health-check-' . time() . '.txt';
            $content = 'test';
            
            Storage::put($path, $content);
            $stored = Storage::get($path);
            Storage::delete($path);
            
            $totalSpace = disk_total_space(storage_path());
            $freeSpace = disk_free_space(storage_path());
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercentage = $totalSpace > 0 ? round(($usedSpace / $totalSpace) * 100, 2) : 0;
            
            return [
                'driver' => $disk,
                'status' => $stored === $content ? 'writable' : 'readonly',
                'total_space' => $this->formatBytes($totalSpace),
                'free_space' => $this->formatBytes($freeSpace),
                'used_space' => $this->formatBytes($usedSpace),
                'usage_percentage' => $usagePercentage . '%',
                'healthy' => $stored === $content && $usagePercentage < 90,
            ];
        } catch (\Exception $e) {
            return [
                'driver' => $disk ?? 'unknown',
                'status' => 'error',
                'error' => $e->getMessage(),
                'healthy' => false,
            ];
        }
    }
    
    /**
     * Format bytes to human-readable format
     *
     * @param  int  $bytes
     * @param  int  $precision
     * @return string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
