<?php

namespace App\Jobs;

use App\Models\Report;
use App\Models\ReportFile;
use App\Services\EnhancedReportDataService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel;

class ProcessReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public $timeout = 3600; // 1 hour

    /**
     * The report instance.
     *
     * @var \App\Models\Report
     */
    protected $report;

    /**
     * The export format.
     *
     * @var string
     */
    protected $format;

    /**
     * The user ID who requested the export.
     *
     * @var int
     */
    protected $userId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Report $report, string $format, int $userId)
    {
        $this->report = $report;
        $this->format = $format;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(EnhancedReportDataService $dataService)
    {
        try {
            $this->report->update(['status' => 'processing', 'progress' => 0]);

            // Get the report data
            $data = $dataService->getAllData($this->report);
            $this->report->update(['progress' => 30]);

            // Generate the export file
            $filePath = $this->generateExportFile($data);
            $this->report->update(['progress' => 80]);

            // Create a report file record
            $this->createReportFile($filePath);

            // Update the report status
            $this->report->update([
                'status' => 'completed',
                'progress' => 100,
                'completed_at' => now(),
            ]);

            Log::info("Report {$this->report->id} generated successfully");

        } catch (\Exception $e) {
            $this->report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Failed to generate report {$this->report->id}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // Notify the user about the failure
            $this->notifyUser($e->getMessage());

            // Re-throw to allow job retries
            throw $e;
        }
    }

    /**
     * Generate the export file
     *
     * @param  \Illuminate\Support\Collection  $data
     * @return string
     */
    protected function generateExportFile($data)
    {
        $fileName = 'reports/'.$this->report->id.'/'.uniqid().'.'.$this->format;
        $fullPath = storage_path('app/'.$fileName);

        // Ensure the directory exists
        if (! file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        switch ($this->format) {
            case 'xlsx':
            case 'csv':
                $this->exportToExcel($data, $fullPath);
                break;

            case 'pdf':
                $this->exportToPdf($data, $fullPath);
                break;

            default:
                throw new \InvalidArgumentException("Unsupported export format: {$this->format}");
        }

        return $fileName;
    }

    /**
     * Export data to Excel format
     *
     * @param  \Illuminate\Support\Collection  $data
     * @param  string  $path
     * @return void
     */
    protected function exportToExcel($data, $path)
    {
        $export = new class($data, $this->report->columns) implements FromCollection, WithHeadings
        {
            protected $data;

            protected $headings;

            public function __construct($data, $columns)
            {
                $this->data = $data;
                $this->headings = $columns;
            }

            public function collection()
            {
                return $this->data;
            }

            public function headings(): array
            {
                return $this->headings;
            }
        };

        $writerType = $this->format === 'csv' ? Excel::CSV : Excel::XLSX;

        app(Excel::class)->store($export, $path, 'local', $writerType);
    }

    /**
     * Export data to PDF format
     *
     * @param  \Illuminate\Support\Collection  $data
     * @param  string  $path
     * @return void
     */
    protected function exportToPdf($data, $path)
    {
        $pdf = app('dompdf.wrapper');

        $pdf->loadView('reports.pdf', [
            'data' => $data,
            'columns' => $this->report->columns,
            'report' => $this->report,
            'generatedAt' => now(),
        ]);

        $pdf->save($path);
    }

    /**
     * Create a report file record
     *
     * @param  string  $filePath
     * @return void
     */
    protected function createReportFile($filePath)
    {
        $fileInfo = pathinfo($filePath);

        ReportFile::create([
            'report_id' => $this->report->id,
            'filename' => $fileInfo['basename'],
            'file_path' => $filePath,
            'file_size' => Storage::size($filePath),
            'mime_type' => $this->getMimeType($fileInfo['extension']),
            'generated_by' => $this->userId,
        ]);
    }

    /**
     * Get MIME type for a file extension
     *
     * @param  string  $extension
     * @return string
     */
    protected function getMimeType($extension)
    {
        $types = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
        ];

        return $types[strtolower($extension)] ?? 'application/octet-stream';
    }

    /**
     * Notify the user about the job status
     *
     * @param  string|null  $error
     * @return void
     */
    protected function notifyUser($error = null)
    {
        $user = \App\Models\User::find($this->userId);

        if (! $user) {
            return;
        }

        if ($error) {
            $user->notify(new \App\Notifications\ReportGenerationFailed(
                $this->report,
                'Failed to generate report: '.$error
            ));
        } else {
            $user->notify(new \App\Notifications\ReportGenerated(
                $this->report,
                'Your report is ready for download.'
            ));
        }
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        $this->report->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        $this->notifyUser($exception->getMessage());

        Log::error('Report generation job failed: '.$exception->getMessage(), [
            'report_id' => $this->report->id,
            'exception' => $exception,
        ]);
    }
}
