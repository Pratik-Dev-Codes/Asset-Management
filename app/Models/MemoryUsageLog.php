<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MemoryUsageLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'memory_used',
        'memory_peak',
        'cpu_usage',
        'disk_usage',
        'queue_size',
        'active_workers',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'memory_used' => 'float',
        'memory_peak' => 'float',
        'cpu_usage' => 'float',
        'disk_usage' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the memory usage logs for the last X hours.
     *
     * @param int $hours Number of hours to look back
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Get the memory usage statistics for the dashboard.
     *
     * @param int $hours Number of hours to include in the statistics
     * @return array
     */
    public static function getDashboardStats($hours = 24)
    {
        $stats = [
            'current' => [
                'memory_used' => 0,
                'memory_peak' => 0,
                'cpu_usage' => 0,
                'disk_usage' => 0,
                'queue_size' => 0,
                'active_workers' => 0,
            ],
            'averages' => [
                'memory_used' => 0,
                'memory_peak' => 0,
                'cpu_usage' => 0,
                'disk_usage' => 0,
                'queue_size' => 0,
                'active_workers' => 0,
            ],
            'max' => [
                'memory_used' => 0,
                'memory_peak' => 0,
                'cpu_usage' => 0,
                'disk_usage' => 0,
                'queue_size' => 0,
                'active_workers' => 0,
            ],
            'min' => [
                'memory_used' => PHP_FLOAT_MAX,
                'memory_peak' => PHP_FLOAT_MAX,
                'cpu_usage' => 100,
                'disk_usage' => 100,
                'queue_size' => PHP_INT_MAX,
                'active_workers' => PHP_INT_MAX,
            ],
        ];

        $logs = self::recent($hours)->get();

        if ($logs->isEmpty()) {
            return $stats;
        }

        // Get current values from the most recent log
        $latestLog = $logs->last();
        $stats['current'] = [
            'memory_used' => $latestLog->memory_used,
            'memory_peak' => $latestLog->memory_peak,
            'cpu_usage' => $latestLog->cpu_usage,
            'disk_usage' => $latestLog->disk_usage,
            'queue_size' => $latestLog->queue_size,
            'active_workers' => $latestLog->active_workers,
        ];

        // Calculate averages and find min/max values
        $sums = [
            'memory_used' => 0,
            'memory_peak' => 0,
            'cpu_usage' => 0,
            'disk_usage' => 0,
            'queue_size' => 0,
            'active_workers' => 0,
        ];

        $count = $logs->count();

        foreach ($logs as $log) {
            $sums['memory_used'] += $log->memory_used;
            $sums['memory_peak'] += $log->memory_peak;
            $sums['cpu_usage'] += $log->cpu_usage;
            $sums['disk_usage'] += $log->disk_usage;
            $sums['queue_size'] += $log->queue_size;
            $sums['active_workers'] += $log->active_workers;

            // Check for max values
            $stats['max']['memory_used'] = max($stats['max']['memory_used'], $log->memory_used);
            $stats['max']['memory_peak'] = max($stats['max']['memory_peak'], $log->memory_peak);
            $stats['max']['cpu_usage'] = max($stats['max']['cpu_usage'], $log->cpu_usage);
            $stats['max']['disk_usage'] = max($stats['max']['disk_usage'], $log->disk_usage);
            $stats['max']['queue_size'] = max($stats['max']['queue_size'], $log->queue_size);
            $stats['max']['active_workers'] = max($stats['max']['active_workers'], $log->active_workers);

            // Check for min values
            $stats['min']['memory_used'] = min($stats['min']['memory_used'], $log->memory_used);
            $stats['min']['memory_peak'] = min($stats['min']['memory_peak'], $log->memory_peak);
            $stats['min']['cpu_usage'] = min($stats['min']['cpu_usage'], $log->cpu_usage);
            $stats['min']['disk_usage'] = min($stats['min']['disk_usage'], $log->disk_usage);
            $stats['min']['queue_size'] = min($stats['min']['queue_size'], $log->queue_size);
            $stats['min']['active_workers'] = min($stats['min']['active_workers'], $log->active_workers);
        }

        // Calculate averages
        $stats['averages'] = [
            'memory_used' => $sums['memory_used'] / $count,
            'memory_peak' => $sums['memory_peak'] / $count,
            'cpu_usage' => $sums['cpu_usage'] / $count,
            'disk_usage' => $sums['disk_usage'] / $count,
            'queue_size' => $sums['queue_size'] / $count,
            'active_workers' => $sums['active_workers'] / $count,
        ];

        // If we didn't find any values, reset min values to 0
        if ($stats['min']['memory_used'] === PHP_FLOAT_MAX) {
            $stats['min'] = array_fill_keys(array_keys($stats['min']), 0);
        }

        return $stats;
    }
}
