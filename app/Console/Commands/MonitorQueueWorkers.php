<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class MonitorQueueWorkers extends Command
{
    use \App\Traits\MonitorsMemoryUsage;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor queue workers and restart them if they exceed memory limits';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting queue worker monitoring...');
        $this->logMemoryUsage('Starting queue worker monitor');

        $workers = $this->getRunningWorkers();

        if (empty($workers)) {
            $this->warn('No queue workers are currently running.');

            return 0;
        }

        $this->info(sprintf('Found %d queue workers', count($workers)));

        $memoryLimit = config('queue.memory.limit', 128) * 1024 * 1024; // Convert MB to bytes
        $threshold = config('queue.memory.threshold', 80);
        $restartCount = 0;

        foreach ($workers as $pid => $worker) {
            $memoryUsage = $this->getWorkerMemoryUsage($pid);
            $memoryPercent = ($memoryUsage / $memoryLimit) * 100;

            $this->info(sprintf(
                'Worker PID %d - Memory: %s / %s (%d%%)',
                $pid,
                $this->formatBytes($memoryUsage),
                $this->formatBytes($memoryLimit),
                round($memoryPercent, 2)
            ));

            if ($memoryPercent > $threshold) {
                $this->warn(sprintf(
                    'Worker %d is using too much memory (%.2f%%). Restarting...',
                    $pid,
                    $memoryPercent
                ));

                $this->restartWorker($pid);
                $restartCount++;

                Log::warning('Restarted queue worker due to high memory usage', [
                    'pid' => $pid,
                    'memory_usage' => $this->formatBytes($memoryUsage),
                    'memory_percent' => round($memoryPercent, 2),
                    'threshold' => $threshold,
                ]);
            }
        }

        $this->info(sprintf('Monitoring complete. %d workers were restarted.', $restartCount));

        return 0;
    }

    /**
     * Get list of running queue workers
     */
    protected function getRunningWorkers(): array
    {
        $process = Process::fromShellCommandline('ps aux | grep "[q]ueue:work" | grep -v "grep"');
        $process->run();

        $workers = [];
        $output = trim($process->getOutput());

        if (empty($output)) {
            return [];
        }

        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (preg_match('/^(\S+)\s+(\d+)\s+/', $line, $matches)) {
                $workers[(int) $matches[2]] = $line;
            }
        }

        return $workers;
    }

    /**
     * Get memory usage of a worker process
     *
     * @return int Memory usage in bytes
     */
    protected function getWorkerMemoryUsage(int $pid): int
    {
        $process = Process::fromShellCommandline('ps -o rss= -p '.$pid);
        $process->run();

        $output = trim($process->getOutput());

        if (empty($output)) {
            return 0;
        }

        return (int) $output * 1024; // Convert KB to bytes
    }

    /**
     * Restart a worker process
     */
    protected function restartWorker(int $pid): bool
    {
        // First try graceful shutdown
        $process = Process::fromShellCommandline('kill -TERM '.$pid);
        $process->run();

        // Wait a bit for the process to shut down
        usleep(500000); // 500ms

        // Check if it's still running
        $checkProcess = Process::fromShellCommandline('ps -p '.$pid.' -o pid=');
        $checkProcess->run();

        if (! empty(trim($checkProcess->getOutput()))) {
            // If still running, force kill
            $process = Process::fromShellCommandline('kill -9 '.$pid);
            $process->run();
        }

        return true;
    }
}
