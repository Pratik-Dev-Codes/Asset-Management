<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-logs:cleanup {--days=90 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old activity logs to prevent excessive database growth';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $date = Carbon::now()->subDays($days);

        $this->info("Cleaning up activity logs older than {$days} days...");

        // Delete in chunks to prevent memory issues
        $deleted = 0;
        DB::table('activity_log')
            ->where('created_at', '<', $date)
            ->orderBy('id')
            ->chunk(1000, function ($logs) use (&$deleted) {
                $ids = $logs->pluck('id')->toArray();
                $count = DB::table('activity_log')
                    ->whereIn('id', $ids)
                    ->delete();
                $deleted += $count;
                $this->info("Deleted {$count} logs...");
            });

        $this->info("Successfully deleted {$deleted} old activity logs.");
    }
}
