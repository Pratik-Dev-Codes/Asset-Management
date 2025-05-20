<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MonitorService
{
    /**
     * Check disk space and return status.
     *
     * @return array
     */
    public function checkDiskSpace(): array
    {
        $disks = config('monitoring.disks', [
            'local' => storage_path(),
        ]);
        
        $status = [];
        
        foreach ($disks as $name => $path) {
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            $used = $total - $free;
            $percentUsed = $total > 0 ? ($used / $total) * 100 : 0;
            
            $status[$name] = [
                'path' => $path,
                'total' => $this->formatBytes($total),
                'used' => $this->formatBytes($used),
                'free' => $this->formatBytes($free),
                'percent_used' => round($percentUsed, 2),
                'status' => $this->getDiskStatus($percentUsed),
            ];
        }
        
        return $status;
    }
    
    /**
     * Check queue health and return status.
     *
     * @return array
     */
    public function checkQueueHealth(): array
    {
        $queues = config('queue.queues', ['default']);
        $status = [
            'failed_jobs' => $this->getFailedJobsCount(),
            'queues' => [],
            'workers' => $this->checkQueueWorkers(),
        ];
        
        foreach ($queues as $queue) {
            $size = Queue::connection()->size($queue);
            $threshold = config("monitoring.queue.thresholds.{$queue}", 
                config('monitoring.queue.default_threshold', 100));
                
            $status['queues'][$queue] = [
                'size' => $size,
                'threshold' => $threshold,
                'status' => $size > $threshold ? 'warning' : 'ok',
            ];
        }
        
        return $status;
    }
    
    /**
     * Check scheduled tasks and return status.
     *
     * @return array
     */
    public function checkScheduledTasks(): array
    {
        $tasks = config('monitoring.scheduled_tasks.tasks', []);
        $status = [];
        
        foreach ($tasks as $taskName => $config) {
            $lastRunKey = "scheduled_task_last_run_{$taskName}";
            $lastRun = Cache::get($lastRunKey);
            
            if (!$lastRun) {
                $status[$taskName] = [
                    'last_run' => null,
                    'status' => 'unknown',
                    'message' => 'No previous run found',
                ];
                continue;
            }
            
            $lastRunTime = Carbon::parse($lastRun);
            $expectedInterval = $this->parseInterval($config['interval']);
            $nextExpectedRun = $lastRunTime->copy()->add($expectedInterval);
            $now = now();
            $minutesLate = $now->diffInMinutes($nextExpectedRun);
            
            $status[$taskName] = [
                'last_run' => $lastRunTime->toDateTimeString(),
                'next_expected_run' => $nextExpectedRun->toDateTimeString(),
                'minutes_late' => $minutesLate,
                'interval' => $config['interval'],
                'status' => $minutesLate > ($config['grace_period'] ?? 5) ? 'late' : 'on_time',
            ];
        }
        
        return $status;
    }
    
    /**
     * Get overall health status of the system.
     *
     * @return array
     */
    public function getHealthStatus(): array
    {
        $diskStatus = $this->checkDiskSpace();
        $queueStatus = $this->checkQueueHealth();
        $scheduledTasksStatus = $this->checkScheduledTasks();
        
        // Check for critical disk space
        $diskHealth = 'ok';
        foreach ($diskStatus as $disk) {
            if ($disk['percent_used'] >= config('monitoring.storage.critical_threshold', 90)) {
                $diskHealth = 'critical';
                break;
            } elseif ($disk['percent_used'] >= config('monitoring.storage.warning_threshold', 80)) {
                $diskHealth = 'warning';
            }
        }
        
        // Check for failed jobs
        $queueHealth = $queueStatus['failed_jobs'] > 0 ? 'warning' : 'ok';
        
        // Check for late scheduled tasks
        $scheduledTasksHealth = 'ok';
        foreach ($scheduledTasksStatus as $task) {
            if (($task['status'] ?? 'unknown') === 'late') {
                $scheduledTasksHealth = 'warning';
                break;
            }
        }
        
        // Overall status
        $overallHealth = 'ok';
        if ($diskHealth === 'critical' || $queueHealth === 'critical' || $scheduledTasksHealth === 'critical') {
            $overallHealth = 'critical';
        } elseif ($diskHealth === 'warning' || $queueHealth === 'warning' || $scheduledTasksHealth === 'warning') {
            $overallHealth = 'warning';
        }
        
        return [
            'status' => $overallHealth,
            'timestamp' => now()->toDateTimeString(),
            'components' => [
                'disk' => [
                    'status' => $diskHealth,
                    'details' => $diskStatus,
                ],
                'queue' => [
                    'status' => $queueHealth,
                    'details' => $queueStatus,
                ],
                'scheduled_tasks' => [
                    'status' => $scheduledTasksHealth,
                    'details' => $scheduledTasksStatus,
                ],
            ],
        ];
    }
    
    /**
     * Get the number of failed jobs.
     *
     * @return int
     */
    protected function getFailedJobsCount(): int
    {
        if (config('queue.failed.driver') === 'database-uuids') {
            return DB::table('failed_jobs')->count();
        }
        
        return DB::table('failed_jobs')->count();
    }
    
    /**
     * Check if queue workers are running.
     *
     * @return array
     */
    protected function checkQueueWorkers(): array
    {
        $command = 'php artisan queue:work';
        $processes = [];
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            exec("tasklist | findstr \"php.exe\"", $processes);
            $count = count(array_filter($processes, function($line) use ($command) {
                return strpos($line, 'php.exe') !== false && 
                       strpos($line, 'queue:work') !== false;
            }));
        } else {
            // Unix/Linux
            exec("ps aux | grep '{$command}' | grep -v grep", $processes);
            $count = count($processes);
        }
        
        return [
            'running' => $count > 0,
            'count' => $count,
        ];
    }
    
    /**
     * Get the status of a disk based on usage percentage.
     *
     * @param  float  $percentUsed
     * @return string
     */
    protected function getDiskStatus(float $percentUsed): string
    {
        if ($percentUsed >= config('monitoring.storage.critical_threshold', 90)) {
            return 'critical';
        }
        
        if ($percentUsed >= config('monitoring.storage.warning_threshold', 80)) {
            return 'warning';
        }
        
        return 'ok';
    }
    
    /**
     * Parse interval string to DateInterval.
     *
     * @param  string  $interval
     * @return \DateInterval
     */
    protected function parseInterval(string $interval)
    {
        $interval = strtolower(trim($interval));
        
        if (is_numeric($interval)) {
            return new \DateInterval("PT{$interval}S");
        }
        
        if (preg_match('/^(\d+)\s*(s|sec|second|seconds)$/i', $interval, $matches)) {
            return new \DateInterval("PT{$matches[1]}S");
        }
        
        if (preg_match('/^(\d+)\s*(m|min|minute|minutes)$/i', $interval, $matches)) {
            return new \DateInterval("PT{$matches[1]}M");
        }
        
        if (preg_match('/^(\d+)\s*(h|hour|hours)$/i', $interval, $matches)) {
            return new \DateInterval("PT{$matches[1]}H");
        }
        
        if (preg_match('/^(\d+)\s*(d|day|days)$/i', $interval, $matches)) {
            return new \DateInterval("P{$matches[1]}D");
        }
        
        // Default to 1 hour if format is not recognized
        return new \DateInterval('PT1H');
    }
    
    /**
     * Format bytes to human-readable format.
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
    
    /**
     * Get queue status.
     *
     * @return array
     */
    public function getQueueStatus(): array
    {
        return $this->checkQueueHealth();
    }
    
    /**
     * Get scheduled tasks status.
     *
     * @return array
     */
    public function getScheduledTasksStatus(): array
    {
        return $this->checkScheduledTasks();
    }
    
    /**
     * Get overall system status.
     *
     * @return array
     */
    public function getSystemStatus(): array
    {
        return [
            'health' => $this->getHealthStatus(),
            'timestamp' => now()->toDateTimeString(),
            'system' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
                'os' => PHP_OS,
                'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                'peak_memory_usage' => $this->formatBytes(memory_get_peak_usage(true)),
            ],
        ];
    }
}
