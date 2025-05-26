<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ScheduleCleanupReportFiles::class,
        Commands\CleanupReportFiles::class,
        Commands\ListScheduledReports::class,
        Commands\RunScheduledReports::class,
        Commands\PruneOldNotifications::class,
        Commands\ManageCache::class,
        Commands\GenerateMemoryUsageData::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Schedule the cleanup of expired report files
        if (Config::get('reports.cleanup.enabled', true)) {
            $retentionDays = Config::get('reports.cleanup.retention_days', 7);
            $scheduleTime = Config::get('reports.cleanup_schedule.time', '00:00');

            $schedule->command('reports:cleanup', [
                '--days' => $retentionDays,
            ])
                ->dailyAt($scheduleTime)
                ->onOneServer()
                ->appendOutputTo(storage_path('logs/report-cleanup.log'));
        }

        // Schedule the running of scheduled reports
        $schedule->command('reports:run-scheduled')
            ->everyFiveMinutes()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scheduled-reports.log'));

        // Schedule cache management to run daily at 3 AM
        $schedule->command('cache:manage')
            ->dailyAt('03:00')
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/cache-management.log'));

        // Schedule the cleanup of old notifications
        $schedule->command('notifications:prune', [
            '--days' => 30,
            '--force' => true,
        ])
            ->daily()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/notification-cleanup.log'));

        // Schedule queue worker monitoring
        $schedule->command('queue:monitor')
            ->everyFiveMinutes()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/queue-monitor.log'));

        // Schedule memory usage check
        $schedule->command('memory:check')
            ->hourly()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/memory-usage.log'));

        // Schedule log cleanup
        $schedule->command('logs:cleanup', [
            '--days' => 30,
        ])
            ->dailyAt('02:00')
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/log-cleanup.log'));

        // Clean up old activity logs weekly
        $schedule->command('activity-logs:cleanup')
            ->weekly()
            ->onSuccess(function () {
                Log::info('Activity logs cleanup completed successfully');
            })
            ->onFailure(function () {
                Log::error('Activity logs cleanup failed');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
