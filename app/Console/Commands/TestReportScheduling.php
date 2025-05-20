<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Services\ReportSchedulerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestReportScheduling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:test-scheduling';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the report scheduling functionality';

    /**
     * The report scheduler service instance.
     *
     * @var \App\Services\ReportSchedulerService
     */
    protected $scheduler;

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\ReportSchedulerService  $scheduler
     * @return void
     */
    public function __construct(ReportSchedulerService $scheduler)
    {
        parent::__construct();
        $this->scheduler = $scheduler;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing report scheduling functionality...');
        
        try {
            // Get all active scheduled reports
            $reports = Report::where('is_scheduled', true)
                ->where('is_active', true)
                ->with('user')
                ->get();
                
            if ($reports->isEmpty()) {
                $this->warn('No active scheduled reports found.');
                return 0;
            }
            
            $this->info("Found {$reports->count()} active scheduled reports.");
            
            // Process each report
            foreach ($reports as $report) {
                $this->info("\nProcessing report: {$report->name} (ID: {$report->id})");
                
                try {
                    // Process the report
                    $this->scheduler->processReport($report);
                    $this->info("Successfully processed report: {$report->name}");
                } catch (\Exception $e) {
                    $this->error("Failed to process report: {$e->getMessage()}");
                    Log::error("Failed to process report: " . $e->getMessage(), [
                        'report_id' => $report->id,
                        'exception' => $e
                    ]);
                }
            }
            
            $this->info("\nReport scheduling test completed successfully.");
            return 0;
            
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
            Log::error("Report scheduling test failed: " . $e->getMessage(), [
                'exception' => $e
            ]);
            return 1;
        }
    }
}
