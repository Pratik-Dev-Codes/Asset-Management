<?php

namespace App\Services;

use App\Models\Report;
use App\Notifications\ReportGenerated;
use App\Notifications\ReportGenerationFailed;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReportSchedulerService
{
    /**
     * The report service instance.
     *
     * @var \App\Services\ReportService
     */
    protected $reportService;

    /**
     * The notification service instance.
     *
     * @var \App\Services\NotificationService
     */
    protected $notificationService;

    /**
     * Create a new scheduler instance.
     *
     * @return void
     */
    public function __construct(ReportService $reportService, NotificationService $notificationService)
    {
        $this->reportService = $reportService;
        $this->notificationService = $notificationService;
    }

    /**
     * Process all scheduled reports that are due.
     *
     * @return void
     */
    public function processScheduledReports()
    {
        $now = now();
        $reports = Report::where('is_scheduled', true)
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('last_run_at')
                    ->orWhere('last_run_at', '<=', $this->getNextRunDate($now));
            })
            ->get();

        foreach ($reports as $report) {
            try {
                $this->processReport($report);
            } catch (\Exception $e) {
                Log::error('Failed to process scheduled report: '.$e->getMessage(), [
                    'report_id' => $report->id,
                    'exception' => $e,
                ]);

                // Notify admin of failure
                if ($report->user) {
                    $this->notificationService->notifyUser(
                        $report->user,
                        new ReportGenerationFailed($report, $e->getMessage())
                    );
                }
            }
        }
    }

    /**
     * Process a single scheduled report.
     *
     * @return bool True if the report was processed successfully, false otherwise
     *
     * @throws \Exception If there's an error processing the report
     */
    public function processReport(Report $report)
    {
        try {
            // Update last run time before processing to prevent duplicate runs
            $report->last_run_at = now();
            $report->save();

            // Generate the report - assuming the report has an export method
            $filePath = $report->export('xlsx'); // Default to Excel format

            // Notify the user
            if ($report->user) {
                $this->notificationService->notifyUser(
                    $report->user,
                    new ReportGenerated($report, $filePath)
                );
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to process scheduled report', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($report->user) {
                $this->notificationService->notifyUser(
                    $report->user,
                    new ReportGenerationFailed($report, $e->getMessage())
                );
            }

            throw $e;
        }
    }

    /**
     * Calculate the next run date based on the schedule.
     *
     * @return \Carbon\Carbon
     */
    protected function getNextRunDate(Carbon $now)
    {
        // Default to daily at midnight
        return $now->copy()->startOfDay()->addDay();
    }
}
