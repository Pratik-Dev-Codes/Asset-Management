<?php

namespace App\Console\Commands\Monitor;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ScheduledTaskAlert;
use App\Notifications\SlackAlert;
use Carbon\Carbon;

class CheckScheduledTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:scheduled-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if scheduled tasks are running as expected';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tasks = config('monitoring.scheduled_tasks.tasks', []);
        $alerts = [];
        
        foreach ($tasks as $taskName => $config) {
            $lastRunKey = "scheduled_task_last_run_{$taskName}";
            $lastRun = Cache::get($lastRunKey);
            
            if (!$lastRun) {
                $this->warn("No previous run found for task: {$taskName}");
                continue;
            }
            
            $lastRunTime = Carbon::parse($lastRun);
            $expectedInterval = $this->parseInterval($config['interval']);
            $nextExpectedRun = $lastRunTime->copy()->add($expectedInterval);
            $now = now();
            
            if ($now->gt($nextExpectedRun)) {
                $minutesLate = $now->diffInMinutes($nextExpectedRun);
                
                if ($minutesLate > $config['grace_period'] ?? 5) {
                    $alerts[] = [
                        'task' => $taskName,
                        'last_run' => $lastRunTime->toDateTimeString(),
                        'expected_interval' => $config['interval'],
                        'minutes_late' => $minutesLate,
                    ];
                }
            }
            
            $this->info(sprintf(
                'Task %s: Last run %s, Next due %s',
                $taskName,
                $lastRunTime->diffForHumans(),
                $nextExpectedRun->diffForHumans()
            ));
        }
        
        // Send alerts if needed
        if (!empty($alerts)) {
            $this->sendAlerts($alerts);
        }
        
        return 0;
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
     * Send scheduled task alerts.
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
                    ->notify(new ScheduledTaskAlert($alert));
            }
            
            // Send Slack alert
            if (config('monitoring.notifications.slack.enabled')) {
                $message = sprintf(
                    "[WARNING] %s: Task '%s' is %d minutes late. Last run: %s",
                    $appName,
                    $alert['task'],
                    $alert['minutes_late'],
                    $alert['last_run']
                );
                
                Notification::route('slack', config('monitoring.notifications.slack.webhook_url'))
                    ->notify(new SlackAlert($message, 'WARNING'));
            }
            
            // Log the alert
            \Illuminate\Support\Facades\Log::warning('Scheduled task alert', $alert);
        }
    }
}
