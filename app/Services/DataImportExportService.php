<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DataImportExportService
{
    public function exportAssets($format = 'xlsx')
    {
        $assets = Asset::with(['model', 'status', 'assignedTo'])->get();

        $headers = [
            'ID', 'Asset Tag', 'Name', 'Model', 'Status', 'Assigned To',
            'Purchase Date', 'Purchase Cost', 'Warranty (Months)', 'Notes',
        ];

        $data = $assets->map(function ($asset) {
            return [
                $asset->id,
                $asset->asset_tag,
                $asset->name,
                $asset->model ? $asset->model->name : 'N/A',
                $asset->status ? $asset->status->name : 'N/A',
                $asset->assignedTo ? $asset->assignedTo->name : 'Unassigned',
                $asset->purchase_date,
                $asset->purchase_cost,
                $asset->warranty_months,
                $asset->notes,
            ];
        })->toArray();

        if ($format === 'csv') {
            return $this->exportToCsv($data, $headers, 'assets-export-'.now()->format('Y-m-d'));
        }

        return $this->exportToExcel($data, $headers, 'assets-export-'.now()->format('Y-m-d'));
    }

    public function importAssets(UploadedFile $file)
    {
        $extension = $file->getClientOriginalExtension();

        if (! in_array($extension, ['xlsx', 'xls', 'csv'])) {
            throw new \Exception('Invalid file type. Only Excel and CSV files are allowed.');
        }

        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Remove header row
        $header = array_shift($rows);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $data = array_combine($header, $row);

                // Skip empty rows
                if (empty(array_filter($data))) {
                    $skipped++;

                    continue;
                }

                // Find or create the asset model
                $model = AssetModel::firstOrCreate(
                    ['name' => $data['Model']],
                    ['name' => $data['Model']]
                );

                // Create or update the asset
                Asset::updateOrCreate(
                    ['asset_tag' => $data['Asset Tag']],
                    [
                        'name' => $data['Name'],
                        'model_id' => $model->id,
                        'purchase_date' => $data['Purchase Date'],
                        'purchase_cost' => $data['Purchase Cost'],
                        'warranty_months' => $data['Warranty (Months)'] ?? null,
                        'notes' => $data['Notes'] ?? null,
                    ]
                );

                $imported++;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $index + 2, // +2 because of 0-based index and header row
                    'error' => $e->getMessage(),
                ];
                $skipped++;
                Log::error('Asset import error on row '.($index + 2).': '.$e->getMessage());
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    protected function exportToCsv($data, $headers, $filename)
    {
        $filename = $filename.'.csv';
        $handle = fopen('php://output', 'w');

        // Add UTF-8 BOM for proper encoding in Excel
        fwrite($handle, "\xEF\xBB\xBF");

        // Add headers
        fputcsv($handle, $headers);

        // Add data
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return response()->stream(
            function () {
                flush();
            },
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    protected function exportToExcel($data, $headers, $filename)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Add headers
        $sheet->fromArray([$headers], null, 'A1');

        // Add data
        $sheet->fromArray($data, null, 'A2');

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Style the header row
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFDDDDDD'],
            ],
        ]);

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename.'.xlsx')->deleteFileAfterSend(true);
    }
}
