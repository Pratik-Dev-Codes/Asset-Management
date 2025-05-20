<?php

namespace App\Console\Commands\Monitor;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;
use App\Notifications\QueueHealthAlert;
use App\Notifications\SlackAlert;

class CheckQueueHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:queue-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health of the queue system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $alerts = [];
        
        // Check failed jobs
        $failedJobsCount = $this->checkFailedJobs();
        if ($failedJobsCount > 0) {
            $alerts[] = [
                'type' => 'failed_jobs',
                'count' => $failedJobsCount,
                'message' => "There are {$failedJobsCount} failed jobs in the queue.",
            ];
        }
        
        // Check queue size
        $queueSizes = $this->checkQueueSizes();
        foreach ($queueSizes as $queueName => $size) {
            $threshold = config("monitoring.queue.thresholds.{$queueName}", 
                config('monitoring.queue.default_threshold', 100));
                
            if ($size > $threshold) {
                $alerts[] = [
                    'type' => 'queue_size',
                    'queue' => $queueName,
                    'size' => $size,
                    'threshold' => $threshold,
                    'message' => "Queue '{$queueName}' has {$size} jobs (threshold: {$threshold}).",
                ];
            }
            
            $this->info("Queue '{$queueName}': {$size} jobs");
        }
        
        // Check queue workers
        $workerStatus = $this->checkQueueWorkers();
        if (!$workerStatus['running']) {
            $alerts[] = [
                'type' => 'worker_status',
                'status' => 'not_running',
                'message' => 'No queue workers are currently running.',
            ];
            $this->error('No queue workers are running!');
        } else {
            $this->info("Queue workers: {$workerStatus['count']} running");
        }
        
        // Send alerts if needed
        if (!empty($alerts)) {
            $this->sendAlerts($alerts);
        }
        
        return 0;
    }
    
    /**
     * Check for failed jobs.
     *
     * @return int
     */
    protected function checkFailedJobs(): int
    {
        if (config('queue.failed.driver') === 'database-uuids') {
            return DB::table('failed_jobs')->count();
        }
        
        return DB::table('failed_jobs')->count();
    }
    
    /**
     * Get the current size of each queue.
     *
     * @return array
     */
    protected function checkQueueSizes(): array
    {
        $queues = config('queue.queues', ['default']);
        $sizes = [];
        
        foreach ($queues as $queue) {
            $sizes[$queue] = Queue::connection()->size($queue);
        }
        
        return $sizes;
    }
    
    /**
     * Check if queue workers are running.
     *
     * @return array
     */
    protected function checkQueueWorkers(): array
    {
        $processes = [];
        $command = 'php artisan queue:work';
        
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
     * Send queue health alerts.
     *
     * @param  array  $alerts
     * @return void
     */
    protected function sendAlerts(array $alerts): void
    {
        $adminEmail = config('monitoring.notifications.mail.to');
        $appName = config('app.name');
        
        foreach ($alerts as $alert) {
            // Send email alert
            if (config('monitoring.notifications.mail.enabled') && $adminEmail) {
                Notification::route('mail', $adminEmail)
                    ->notify(new QueueHealthAlert($alert));
            }
            
            // Send Slack alert
            if (config('monitoring.notifications.slack.enabled')) {
                $message = sprintf(
                    "[%s] %s: %s",
                    $alert['type'] === 'worker_status' ? 'CRITICAL' : 'WARNING',
                    $appName,
                    $alert['message']
                );
                
                $level = $alert['type'] === 'worker_status' ? 'CRITICAL' : 'WARNING';
                
                Notification::route('slack', config('monitoring.notifications.slack.webhook_url'))
                    ->notify(new SlackAlert($message, $level));
            }
            
            // Log the alert
            $logLevel = $alert['type'] === 'worker_status' ? 'error' : 'warning';
            \Illuminate\Support\Facades\Log::{$logLevel}('Queue health alert', $alert);
        }
    }
}
