<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomReportExport implements FromCollection, WithEvents, WithHeadings, WithMapping, WithTitle
{
    protected $data;

    protected $columns;

    protected $reportType;

    protected $headings = [];

    public function __construct($data, array $columns, string $reportType)
    {
        $this->data = $data;
        $this->columns = $columns;
        $this->reportType = $reportType;
        $this->setHeadings();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->data);
    }

    /**
     * Set the headings based on the selected columns and report type
     */
    protected function setHeadings(): void
    {
        $columnMap = [
            'name' => 'Asset Name',
            'asset_tag' => 'Asset Tag',
            'serial_number' => 'Serial Number',
            'category' => 'Category',
            'status' => 'Status',
            'location' => 'Location',
            'department' => 'Department',
            'assigned_to' => 'Assigned To',
            'purchase_date' => 'Purchase Date',
            'purchase_cost' => 'Purchase Cost',
            'current_value' => 'Current Value',
            'warranty_expiry' => 'Warranty Expiry',
            'notes' => 'Notes',
        ];

        // Add report type specific columns
        if ($this->reportType === 'financial') {
            $columnMap['depreciation'] = 'Depreciation';
            $columnMap['maintenance_costs'] = 'Maintenance Costs';
        } elseif ($this->reportType === 'maintenance') {
            $columnMap['last_maintenance_date'] = 'Last Maintenance Date';
            $columnMap['maintenance_count'] = 'Maintenance Count';
        } elseif ($this->reportType === 'compliance') {
            $columnMap['is_warranty_valid'] = 'Warranty Valid';
            $columnMap['last_inspection'] = 'Last Inspection';
        }

        // Filter and order the headings based on selected columns
        foreach ($this->columns as $column) {
            if (isset($columnMap[$column])) {
                $this->headings[] = $columnMap[$column];
            }
        }

        // Add report type specific columns if they exist in the data
        if ($this->reportType === 'financial') {
            $this->headings[] = 'Depreciation';
            $this->headings[] = 'Maintenance Costs';
        } elseif ($this->reportType === 'maintenance') {
            $this->headings[] = 'Last Maintenance Date';
            $this->headings[] = 'Maintenance Count';
        } elseif ($this->reportType === 'compliance') {
            $this->headings[] = 'Warranty Valid';
            $this->headings[] = 'Last Inspection';
        }
    }

    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * @param  mixed  $row
     */
    public function map($row): array
    {
        $mappedRow = [];

        // Map the selected columns
        foreach ($this->columns as $column) {
            $mappedRow[] = $row[$column] ?? '';
        }

        // Add report type specific data
        if ($this->reportType === 'financial') {
            $mappedRow[] = $row['depreciation'] ?? 0;
            $mappedRow[] = $row['maintenance_costs'] ?? 0;
        } elseif ($this->reportType === 'maintenance') {
            $mappedRow[] = $row['last_maintenance_date'] ?? 'N/A';
            $mappedRow[] = $row['maintenance_count'] ?? 0;
        } elseif ($this->reportType === 'compliance') {
            $mappedRow[] = $row['is_warranty_valid'] ?? 'N/A';
            $mappedRow[] = $row['last_inspection'] ?? 'N/A';
        }

        return $mappedRow;
    }

    public function title(): string
    {
        return ucfirst($this->reportType).' Report';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Set auto size for all columns
                foreach (range('A', $sheet->getHighestColumn()) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Style the header row
                $headerRange = 'A1:'.$sheet->getHighestColumn().'1';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F81BD'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Style all cells
                $dataRange = 'A1:'.$sheet->getHighestColumn().$sheet->getHighestRow();
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_TOP,
                        'wrapText' => true,
                    ],
                ]);

                // Format currency columns
                $currencyColumns = [];
                $numericColumns = [];

                foreach ($this->columns as $index => $column) {
                    if (in_array($column, ['purchase_cost', 'current_value', 'depreciation', 'maintenance_costs'])) {
                        $currencyColumns[] = $this->getColumnLetter($index + 1);
                    } elseif (in_array($column, ['maintenance_count'])) {
                        $numericColumns[] = $this->getColumnLetter($index + 1);
                    }
                }

                // Apply currency format
                foreach ($currencyColumns as $col) {
                    $sheet->getStyle($col.'2:'.$col.$sheet->getHighestRow())
                        ->getNumberFormat()
                        ->setFormatCode('$#,##0.00');
                }

                // Apply number format
                foreach ($numericColumns as $col) {
                    $sheet->getStyle($col.'2:'.$col.$sheet->getHighestRow())
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                }

                // Freeze the first row
                $sheet->freezePane('A2');

                // Set print settings
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
            },
        ];
    }

    /**
     * Convert column number to letter (e.g., 1 -> A, 27 -> AA)
     */
    protected function getColumnLetter(int $number): string
    {
        $letter = '';
        while ($number > 0) {
            $temp = ($number - 1) % 26;
            $letter = chr(65 + $temp).$letter;
            $number = intval(($number - $temp) / 26);
        }

        return $letter ?: 'A';
    }
}
