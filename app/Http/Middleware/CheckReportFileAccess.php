<?php

namespace App\Http\Middleware;

use App\Exceptions\ReportGenerationException;
use App\Models\ReportFile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckReportFileAccess
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $fileId = $request->route('file');

        try {
            $file = ReportFile::findOrFail($fileId);
            $user = $request->user();

            // Check if the file exists in storage
            if (! \Storage::disk('public')->exists($file->file_path)) {
                throw ReportGenerationException::notFound('The requested file does not exist or has been deleted.');
            }

            // Check if the file has expired
            if ($file->expires_at && $file->expires_at->isPast()) {
                throw ReportGenerationException::validationError('This file has expired and is no longer available for download.');
            }

            // Check if the user has access to the report
            if (! $user->can('view', $file->report)) {
                throw ReportGenerationException::validationError('You do not have permission to access this file.');
            }

            // Add the file to the request for use in the controller
            $request->attributes->add(['report_file' => $file]);

            return $next($request);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw ReportGenerationException::notFound('The requested file could not be found.');
        } catch (\Exception $e) {
            throw ReportGenerationException::validationError('Failed to process file download: '.$e->getMessage());
        }
    }
}
