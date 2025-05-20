<?php

namespace App\Services;

use App\Exports\AssetsExport;
use App\Models\Asset;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

class ReportExporter
{
    /**
     * Export data to the specified format
     *
     * @param  string  $format  pdf, xlsx, csv
     * @param  array  $data
     * @param  string|null  $filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response|string
     */
    public function export($format, $data, array $columns, $filename = null)
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('No data provided for export');
        }

        if (empty($columns)) {
            throw new \InvalidArgumentException('No columns provided for export');
        }

        $filename = $this->generateFilename($filename);
        $format = strtolower($format);

        switch ($format) {
            case 'pdf':
                return $this->exportToPdf($data, $columns, $filename);
            case 'xlsx':
            case 'xls':
                return $this->exportToExcel($data, $columns, $filename, $format);
            case 'csv':
                return $this->exportToCsv($data, $columns, $filename);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Generate a filename with timestamp
     *
     * @param  string|null  $baseName
     * @return string
     */
    protected function generateFilename($baseName = null)
    {
        $baseName = $baseName ?: 'export';
        $baseName = Str::slug($baseName);
        $timestamp = now()->format('Y-m-d_His');

        return "{$baseName}_{$timestamp}";
    }

    /**
     * Export data to PDF format
     *
     * @param  array  $data
     * @param  string  $filename
     * @return \Illuminate\Http\Response
     */
    protected function exportToPdf($data, array $columns, $filename)
    {
        try {
            $pdf = PDF::loadView('exports.pdf.report', [
                'data' => $data,
                'columns' => $columns,
                'title' => 'Asset Management Report',
                'date' => now()->format('F j, Y'),
                'orientation' => 'landscape',
            ]);

            $pdf->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'dpi' => 96,
                    'defaultFont' => 'sans-serif',
                ]);

            return $pdf->download($filename.'.pdf');

        } catch (\Exception $e) {
            Log::error('PDF Export Error: '.$e->getMessage());
            throw new \RuntimeException('Failed to generate PDF: '.$e->getMessage());
        }
    }

    protected function exportToExcel($data, $columns, $filename, $format = 'xlsx')
    {
        try {
            $export = new AssetsExport($data, $columns);

            return ExcelFacade::download(
                $export,
                "{$filename}.{$format}",
                strtoupper($format)
            );
        } catch (\Exception $e) {
            Log::error('Excel Export Error: '.$e->getMessage());
            throw new \RuntimeException('Failed to generate Excel file: '.$e->getMessage());
        }
    }

    protected function exportToCsv($data, $columns, $filename)
    {
        try {
            $headers = array_column($columns, 'label');

            $callback = function () use ($data, $columns, $headers) {
                $file = fopen('php://output', 'w');

                // Add BOM for Excel compatibility
                fwrite($file, chr(0xEF).chr(0xBB).chr(0xBF));

                // Add headers
                fputcsv($file, $headers);

                // Add data
                foreach ($data as $row) {
                    $csvRow = [];
                    foreach ($columns as $column) {
                        $csvRow[] = $row[$column['key']] ?? '';
                    }
                    fputcsv($file, $csvRow);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ]);

        } catch (\Exception $e) {
            Log::error('CSV Export Error: '.$e->getMessage());
            throw new \RuntimeException('Failed to generate CSV file: '.$e->getMessage());
        }
    }
}
