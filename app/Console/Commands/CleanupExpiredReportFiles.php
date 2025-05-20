<?php

namespace App\Console\Commands;

use App\Models\ReportFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredReportFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:cleanup 
                            {--dry-run : Run without actually deleting any files}
                            {--days= : Only delete files older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired report files from storage and database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $days = $this->option('days') ? (int) $this->option('days') : null;
        
        $this->info('Starting cleanup of expired report files' . ($isDryRun ? ' (dry run)' : '') . '...');
        
        // Get the query for expired files
        $query = ReportFile::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
            
        // If days is specified, only delete files older than that many days
        if ($days !== null) {
            $query->where('expires_at', '<=', now()->subDays($days));
        }
        
        // Get the files to be deleted
        $files = $query->get();
        $totalFiles = $files->count();
        
        if ($totalFiles === 0) {
            $this->info('No expired report files found.');
            return 0;
        }
        
        $this->info("Found {$totalFiles} expired report files to clean up.");
        
        if ($isDryRun) {
            $this->info('This is a dry run. No files will be deleted.');
            $this->table(
                ['ID', 'File Name', 'Expired At', 'Size'],
                $files->map(function ($file) {
                    return [
                        $file->id,
                        $file->file_name,
                        $file->expires_at->format('Y-m-d H:i:s'),
                        $file->formatted_file_size,
                    ];
                })
            );
            return 0;
        }
        
        // Create a progress bar
        $bar = $this->output->createProgressBar($totalFiles);
        $bar->start();
        
        $deletedCount = 0;
        $failedCount = 0;
        $deletedSize = 0;
        
        foreach ($files as $file) {
            try {
                // Delete the file from storage
                if (Storage::exists($file->file_path)) {
                    Storage::delete($file->file_path);
                    $deletedSize += $file->file_size;
                }
                
                // Delete the database record
                $file->delete();
                $deletedCount++;
                
                // Log the deletion
                Log::info("Deleted expired report file: {$file->file_name} (ID: {$file->id})");
            } catch (\Exception $e) {
                $failedCount++;
                Log::error("Failed to delete report file: {$file->file_name} (ID: {$file->id}) - " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        // Output the results
        $this->info("Cleanup completed!");
        $this->line("Total files processed: {$totalFiles}");
        $this->line("Successfully deleted: {$deletedCount}");
        $this->line("Failed to delete: {$failedCount}");
        $this->line("Total storage freed: " . $this->formatBytes($deletedSize));
        
        // Log the summary
        Log::info("Report file cleanup completed. Deleted {$deletedCount} files, failed {$failedCount}, freed " . $this->formatBytes($deletedSize));
        
        return 0;
    }
    
    /**
     * Format bytes to a human-readable format.
     *
     * @param  int  $bytes
     * @param  int  $precision
     * @return string
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
