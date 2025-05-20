<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

class ScheduleCleanupReportFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:cleanup-report-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule the cleanup of expired report files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Scheduling cleanup of expired report files...');
        
        // Get the cleanup schedule configuration
        $scheduleTime = Config::get('reports.cleanup_schedule.time', '00:00');
        $retentionDays = Config::get('reports.cleanup_schedule.retention_days', 7);
        
        // Schedule the cleanup command to run daily
        Schedule::command('reports:cleanup', [
            '--days' => $retentionDays,
        ])->dailyAt($scheduleTime)
          ->onOneServer()
          ->before(function () use ($scheduleTime, $retentionDays) {
              Log::info("Starting scheduled cleanup of report files older than {$retentionDays} days at {$scheduleTime}");
          })
          ->after(function () {
              Log::info("Completed scheduled cleanup of report files");
          });
        
        $this->info("Cleanup scheduled to run daily at {$scheduleTime} for files older than {$retentionDays} days.");
        
        return 0;
    }
}
