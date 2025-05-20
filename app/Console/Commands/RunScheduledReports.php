<?php

namespace App\Console\Commands;

use App\Jobs\ProcessReportJob;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:run-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all scheduled reports that are due';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = now();
        $this->info("Running scheduled reports at {$now->toDateTimeString()}");

        $reports = Report::where('is_scheduled', true)
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('last_run_at')
                    ->orWhere('last_run_at', '<', $now->copy()->subDay());
            })
            ->with('user')
            ->get();

        if ($reports->isEmpty()) {
            $this->info('No scheduled reports to run at this time.');

            return 0;
        }

        $this->info("Found {$reports->count()} report(s) to process.");

        $bar = $this->output->createProgressBar($reports->count());
        $bar->start();

        $reports->each(function ($report) use ($bar) {
            try {
                $this->processReport($report);
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("Failed to process report {$report->id}: ".$e->getMessage());
                Log::error('Failed to process scheduled report: '.$e->getMessage(), [
                    'report_id' => $report->id,
                    'error' => $e->getTraceAsString(),
                ]);
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('All scheduled reports have been processed.');

        return 0;
    }

    /**
     * Process a single scheduled report.
     *
     * @param  \App\Models\Report  $report
     * @return void
     */
    protected function processReport($report)
    {
        // Check if report is due
        if (! $this->isReportDue($report)) {
            $this->line("Skipping report {$report->id} - not due yet.", null, 'v');

            return;
        }

        $this->line("Processing report: {$report->name} (ID: {$report->id})", null, 'v');

        // Update the report status and last run time
        $report->update([
            'status' => 'processing',
            'last_run_at' => now(),
            'error_message' => null,
        ]);

        try {
            // Dispatch the job to process the report
            ProcessReportJob::dispatch($report, 'xlsx', $report->created_by)
                ->onQueue('reports');

            $this->info("Queued report {$report->id} for processing.");

        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if a report is due to run.
     *
     * @param  \App\Models\Report  $report
     * @return bool
     */
    protected function isReportDue($report)
    {
        if (! $report->schedule_frequency || ! $report->schedule_time) {
            return false;
        }

        $now = now();
        $time = Carbon::parse($report->schedule_time);

        // Create a carbon instance for the scheduled time today
        $scheduledTime = Carbon::create(
            $now->year,
            $now->month,
            $now->day,
            $time->hour,
            $time->minute,
            0
        );

        // Check if the scheduled time for today has passed
        if ($scheduledTime->isFuture()) {
            return false;
        }

        switch ($report->schedule_frequency) {
            case 'daily':
                // For daily reports, check if we haven't run it today
                return ! $report->last_run_at ||
                       $report->last_run_at->startOfDay()->lt($now->startOfDay());

            case 'weekly':
                // For weekly reports, check if today is the scheduled day and we haven't run it this week
                $scheduledDay = strtolower($report->schedule_day);
                $today = strtolower($now->englishDayOfWeek);

                if ($scheduledDay !== $today) {
                    return false;
                }

                return ! $report->last_run_at ||
                       $report->last_run_at->startOfWeek()->lt($now->startOfWeek());

            case 'monthly':
                // For monthly reports, check if today is the scheduled day and we haven't run it this month
                $scheduledDay = (int) $report->schedule_day;

                if ($now->day !== $scheduledDay) {
                    // If the scheduled day is greater than the number of days in the current month,
                    // run on the last day of the month
                    if ($scheduledDay > $now->daysInMonth) {
                        if ($now->day !== $now->daysInMonth) {
                            return false;
                        }
                    } else {
                        return false;
                    }
                }

                return ! $report->last_run_at ||
                       $report->last_run_at->startOfMonth()->lt($now->startOfMonth());

            default:
                return false;
        }
    }
}
