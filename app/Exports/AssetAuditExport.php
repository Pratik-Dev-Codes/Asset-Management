<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AssetAuditExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data['assets']);
    }

    public function title(): string
    {
        return 'Asset Audit Report';
    }

    public function headings(): array
    {
        return [
            'Asset ID',
            'Asset Code',
            'Name',
            'Category',
            'Serial Number',
            'Model',
            'Status',
            'Condition',
            'Location',
            'Department',
            'Assigned To',
            'Purchase Date',
            'Purchase Cost',
            'Current Value',
            'Warranty Expiry',
            'Last Audited',
            'Audit Status',
            'Notes',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->id,
            $asset->asset_code,
            $asset->name,
            $asset->category ? $asset->category->name : '',
            $asset->serial_number,
            $asset->model,
            $asset->status,
            $asset->condition,
            $asset->location ? $asset->location->name : '',
            $asset->department ? $asset->department->name : '',
            $asset->assignedTo ? $asset->assignedTo->name : '',
            $asset->purchase_date,
            $asset->purchase_cost,
            $asset->current_value,
            $asset->warranty_expiry_date,
            $asset->last_audit_date,
            $asset->audit_status,
            $asset->notes,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $this->data['assets']->count() + 3;
                
                // Set title and date range
                $sheet->mergeCells('A1:R1');
                $sheet->setCellValue('A1', 'Asset Audit Report');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Add date range if provided
                if (!empty($this->data['date_from']) || !empty($this->data['date_to'])) {
                    $dateRange = 'Date Range: ';
                    $dateRange .= !empty($this->data['date_from']) ? 'From ' . $this->data['date_from'] . ' ' : '';
                    $dateRange .= !empty($this->data['date_to']) ? 'To ' . $this->data['date_to'] : '';
                    
                    $sheet->mergeCells('A2:R2');
                    $sheet->setCellValue('A2', $dateRange);
                    $sheet->getStyle('A2')->getFont()->setBold(true);
                    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                
                // Set headers
                $headerRow = !empty($this->data['date_from']) || !empty($this->data['date_to']) ? 3 : 2;
                $sheet->fromArray($this->headings(), null, 'A' . $headerRow);
                $sheet->getStyle('A' . $headerRow . ':R' . $headerRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $headerRow . ':R' . $headerRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');
                
                // Format currency columns
                $currencyColumns = ['M', 'N'];
                foreach ($currencyColumns as $col) {
                    $sheet->getStyle($col . ($headerRow + 1) . ':' . $col . $lastRow)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                }
                
                // Format date columns
                $dateColumns = ['L', 'O', 'P'];
                foreach ($dateColumns as $col) {
                    $sheet->getStyle($col . ($headerRow + 1) . ':' . $col . $lastRow)
                        ->getNumberFormat()
                        ->setFormatCode('yyyy-mm-dd');
                }
                
                // Add conditional formatting for audit status
                $auditStatusColumn = 'Q';
                $startRow = $headerRow + 1;
                
                // Set colors based on audit status
                $colors = [
                    'Passed' => 'FFC6EFCE',  // Light green
                    'Failed' => 'FFFFC7CE',   // Light red
                    'Needs Attention' => 'FFFFEB9C', // Light yellow
                    'Not Audited' => 'FFD9D9D9', // Light gray
                ];
                
                for ($i = $startRow; $i <= $lastRow; $i++) {
                    $cell = $auditStatusColumn . $i;
                    $status = $sheet->getCell($cell)->getValue();
                    if (isset($colors[$status])) {
                        $sheet->getStyle($cell)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setARGB($colors[$status]);
                    }
                }
                
                // Add borders
                $sheet->getStyle('A' . $headerRow . ':R' . $lastRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                
                // Add generated info
                $sheet->setCellValue('A' . ($lastRow + 2), 'Generated by: ' . $this->data['generated_by']);
                $sheet->setCellValue('A' . ($lastRow + 3), 'Generated at: ' . $this->data['generated_at']->format('Y-m-d H:i:s'));
                
                // Freeze panes
                $sheet->freezePane('A' . ($headerRow + 1));
                
                // Add filters
                $sheet->setAutoFilter('A' . $headerRow . ':R' . $lastRow);
                
                // Set row height for header
                $sheet->getRowDimension($headerRow)->setRowHeight(25);
                
                // Set alignment for all cells
                $sheet->getStyle('A1:R' . $lastRow)
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                
                // Set wrap text for notes column
                $sheet->getStyle('R' . ($headerRow + 1) . ':R' . $lastRow)
                    ->getAlignment()
                    ->setWrapText(true);
            },
        ];
    }
}
