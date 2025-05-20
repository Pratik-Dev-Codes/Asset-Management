<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Traits\MonitorsMemoryUsage;

class CheckMemoryUsage extends Command
{
    use MonitorsMemoryUsage;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'memory:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check current memory usage and log it';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->getMemoryLimitInBytes();
        
        $memoryPercent = ($memory / $limit) * 100;
        $peakPercent = ($peak / $limit) * 100;
        
        $data = [
            'current_memory' => $this->formatBytes($memory),
            'peak_memory' => $this->formatBytes($peak),
            'memory_limit' => $this->formatBytes($limit),
            'current_percent' => round($memoryPercent, 2) . '%',
            'peak_percent' => round($peakPercent, 2) . '%',
            'timestamp' => now()->toDateTimeString(),
        ];
        
        // Log the memory usage
        Log::info('Memory usage check', $data);
        
        // Display in console
        $this->table(
            ['Metric', 'Value'],
            [
                ['Current Memory', $data['current_memory']],
                ['Peak Memory', $data['peak_memory']],
                ['Memory Limit', $data['memory_limit']],
                ['Current Usage', $data['current_percent']],
                ['Peak Usage', $data['peak_percent']],
                ['Time', $data['timestamp']],
            ]
        );
        
        // Check if we're approaching memory limit
        $threshold = config('queue.memory.threshold', 80);
        if ($memoryPercent > $threshold) {
            $message = sprintf(
                'Memory usage is at %.2f%% of the limit. Current: %s, Peak: %s, Limit: %s',
                $memoryPercent,
                $data['current_memory'],
                $data['peak_memory'],
                $data['memory_limit']
            );
            
            Log::warning('High memory usage detected', [
                'message' => $message,
                'threshold' => $threshold,
            ]);
            
            $this->warn($message);
            
            // Return non-zero exit code to indicate warning
            return 1;
        }
        
        return 0;
    }
}
