<?php

namespace App\Http\Traits;

use App\Exceptions\ReportGenerationException;
use App\Models\Report;
use App\Services\ReportExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Trait for handling report exports
 */
trait HandlesReportExports
{
    /**
     * Export a report to the specified format.
     *
     * @return \Illuminate\Http\JsonResponse|StreamedResponse
     */
    protected function exportReport(Report $report, string $format, bool $queue = false)
    {
        try {
            $user = auth()->user();
            $exportService = app(ReportExportService::class);

            // Validate export format
            if (! in_array($format, ['xlsx', 'csv', 'pdf'])) {
                throw ReportGenerationException::validationError(
                    'Invalid export format. Supported formats: xlsx, csv, pdf.'
                );
            }

            // If queue is requested, dispatch the job
            if ($queue) {
                $exportService->export($report, $format, $user, true);

                return response()->json([
                    'success' => true,
                    'message' => 'Report export has been queued. You will be notified when it\'s ready.',
                    'data' => [
                        'report_id' => $report->id,
                        'format' => $format,
                        'queued' => true,
                    ],
                ]);
            }

            // Otherwise, generate the export immediately
            $file = $exportService->export($report, $format, $user, false);

            return $this->downloadExportFile($file, $format);

        } catch (\Exception $e) {
            throw ReportGenerationException::validationError(
                'Failed to export report: '.$e->getMessage()
            );
        }
    }

    /**
     * Download the exported file.
     *
     * @param  \App\Models\ReportFile  $file
     */
    protected function downloadExportFile($file, string $format): StreamedResponse
    {
        $headers = [
            'Content-Type' => $this->getMimeType($format),
            'Content-Disposition' => 'attachment; filename="'.$file->file_name.'"',
        ];

        return response()->stream(
            function () use ($file) {
                $stream = \Storage::disk('public')->readStream($file->file_path);
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            },
            200,
            $headers
        );
    }

    /**
     * Get the MIME type for the given format.
     */
    protected function getMimeType(string $format): string
    {
        $mimeTypes = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
        ];

        return $mimeTypes[$format] ?? 'application/octet-stream';
    }

    /**
     * Get the export status for a report.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function getExportStatus(Report $report)
    {
        $exports = $report->files()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'file_name' => $file->file_name,
                    'file_type' => $file->file_type,
                    'file_size' => $file->file_size,
                    'download_url' => route('reports.download', $file->id),
                    'created_at' => $file->created_at->toDateTimeString(),
                    'expires_at' => $file->expires_at?->toDateTimeString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'report_id' => $report->id,
                'exports' => $exports,
                'has_exports' => $exports->isNotEmpty(),
            ],
        ]);
    }
}
