<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ManageCache extends Command
{
    protected $signature = 'cache:manage';

    protected $description = 'Manage application cache and perform maintenance';

    public function handle()
    {
        $this->info('Starting cache management...');

        // Clear expired cache items
        $this->clearExpiredCache();

        // Clear old log files
        $this->clearOldLogs();

        // Run garbage collection
        $this->runGarbageCollection();

        $this->info('Cache management completed.');
        Log::info('Cache management completed successfully.');

        return 0;
    }

    protected function clearExpiredCache()
    {
        $this->info('Clearing expired cache items...');

        // Clear expired cache items for file cache
        if (config('cache.default') === 'file') {
            $this->clearFileCache();
        }

        // Clear all cache items for the default store
        try {
            $cache = Cache::store(config('cache.default'));

            // Try different methods to clear the cache
            if (method_exists($cache->getStore(), 'flush')) {
                $cache->getStore()->flush();
            } elseif (method_exists($cache, 'clear')) {
                $cache->clear();
            } elseif ($cache instanceof \Illuminate\Cache\Repository) {
                $cache->forget('*');
            }

            $this->info('Cache store cleared successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to clear cache store: '.$e->getMessage());
        }
    }

    protected function clearFileCache()
    {
        $directory = storage_path('framework/cache/data');

        if (! is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        $now = time();
        $deleted = 0;

        foreach ($files as $file) {
            $path = $directory.'/'.$file;

            // Delete files older than 1 day
            if (is_file($path) && filemtime($path) < ($now - 86400)) {
                @unlink($path);
                $deleted++;
            }
        }

        $this->info("Deleted $deleted old cache files.");
    }

    protected function clearOldLogs()
    {
        $this->info('Cleaning up old log files...');

        $logPath = storage_path('logs');
        $files = glob($logPath.'/laravel-*.log');
        $now = time();
        $deleted = 0;

        foreach ($files as $file) {
            // Keep logs from the last 7 days
            if (is_file($file) && filemtime($file) < ($now - (7 * 86400))) {
                @unlink($file);
                $deleted++;
            }
        }

        $this->info("Deleted $deleted old log files.");
    }

    protected function runGarbageCollection()
    {
        $this->info('Running garbage collection...');

        $before = memory_get_usage(true);
        $cycles = gc_collect_cycles();
        $after = memory_get_usage(true);

        $this->info(sprintf(
            'Garbage collection completed. Reclaimed %s memory. Cycles: %d',
            $this->formatBytes($before - $after),
            $cycles
        ));
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
