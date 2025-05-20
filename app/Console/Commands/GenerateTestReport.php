<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Models\User;
use App\Jobs\GenerateReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GenerateTestReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a test report to verify the reporting system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Asset Management System - Test Report Generation ===');
        $this->line('This command will help you generate a test report to verify the reporting system.');
        $this->line('');

        // Get user selection for report type
        $reportType = $this->choice(
            'Select report type',
            ['asset' => 'Asset Report'],
            0
        );

        // Get user selection for format
        $format = $this->choice(
            'Select export format',
            ['xlsx' => 'Excel (XLSX)', 'csv' => 'CSV', 'pdf' => 'PDF'],
            0
        );

        // Get filters
        $filters = [];
        if ($this->confirm('Would you like to apply any filters?', false)) {
            $filters = $this->getFilters();
        }

        // Get notification email
        $email = $this->ask('Enter email address to send notification (leave empty to skip email)');
        
        if ($email) {
            $validator = Validator::make(
                ['email' => $email],
                ['email' => 'required|email']
            );

            if ($validator->fails()) {
                $this->error('Invalid email address. No notification will be sent.');
                $email = null;
            }
        }

        // Create the report record
        $report = new Report([
            'name' => 'Test Report - ' . now()->format('Y-m-d H:i:s'),
            'description' => 'Test report generated from command line',
            'type' => $reportType,
            'format' => $format,
            'filters' => $filters,
            'status' => 'pending',
            'created_by' => User::first()?->id ?? 1,
            'notify_email' => $email,
        ]);

        $report->save();

        $this->info("\nGenerating report...");
        $this->line("Report ID: {$report->id}");
        $this->line("Type: {$report->type}");
        $this->line("Format: {$report->format}");
        $this->line("Status: {$report->status}");
        
        if (!empty($filters)) {
            $this->line("\nFilters:");
            foreach ($filters as $key => $value) {
                if (is_array($value)) {
                    $this->line("- {$key}: " . json_encode($value) . "");
                } else {
                    $this->line("- {$key}: {$value}");
                }
            }
        }

        // Dispatch the job
        GenerateReport::dispatch($report);

        $this->info("\nReport generation has been queued. You will be notified when it's ready.");
        $this->line("To check the status, run: php artisan queue:work");
        $this->line("Or check the reports list in the web interface.");
    }

    /**
     * Get filters from user input.
     *
     * @return array
     */
    protected function getFilters()
    {
        $filters = [];

        // Status filter
        if ($this->confirm('Filter by status?', false)) {
            $status = $this->choice(
                'Select status',
                ['active' => 'Active', 'in_maintenance' => 'In Maintenance', 'retired' => 'Retired'],
                0
            );
            $filters['status'] = $status;
        }

        // Date range filter
        if ($this->confirm('Filter by purchase date range?', false)) {
            $startDate = $this->ask('Enter start date (YYYY-MM-DD) or leave empty for no start date');
            $endDate = $this->ask('Enter end date (YYYY-MM-DD) or leave empty for no end date');

            $dateRange = [];
            if ($startDate) {
                $dateRange['start'] = $startDate;
            }
            if ($endDate) {
                $dateRange['end'] = $endDate;
            }

            if (!empty($dateRange)) {
                $filters['purchase_date'] = $dateRange;
            }
        }

        return $filters;
    }
}
