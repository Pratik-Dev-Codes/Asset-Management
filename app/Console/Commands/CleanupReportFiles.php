<?php

namespace App\Console\Commands;

use App\Models\ReportFile;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupReportFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:cleanup 
                            {--days=7 : Number of days to keep report files}
                            {--dry-run : Run in dry-run mode to see which files would be deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old report files from storage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        
        if ($days < 1) {
            $this->error('Days must be greater than 0');
            return 1;
        }
        
        $cutoffDate = Carbon::now()->subDays($days);
        $this->info("Cleaning up report files older than {$cutoffDate->toDateTimeString()} (" . ($dryRun ? 'dry run' : 'live') . ")");
        
        $query = ReportFile::where('created_at', '<', $cutoffDate);
        $count = $query->count();
        
        if ($count === 0) {
            $this->info('No report files to clean up.');
            return 0;
        }
        
        $this->info("Found {$count} report file(s) to clean up.");
        
        if ($dryRun) {
            $this->info('Dry run: No files will be deleted.');
            $this->table(
                ['ID', 'Report ID', 'File Path', 'Created At'],
                $query->get(['id', 'report_id', 'file_path', 'created_at'])->toArray()
            );
            return 0;
        }
        
        if (!$this->confirm("Are you sure you want to delete {$count} report file(s)?")) {
            $this->info('Cleanup cancelled.');
            return 0;
        }
        
        $deleted = 0;
        $failed = 0;
        
        $query->chunk(100, function ($files) use (&$deleted, &$failed) {
            foreach ($files as $file) {
                try {
                    // Delete the physical file
                    if (Storage::exists($file->file_path)) {
                        Storage::delete($file->file_path);
                    }
                    
                    // Delete the database record
                    $file->delete();
                    $deleted++;
                    
                    // Clean up empty directories
                    $this->cleanupEmptyDirectories(dirname($file->file_path));
                    
                } catch (\Exception $e) {
                    Log::error("Failed to delete report file: {$file->file_path}", [
                        'error' => $e->getMessage(),
                        'file_id' => $file->id,
                    ]);
                    $failed++;
                }
            }
        });
        
        $this->info("Cleanup completed. Deleted: {$deleted}, Failed: {$failed}");
        
        if ($failed > 0) {
            $this->warn("Failed to delete {$failed} file(s). Check the logs for details.");
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Recursively remove empty directories
     * 
     * @param string $directory
     * @return void
     */
    protected function cleanupEmptyDirectories($directory)
    {
        if (!Storage::exists($directory)) {
            return;
        }
        
        // Skip if directory is not empty
        if (count(Storage::files($directory)) > 0 || count(Storage::directories($directory)) > 0) {
            return;
        }
        
        try {
            Storage::deleteDirectory($directory);
            $this->cleanupEmptyDirectories(dirname($directory));
        } catch (\Exception $e) {
            Log::error("Failed to delete directory: {$directory}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
