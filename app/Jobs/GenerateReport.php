<?php

namespace App\Jobs;

use App\Exports\ReportsExport;
use App\Mail\ReportGenerated;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The report instance.
     *
     * @var \App\Models\Report
     */
    protected $report;

    /**
     * The user instance.
     *
     * @var \App\Models\User
     */
    protected $user;

    /**
     * The export format.
     *
     * @var string
     */
    protected $format;

    /**
     * The filters to apply.
     *
     * @var array
     */
    protected $filters;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Report $report, User $user, string $format = 'xlsx', array $filters = [])
    {
        $this->report = $report;
        $this->user = $user;
        $this->format = $format;
        $this->filters = $filters;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ReportService $reportService)
    {
        try {
            // Get the report data
            $data = $reportService->getReportData($this->report, $this->filters);

            // Define the filename
            $filename = 'reports/'.Str::slug($this->report->name).'-'.now()->format('Y-m-d-His').'.'.$this->format;

            // Define the columns for the export
            $columns = $this->getExportColumns();

            // Generate the export file
            Excel::store(
                new ReportsExport($this->report, $data['data'], $columns, $this->format),
                $filename,
                'public',
                $this->getWriterType()
            );

            // Get the public URL for the file
            $fileUrl = Storage::disk('public')->url($filename);

            // Send notification to user
            $this->user->notify(new ReportGenerated($this->report, $fileUrl));

            // If email notification is requested, send email
            if ($this->user->email) {
                Mail::to($this->user->email)->send(new ReportGenerated($this->report, $fileUrl));
            }

            // Log the report generation
            activity()
                ->performedOn($this->report)
                ->causedBy($this->user)
                ->log('Generated report: '.$this->report->name);

        } catch (\Exception $e) {
            // Log the error
            \Log::error('Report generation failed: '.$e->getMessage(), [
                'report_id' => $this->report->id,
                'user_id' => $this->user->id,
                'exception' => $e->getTraceAsString(),
            ]);

            // Notify admin about the failure
            $admin = User::where('is_admin', true)->first();
            if ($admin) {
                $admin->notify(new ReportGenerationFailed($this->report, $e->getMessage()));
            }

            // Re-throw the exception to trigger job retry
            throw $e;
        }
    }

    /**
     * Get the columns for the export
     */
    protected function getExportColumns(): array
    {
        $columns = [];

        foreach ($this->report->columns as $column) {
            if (is_string($column)) {
                $columns[] = [
                    'id' => $column,
                    'label' => ucwords(str_replace('_', ' ', $column)),
                    'type' => 'string',
                ];
            } elseif (is_array($column)) {
                $columns[] = array_merge([
                    'type' => 'string',
                    'label' => $column['name'] ?? ucwords(str_replace('_', ' ', $column['id'] ?? '')),
                ], $column);
            }
        }

        return $columns;
    }

    /**
     * Get the writer type for the export
     */
    protected function getWriterType(): string
    {
        return match ($this->format) {
            'csv' => \Maatwebsite\Excel\Excel::CSV,
            'pdf' => \Maatwebsite\Excel\Excel::DOMPDF,
            'html' => \Maatwebsite\Excel\Excel::HTML,
            'ods' => \Maatwebsite\Excel::ODS,
            'xls' => \Maatwebsite\Excel::XLS,
            default => \Maatwebsite\Excel::XLSX,
        };
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        // Notify the user that the report generation failed
        $this->user->notify(new ReportGenerationFailed($this->report, $exception->getMessage()));

        // Log the failure
        \Log::error('Report generation job failed after all attempts', [
            'report_id' => $this->report->id,
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
