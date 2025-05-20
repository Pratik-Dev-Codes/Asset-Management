<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:prune 
                            {--days=30 : Delete notifications older than this number of days}
                            {--force : Run without confirmation}
                            {--dry-run : Show how many notifications would be deleted without actually deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove old notifications from the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($days < 1) {
            $this->error('Days must be greater than 0');

            return 1;
        }

        $cutoffDate = Carbon::now()->subDays($days);
        $this->info("Pruning notifications older than {$cutoffDate->toDateTimeString()}");

        // Count notifications that would be deleted
        $count = DB::table('notifications')
            ->where('created_at', '<', $cutoffDate)
            ->count();

        if ($count === 0) {
            $this->info('No notifications to prune.');

            return 0;
        }

        if ($dryRun) {
            $this->info("Dry run: Would delete {$count} notification(s) older than {$cutoffDate->toDateTimeString()}");

            return 0;
        }

        if (! $force && ! $this->confirm("Are you sure you want to delete {$count} notification(s)?")) {
            $this->info('Pruning cancelled.');

            return 0;
        }

        $this->info("Deleting {$count} notification(s) older than {$cutoffDate->toDateTimeString()}...");

        $startTime = microtime(true);

        try {
            // Use chunking to avoid memory issues with large tables
            $query = DB::table('notifications')
                ->where('created_at', '<', $cutoffDate);

            $deleted = 0;
            $query->chunk(1000, function ($notifications) use (&$deleted) {
                $ids = $notifications->pluck('id')->toArray();
                $count = DB::table('notifications')->whereIn('id', $ids)->delete();
                $deleted += $count;
                $this->info("Deleted {$deleted} notifications so far...");
            });

            $duration = round(microtime(true) - $startTime, 2);

            $this->info("Successfully deleted {$deleted} notification(s) in {$duration} seconds.");

            // Log the cleanup
            Log::info("Pruned {$deleted} old notifications older than {$cutoffDate->toDateTimeString()}");

            return 0;

        } catch (\Exception $e) {
            $this->error('Error pruning notifications: '.$e->getMessage());
            Log::error('Failed to prune notifications: '.$e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
