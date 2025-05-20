<?php

namespace App\Console\Commands;

use App\Services\ReportExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired report files';

    /**
     * The report export service instance.
     *
     * @var \App\Services\ReportExportService
     */
    protected $reportExportService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ReportExportService $reportExportService)
    {
        parent::__construct();
        $this->reportExportService = $reportExportService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting cleanup of expired report files...');

        try {
            $deletedCount = $this->reportExportService->deleteExpiredFiles();

            if ($deletedCount > 0) {
                $this->info("Successfully deleted {$deletedCount} expired report files.");
                Log::info("Cleaned up {$deletedCount} expired report files.");
            } else {
                $this->info('No expired report files to clean up.');
            }

            return 0;

        } catch (\Exception $e) {
            $error = 'Failed to clean up expired report files: '.$e->getMessage();
            $this->error($error);
            Log::error($error, [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
