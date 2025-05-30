<?php

namespace App\Jobs;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $report;
    protected $user;
    protected $format;

    /**
     * Create a new job instance.
     *
     * @param Report $report
     * @param User $user
     * @param string $format
     * @return void
     */
    public function __construct(Report $report, User $user, string $format)
    {
        $this->report = $report;
        $this->user = $user;
        $this->format = $format;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Generate a unique filename
            $filename = 'reports/' . $this->report->id . '/' . uniqid() . '.' . $this->format;
            $filePath = storage_path('app/public/' . $filename);

            // Ensure the directory exists
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            // Generate the report content (simplified example)
            $content = "Report: {$this->report->name}\n";
            $content .= "Generated at: " . now()->toDateTimeString() . "\n\n";
            
            // Add report data (in a real app, this would be the actual report data)
            $content .= "Report ID: {$this->report->id}\n";
            $content .= "Name: {$this->report->name}\n";
            $content .= "Type: {$this->report->type}\n";
            $content .= "Generated by: {$this->user->name}\n";

            // Save the file
            file_put_contents($filePath, $content);

            // Create a record in the database
            $reportFile = new ReportFile([
                'report_id' => $this->report->id,
                'file_name' => basename($filename),
                'file_path' => $filename,
                'file_type' => $this->format,
                'file_size' => filesize($filePath),
                'generated_by' => $this->user->id,
                'expires_at' => now()->addDays(7), // Files expire in 7 days
            ]);

            $reportFile->save();

            // In a real app, you would send a notification to the user
            // $this->user->notify(new ReportGenerated($reportFile));

        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to generate report: ' . $e->getMessage());
            throw $e;
        }
    }
}
