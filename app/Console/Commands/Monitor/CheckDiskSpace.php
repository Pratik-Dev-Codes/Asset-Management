<?php

namespace App\Console\Commands\Monitor;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DiskSpaceAlert;
use App\Notifications\SlackAlert;

class CheckDiskSpace extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:disk-space';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check server disk space and send alerts if needed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $disks = config('monitoring.disks', [
            'local' => storage_path(),
        ]);
        
        $alerts = [];
        
        foreach ($disks as $name => $path) {
            $total = disk_total_space($path);
            $free = disk_free_space($path);
            $used = $total - $free;
            $percentUsed = ($used / $total) * 100;
            
            $warningThreshold = config('monitoring.storage.warning_threshold', 80);
            $criticalThreshold = config('monitoring.storage.critical_threshold', 90);
            
            if ($percentUsed >= $criticalThreshold) {
                $level = 'CRITICAL';
                $alerts[] = [
                    'disk' => $name,
                    'path' => $path,
                    'level' => $level,
                    'percent_used' => round($percentUsed, 2),
                    'total' => $this->formatBytes($total),
                    'used' => $this->formatBytes($used),
                    'free' => $this->formatBytes($free),
                ];
            } elseif ($percentUsed >= $warningThreshold) {
                $level = 'WARNING';
                $alerts[] = [
                    'disk' => $name,
                    'path' => $path,
                    'level' => $level,
                    'percent_used' => round($percentUsed, 2),
                    'total' => $this->formatBytes($total),
                    'used' => $this->formatBytes($used),
                    'free' => $this->formatBytes($free),
                ];
            }
            
            $this->info(sprintf(
                'Disk %s: %.2f%% used (%s / %s)',
                $name,
                $percentUsed,
                $this->formatBytes($used),
                $this->formatBytes($total)
            ));
        }
        
        // Send alerts if needed
        if (!empty($alerts)) {
            $this->sendAlerts($alerts);
        }
        
        return 0;
    }
    
    /**
     * Send disk space alerts.
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
                Mail::to($adminEmail)->send(new \App\Mail\DiskSpaceAlert($alert));
            }
            
            // Send Slack alert
            if (config('monitoring.notifications.slack.enabled')) {
                $message = sprintf(
                    "[%s] %s: Disk space %s%% used on %s (%s)",
                    $alert['level'],
                    $appName,
                    $alert['percent_used'],
                    $alert['disk'],
                    $alert['path']
                );
                
                Notification::route('slack', config('monitoring.notifications.slack.webhook_url'))
                    ->notify(new SlackAlert($message, $alert['level']));
            }
            
            // Log the alert
            \Illuminate\Support\Facades\Log::warning('Disk space alert', $alert);
        }
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
}
