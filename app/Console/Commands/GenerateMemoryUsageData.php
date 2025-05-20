<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMemoryUsageData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'memory:generate-data {--hours=24 : Number of hours of data to generate} {--interval=5 : Interval in minutes between data points}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sample memory usage data for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Disable query logging to save memory
        DB::connection()->disableQueryLog();

        $hours = (int) $this->option('hours');
        $interval = (int) $this->option('interval');

        if ($hours <= 0 || $interval <= 0) {
            $this->error('Hours and interval must be greater than 0');

            return 1;
        }

        $now = now();
        $startTime = $now->copy()->subHours($hours);

        $this->info("Generating memory usage data from {$startTime} to {$now} with {$interval}-minute intervals");

        $current = $startTime->copy();
        $count = 0;
        $batch = [];
        $batchSize = 100; // Process in batches of 100 records

        while ($current->lte($now)) {
            $memoryUsage = $this->generateMemoryDataPoint($current);

            // Add to batch
            $batch[] = [
                'memory_used' => $memoryUsage['memory_used'],
                'memory_peak' => $memoryUsage['memory_peak'],
                'cpu_usage' => $memoryUsage['cpu_usage'],
                'disk_usage' => $memoryUsage['disk_usage'],
                'queue_size' => $memoryUsage['queue_size'],
                'active_workers' => $memoryUsage['active_workers'],
                'created_at' => $current->toDateTimeString(),
                'updated_at' => $current->toDateTimeString(),
            ];

            $current->addMinutes($interval);
            $count++;

            // Insert batch if batch size is reached
            if (count($batch) >= $batchSize) {
                $this->insertBatch($batch);
                $batch = [];
                $this->info("Generated {$count} data points...");

                // Explicitly free memory
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }
        }

        // Insert any remaining records
        if (! empty($batch)) {
            $this->insertBatch($batch);
        }

        $this->info("Successfully generated {$count} data points of memory usage data.");

        return 0;
    }

    /**
     * Generate a single data point of memory usage
     */
    /**
     * Insert a batch of records efficiently
     */
    protected function insertBatch(array $batch): void
    {
        if (empty($batch)) {
            return;
        }

        // Use a transaction for better performance
        DB::beginTransaction();
        try {
            $table = 'memory_usage_logs';
            $columns = array_keys($batch[0]);
            $values = [];
            $placeholders = [];

            foreach ($batch as $row) {
                $placeholders[] = '('.implode(',', array_fill(0, count($row), '?')).')';
                $values = array_merge($values, array_values($row));
            }

            $sql = "INSERT INTO {$table} (".implode(',', $columns).') VALUES '.implode(',', $placeholders);
            DB::insert($sql, $values);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error inserting batch: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate a single data point of memory usage
     */
    protected function generateMemoryDataPoint(Carbon $timestamp): array
    {
        // Base values with some randomness
        $hourOfDay = $timestamp->hour;
        $isDaytime = $hourOfDay >= 8 && $hourOfDay < 20;
        $isPeakTime = ($hourOfDay >= 9 && $hourOfDay < 12) || ($hourOfDay >= 14 && $hourOfDay < 17);

        // Base memory usage (MB)
        $baseMemory = $isDaytime ? 200 : 100;
        $memoryVariance = rand(0, 50) + ($isPeakTime ? rand(20, 100) : 0);
        $memoryUsed = $baseMemory + $memoryVariance;

        // Peak is 10-30% higher than used
        $peakMultiplier = 1.0 + (rand(10, 30) / 100);
        $memoryPeak = min(512, round($memoryUsed * $peakMultiplier, 2));

        // CPU usage (0-100%)
        $baseCpu = $isDaytime ? 20 : 10;
        $cpuVariance = rand(0, 20) + ($isPeakTime ? rand(10, 40) : 0);
        $cpuUsage = min(100, $baseCpu + $cpuVariance);

        // Disk usage (0-100%)
        $baseDisk = 40; // 40% base usage
        $diskVariance = rand(-5, 5);
        $diskUsage = max(0, min(100, $baseDisk + $diskVariance));

        // Queue size (0-1000)
        $baseQueue = $isDaytime ? 50 : 10;
        $queueVariance = rand(0, 30) + ($isPeakTime ? rand(20, 100) : 0);
        $queueSize = $baseQueue + $queueVariance;

        // Active workers (0-20)
        $baseWorkers = $isDaytime ? 3 : 1;
        $workerVariance = rand(0, 2) + ($isPeakTime ? rand(1, 5) : 0);
        $activeWorkers = min(20, $baseWorkers + $workerVariance);

        return [
            'memory_used' => $memoryUsed,
            'memory_peak' => $memoryPeak,
            'cpu_usage' => $cpuUsage,
            'disk_usage' => $diskUsage,
            'queue_size' => $queueSize,
            'active_workers' => $activeWorkers,
        ];
    }
}
