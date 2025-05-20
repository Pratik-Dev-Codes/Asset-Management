<?php

namespace App\Services;

use App\Exports\ReportExport;
use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use App\Notifications\ReportGenerated;
use App\Notifications\ReportGenerationFailed;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function app;

class ReportExportService
{
    /**
     * Default export formats.
     *
     * @var array
     */
    protected $allowedFormats = ['xlsx', 'csv', 'pdf'];

    /**
     * Maximum number of rows to process in a single chunk.
     *
     * @var int
     */
    protected $chunkSize = 1000;

    /**
     * Default number of days before report files expire.
     *
     * @var int
     */
    protected $defaultExpiryDays = 7;

    /**
     * Queue name for export jobs.
     *
     * @var string
     */
    protected $queueName = 'reports';

    /**
     * Export a report to the specified format.
     *
     * @return ReportFile|bool
     */
    public function export(Report $report, string $format, User $user, bool $queue = true)
    {
        try {
            // Validate the export format
            $format = strtolower($format);
            if (! in_array($format, $this->allowedFormats)) {
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
            }

            // If queue is enabled, dispatch a job
            if ($queue) {
                GenerateReportJob::dispatch($report, $format, $user->id)
                    ->onQueue($this->queueName);

                return true;
            }

            // Otherwise, process synchronously
            return $this->generateExport($report, $format, $user);

        } catch (\Exception $e) {
            Log::error('Failed to export report', [
                'report_id' => $report->id,
                'format' => $format,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Notify the user about the failure
            $user->notify(new ReportGenerationFailed(
                $report,
                'Failed to generate report: '.$e->getMessage()
            ));

            throw $e;
        }
    }

    /**
     * Generate the export file.
     *
     * @throws \Exception
     */
    protected function generateExport(Report $report, string $format, User $user): ReportFile
    {
        // Generate the report data
        $data = $report->generateData();

        // Generate a unique file name
        $fileName = $this->generateFileName($report, $format);
        $filePath = "reports/{$report->id}/{$fileName}";
        $fullPath = storage_path("app/public/{$filePath}");

        // Ensure the directory exists
        Storage::makeDirectory("public/reports/{$report->id}");

        // Generate the file based on the format
        switch ($format) {
            case 'xlsx':
            case 'csv':
                $this->exportToExcel($data, $report, $filePath, $format);
                break;

            case 'pdf':
                $this->exportToPdf($data, $report, $fullPath);
                break;
        }

        // Create a record of the exported file
        $file = new ReportFile([
            'report_id' => $report->id,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $format,
            'file_size' => Storage::size("public/{$filePath}"),
            'generated_by' => $user->id,
            'expires_at' => now()->addDays($this->defaultExpiryDays),
        ]);

        $report->files()->save($file);
        $report->update(['last_generated_at' => now()]);

        // Clean up old report files
        $this->cleanupOldReports($report);

        return $file;
    }

    /**
     * Export data to Excel format.
     */
    /**
     * Export data to Excel format.
     */
    protected function exportToExcel(array $data, Report $report, string $filePath, string $format): void
    {
        try {
            // Ensure we have the correct class name with namespace
            $exportClass = 'App\Exports\ReportExport';

            if (! class_exists($exportClass)) {
                throw new \RuntimeException("Export class {$exportClass} not found");
            }

            $export = new $exportClass(
                $data,
                $report->columns ?? [],
                $report->name,
                $report->description
            );

            Excel::store(
                $export,
                $filePath,
                'public',
                $format === 'xlsx' ? ExcelFormat::XLSX : ExcelFormat::CSV
            );

        } catch (\Exception $e) {
            Log::error('Failed to generate Excel export', [
                'report_id' => $report->id,
                'format' => $format,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Failed to generate Excel export: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Export data to PDF format.
     */
    /**
     * Export data to PDF format.
     */
    protected function exportToPdf(array $data, Report $report, string $fullPath): void
    {
        try {
            // Get the PDF facade instance
            $pdf = app('dompdf.wrapper');

            // Load the view and set options
            $pdf->loadView('reports.exports.pdf', [
                'data' => $data,
                'columns' => $report->columns,
                'report' => $report,
                'generatedAt' => now(),
            ]);

            // Set paper size and orientation
            $pdf->setPaper('a4', 'landscape');

            // Save the PDF to the specified path
            $pdf->save($fullPath);

        } catch (\Exception $e) {
            Log::error('Failed to generate PDF export', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Failed to generate PDF export: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate a file name for the export.
     */
    protected function generateFileName(Report $report, string $extension): string
    {
        $name = Str::slug($report->name);
        $timestamp = now()->format('Y-m-d_His');

        return "{$name}_{$timestamp}.{$extension}";
    }

    /**
     * Clean up old report files, keeping only the most recent ones.
     */
    public function cleanupOldReports(Report $report, int $keepCount = 5): void
    {
        // Get all files for this report, ordered by creation date (newest first)
        $files = $report->files()
            ->orderBy('created_at', 'desc')
            ->get();

        // If we have more files than we want to keep, delete the older ones
        if ($files->count() > $keepCount) {
            $filesToDelete = $files->slice($keepCount);

            foreach ($filesToDelete as $file) {
                // Delete the file from storage
                if (Storage::exists("public/{$file->file_path}")) {
                    Storage::delete("public/{$file->file_path}");
                }

                // Delete the database record
                $file->delete();
            }
        }
    }

    /**
     * Get the download URL for a report file.
     */
    public function getDownloadUrl(ReportFile $file): string
    {
        return Storage::url($file->file_path);
    }

    /**
     * Get the storage path for a report file.
     */
    public function getStoragePath(ReportFile $file): string
    {
        return storage_path("app/public/{$file->file_path}");
    }

    /**
     * Delete expired report files.
     *
     * @return int Number of deleted files
     */
    public function deleteExpiredFiles(): int
    {
        $count = 0;
        $expiredFiles = ReportFile::where('expires_at', '<=', now())->get();

        foreach ($expiredFiles as $file) {
            try {
                // Delete the file from storage
                if (Storage::exists("public/{$file->file_path}")) {
                    Storage::delete("public/{$file->file_path}");
                }

                // Delete the database record
                $file->delete();
                $count++;

            } catch (\Exception $e) {
                Log::error('Failed to delete expired report file', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
