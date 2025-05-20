<?php

namespace App\Console\Commands;

use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ListScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:list-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all scheduled reports with their next run time';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $reports = Report::where('is_scheduled', true)
            ->where('is_active', true)
            ->with('user:id,name,email')
            ->get();

        if ($reports->isEmpty()) {
            $this->info('No scheduled reports found.');
            return 0;
        }

        $this->info('Scheduled Reports');
        $this->info('================');

        $headers = ['ID', 'Name', 'Frequency', 'Next Run', 'Created By', 'Last Run', 'Status'];
        
        $rows = $reports->map(function ($report) {
            $nextRun = $this->calculateNextRun($report);
            
            return [
                $report->id,
                $report->name,
                ucfirst($report->schedule_frequency) . ' at ' . $report->schedule_time,
                $nextRun ? $nextRun->toDateTimeString() : 'N/A',
                $report->user ? $report->user->name . ' (' . $report->user->email . ')' : 'N/A',
                $report->last_run_at ? $report->last_run_at->diffForHumans() : 'Never',
                $this->getStatusBadge($report->status),
            ];
        });

        $this->table($headers, $rows);

        return 0;
    }

    /**
     * Calculate the next run time for a report.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Support\Carbon|null
     */
    protected function calculateNextRun($report)
    {
        if (!$report->schedule_frequency || !$report->schedule_time) {
            return null;
        }

        $now = now();
        $time = Carbon::parse($report->schedule_time);
        
        $nextRun = Carbon::create(
            $now->year,
            $now->month,
            $now->day,
            $time->hour,
            $time->minute,
            0
        );

        switch ($report->schedule_frequency) {
            case 'daily':
                if ($nextRun->isPast()) {
                    $nextRun->addDay();
                }
                break;
                
            case 'weekly':
                $nextRun->next(ucfirst($report->schedule_day));
                break;
                
            case 'monthly':
                $nextRun->addMonthNoOverflow();
                $nextRun->day = min($report->schedule_day, $nextRun->daysInMonth);
                break;
                
            default:
                return null;
        }

        return $nextRun;
    }
    
    /**
     * Get a formatted status badge.
     *
     * @param  string  $status
     * @return string
     */
    protected function getStatusBadge($status)
    {
        $statuses = [
            'pending' => '<fg=yellow>Pending</>',
            'processing' => '<fg=blue>Processing</>',
            'completed' => '<fg=green>Completed</>',
            'failed' => '<fg=red>Failed</>',
        ];
        
        return $statuses[strtolower($status)] ?? '<fg=gray>Unknown</>';
    }
}
