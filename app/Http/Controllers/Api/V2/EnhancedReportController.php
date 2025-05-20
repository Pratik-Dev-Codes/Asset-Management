<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportExportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnhancedReportController extends Controller
{
    /**
     * @var ReportExportService
     */
    protected $reportService;

    public function __construct(ReportExportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('auth:api');
        $this->middleware('validate.report')->only(['export']);
    }

    /**
     * Export a report in the specified format
     *
     * @return \Illuminate\Http\JsonResponse|StreamedResponse
     */
    public function export(Request $request)
    {
        try {
            $user = Auth::user();
            $reportType = $request->input('report_type');
            $format = $request->input('format', 'xlsx');
            $filters = $request->input('filters', []);
            $columns = $request->input('columns');

            // Generate a unique cache key for this report
            $cacheKey = $this->generateCacheKey($reportType, $filters, $columns, $format);

            // Check if the report is already in cache
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Create a report record
            $report = $this->createReport($user, $reportType, $filters, $columns);

            // Generate the export
            $result = $this->reportService->export(
                $report,
                $format,
                $user,
                $request->boolean('queue', true) // Default to queued
            );

            // Cache the response for 1 hour
            $response = response()->json([
                'success' => true,
                'message' => 'Report generation started',
                'report_id' => $report->id,
                'status_url' => route('api.v1.reports.status', $report->id),
            ]);

            Cache::put($cacheKey, $response, now()->addHour());

            return $response;

        } catch (\Exception $e) {
            Log::error('Report export failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: '.$e->getMessage(),
                'error_code' => 'REPORT_EXPORT_ERROR',
            ], 500);
        }
    }

    /**
     * Check the status of a report generation
     *
     * @param  int  $reportId
     * @return \Illuminate\Http\JsonResponse
     */
    public function status($reportId)
    {
        try {
            $report = Report::findOrFail($reportId);

            // Check if user has permission to view this report
            if (Gate::denies('view', $report)) {
                throw new AuthorizationException('You are not authorized to view this report.');
            }

            return response()->json([
                'success' => true,
                'status' => $report->status,
                'download_url' => $report->status === 'completed'
                    ? route('api.v1.reports.download', $report->id)
                    : null,
                'progress' => $report->progress ?? 0,
                'message' => $this->getStatusMessage($report->status),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get report status: '.$e->getMessage(),
                'error_code' => 'REPORT_STATUS_ERROR',
            ], 500);
        }
    }

    /**
     * Download a generated report
     *
     * @param  int  $reportId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download($reportId)
    {
        try {
            $report = Report::with('latestFile')->findOrFail($reportId);

            // Check if user has permission to download this report
            if (Gate::denies('download', $report)) {
                throw new AuthorizationException('You are not authorized to download this report.');
            }

            if ($report->status !== 'completed' || ! $report->latestFile) {
                abort(404, 'Report not available for download');
            }

            $filePath = storage_path('app/'.$report->latestFile->file_path);

            if (! file_exists($filePath)) {
                Log::error("Report file not found: {$filePath}");
                abort(404, 'Report file not found');
            }

            return response()->download(
                $filePath,
                $report->latestFile->filename,
                ['Content-Type' => $report->latestFile->mime_type]
            )->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Report download failed: '.$e->getMessage());
            abort(500, 'Failed to download report');
        }
    }

    /**
     * Generate a cache key for the report
     *
     * @param  string  $reportType
     * @param  array  $filters
     * @param  array  $columns
     * @param  string  $format
     * @return string
     */
    protected function generateCacheKey($reportType, $filters, $columns, $format)
    {
        return sprintf(
            'report_%s_%s_%s_%s',
            $reportType,
            md5(json_encode($filters)),
            md5(json_encode($columns)),
            $format
        );
    }

    /**
     * Create a new report record
     *
     * @param  string  $type
     * @param  array  $filters
     * @param  array  $columns
     * @return Report
     */
    protected function createReport(User $user, $type, $filters, $columns)
    {
        return Report::create([
            'name' => ucfirst($type).' Report - '.now()->format('Y-m-d H:i:s'),
            'description' => 'Generated report',
            'type' => $type,
            'filters' => $filters,
            'columns' => $columns,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);
    }

    /**
     * Get a user-friendly status message
     *
     * @param  string  $status
     * @return string
     */
    protected function getStatusMessage($status)
    {
        $messages = [
            'pending' => 'Your report is in the queue and will be generated shortly.',
            'processing' => 'Your report is being generated. This may take a few minutes.',
            'completed' => 'Your report is ready for download.',
            'failed' => 'Failed to generate report. Please try again later.',
        ];

        return $messages[$status] ?? 'Unknown status';
    }
}
