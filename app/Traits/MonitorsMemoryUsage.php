<?php

namespace App\Traits;

trait MonitorsMemoryUsage
{
    /**
     * Log current memory usage
     *
     * @param  string  $context  Context for the log message
     */
    protected function logMemoryUsage(string $context = ''): void
    {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        \Illuminate\Support\Facades\Log::info('Memory Usage', [
            'context' => $context,
            'memory_usage' => $this->formatBytes($memory),
            'peak_memory' => $this->formatBytes($peak),
            'memory_limit' => ini_get('memory_limit'),
        ]);
    }

    /**
     * Check if memory usage is approaching the limit
     *
     * @param  int  $threshold  Percentage threshold (default: 80%)
     * @return bool True if memory usage is above the threshold
     */
    protected function checkMemoryLimit(int $threshold = 80): bool
    {
        $memoryLimit = $this->getMemoryLimitInBytes();
        $usage = memory_get_usage(true);

        $usagePercent = ($usage / $memoryLimit) * 100;

        if ($usagePercent > $threshold) {
            \Illuminate\Support\Facades\Log::warning('Memory limit approaching', [
                'usage_percent' => round($usagePercent, 2),
                'threshold' => $threshold,
                'memory_usage' => $this->formatBytes($usage),
                'memory_limit' => $this->formatBytes($memoryLimit),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get memory limit in bytes
     *
     * @return int Memory limit in bytes
     */
    private function getMemoryLimitInBytes(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return PHP_INT_MAX;
        }

        $memoryLimit = trim($memoryLimit);
        $lastChar = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $memoryLimit = (int) $memoryLimit;

        switch ($lastChar) {
            case 'g':
                $memoryLimit *= 1024;
                // no break
            case 'm':
                $memoryLimit *= 1024;
                // no break
            case 'k':
                $memoryLimit *= 1024;
        }

        return $memoryLimit;
    }

    /**
     * Format bytes to human-readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = $bytes ? floor(log($bytes) / log(1024)) : 0;
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2).' '.$units[$pow];
    }
}
