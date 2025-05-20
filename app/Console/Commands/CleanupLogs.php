<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanupLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:cleanup {--days=30 : Delete log files older than this many days} {--dry-run : List files that would be deleted without actually deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old log files to save disk space';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $logPath = storage_path('logs');
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days)->getTimestamp();
        
        $this->info(sprintf(
            'Looking for log files older than %d days in %s',
            $days,
            $logPath
        ));
        
        if ($dryRun) {
            $this->info('DRY RUN: No files will be deleted');
        }
        
        $files = File::files($logPath);
        $deleted = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($files as $file) {
            $lastModified = $file->getMTime();
            $size = $file->getSize();
            $formattedSize = $this->formatBytes($size);
            $formattedDate = date('Y-m-d H:i:s', $lastModified);
            
            if ($lastModified < $cutoff && $file->getExtension() === 'log') {
                $this->line(sprintf(
                    'Found old log file: %s (%s, last modified: %s)',
                    $file->getFilename(),
                    $formattedSize,
                    $formattedDate
                ));
                
                if (!$dryRun) {
                    try {
                        File::delete($file->getPathname());
                        $this->info('Deleted: ' . $file->getFilename());
                        $deleted++;
                    } catch (\Exception $e) {
                        $this->error('Error deleting ' . $file->getFilename() . ': ' . $e->getMessage());
                        Log::error('Error deleting log file', [
                            'file' => $file->getFilename(),
                            'error' => $e->getMessage(),
                        ]);
                        $errors++;
                    }
                } else {
                    $deleted++;
                }
            } else {
                $skipped++;
            }
        }
        
        $this->newLine();
        $this->info(sprintf(
            'Log cleanup complete. %d files would be deleted (%d actually deleted, %d skipped, %d errors)',
            $deleted,
            $dryRun ? 0 : $deleted,
            $skipped,
            $errors
        ));
        
        if ($deleted > 0) {
            Log::info('Log cleanup completed', [
                'deleted' => $deleted,
                'skipped' => $skipped,
                'errors' => $errors,
                'dry_run' => $dryRun,
                'days' => $days,
            ]);
        }
        
        return $errors > 0 ? 1 : 0;
    }
    
    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = $bytes ? floor(log($bytes) / log(1024)) : 0;
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }
}
