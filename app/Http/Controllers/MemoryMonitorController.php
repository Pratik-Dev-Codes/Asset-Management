<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\MemoryUsageLog;
use App\Traits\MonitorsMemoryUsage;
use Carbon\Carbon;

class MemoryMonitorController extends Controller
{
    use MonitorsMemoryUsage;

    /**
     * Display memory usage statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Get current memory usage
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->getMemoryLimitInBytes();
        
        $memoryPercent = ($memory / $limit) * 100;
        $peakPercent = ($peak / $limit) * 100;
        
        // Get queue worker status
        $workers = $this->getQueueWorkerStatus();
        
        // Get database status
        $dbStatus = $this->getDatabaseStatus();
        
        // Get cache status
        $cacheStatus = $this->getCacheStatus();
        
        // Get time range from request or default to 24 hours
        $hours = $request->input('hours', 24);
        
        // Get historical data
        $historicalData = $this->getHistoricalData($hours);
        
        // Get system statistics
        $stats = MemoryUsageLog::getDashboardStats($hours);
        
        // Log current memory usage
        $this->logMemoryUsage($workers['count']);
        
        return response()->json([
            'memory' => [
                'current' => $memory,
                'current_formatted' => $this->formatBytes($memory),
                'peak' => $peak,
                'peak_formatted' => $this->formatBytes($peak),
                'limit' => $limit,
                'limit_formatted' => $this->formatBytes($limit),
                'current_percent' => round($memoryPercent, 2),
                'peak_percent' => round($peakPercent, 2),
            ],
            'workers' => $workers,
            'database' => $dbStatus,
            'cache' => $cacheStatus,
            'historical' => $historicalData,
            'stats' => $stats,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
    
    /**
     * Get queue worker status
     * 
     * @return array
     */
    protected function getQueueWorkerStatus(): array
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows implementation
            $process = new \Symfony\Component\Process\Process(['tasklist', '/FI', 'IMAGENAME eq php.exe', '/FO', 'CSV', '/NH']);
            $process->run();
            
            $workers = [];
            $output = trim($process->getOutput());
            
            if (!empty($output)) {
                $lines = explode("\n", $output);
                
                foreach ($lines as $line) {
                    if (strpos($line, 'php.exe') !== false) {
                        $parts = str_getcsv($line);
                        if (count($parts) >= 5) {
                            $workers[] = [
                                'user' => 'SYSTEM',
                                'pid' => (int) $parts[1],
                                'memory' => rtrim($parts[4]),
                                'command' => 'php.exe',
                            ];
                        }
                    }
                }
            }
        } else {
            // Unix/Linux implementation
            $process = new \Symfony\Component\Process\Process(['ps', 'aux', '|', 'grep', '[q]ueue:work']);
            $process->run();
            
            $workers = [];
            $output = trim($process->getOutput());
            
            if (!empty($output)) {
                $lines = explode("\n", $output);
                
                foreach ($lines as $line) {
                    if (preg_match('/^(\S+)\s+(\d+)\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+(\S+)/', $line, $matches)) {
                        $workers[] = [
                            'user' => $matches[1],
                            'pid' => (int)$matches[2],
                            'memory' => $matches[3],
                            'command' => substr($line, strpos($line, 'queue:work')),
                        ];
                    }
                }
            }
        }
        
        return [
            'count' => count($workers),
            'workers' => $workers,
        ];
    }
    
    /**
     * Get database status
     * 
     * @return array
     */
    protected function getDatabaseStatus(): array
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            
            $status = [
                'driver' => $connection->getDriverName(),
                'version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
                'connection' => $connection->getDatabaseName(),
                'tables' => [],
            ];
            
            // Get table status
            $tables = $connection->select('SHOW TABLE STATUS');
            
            foreach ($tables as $table) {
                $status['tables'][] = [
                    'name' => $table->Name,
                    'rows' => $table->Rows,
                    'data_length' => $table->Data_length,
                    'data_length_formatted' => $this->formatBytes($table->Data_length),
                    'index_length' => $table->Index_length,
                    'index_length_formatted' => $this->formatBytes($table->Index_length),
                    'data_free' => $table->Data_free,
                    'data_free_formatted' => $this->formatBytes($table->Data_free),
                ];
            }
            
            return $status;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get cache status
     * 
     * @return array
     */
    protected function getCacheStatus(): array
    {
        try {
            $driver = config('cache.default');
            $stats = [];
            
            if ($driver === 'file') {
                $path = storage_path('framework/cache');
                $size = 0;
                $files = 0;
                
                if (is_dir($path)) {
                    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
                        if ($file->isFile()) {
                            $size += $file->getSize();
                            $files++;
                        }
                    }
                }
                
                $stats = [
                    'driver' => $driver,
                    'size' => $size,
                    'size_formatted' => $this->formatBytes($size),
                    'files' => $files,
                ];
            } elseif ($driver === 'redis') {
                $redis = Cache::getRedis();
                $info = $redis->info();
                
                $stats = [
                    'driver' => $driver,
                    'version' => $info['redis_version'] ?? 'unknown',
                    'uptime' => $info['uptime_in_seconds'] ?? 0,
                    'memory_used' => $info['used_memory'] ?? 0,
                    'memory_used_formatted' => $this->formatBytes($info['used_memory'] ?? 0),
                    'memory_peak' => $info['used_memory_peak'] ?? 0,
                    'memory_peak_formatted' => $this->formatBytes($info['used_memory_peak'] ?? 0),
                    'keys' => $info['db0'] ?? 'n/a',
                ];
            }
            
            return $stats;
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
