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
use PhpOffice\PhpSpreadsheet\Style\Color;

class DepreciationScheduleExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data['depreciations']);
    }

    public function title(): string
    {
        return 'Depreciation Schedule';
    }

    public function headings(): array
    {
        return [
            'Asset ID',
            'Asset Name',
            'Category',
            'Purchase Date',
            'Purchase Cost',
            'Depreciation Method',
            'Useful Life (Years)',
            'Salvage Value',
            'Depreciation Date',
            'Depreciation Amount',
            'Accumulated Depreciation',
            'Book Value',
            'Remaining Life',
            'Depreciation Status',
        ];
    }

    public function map($depreciation): array
    {
        $asset = $depreciation->asset;
        $usefulLife = $asset->useful_life_years ?? 1;
        $remainingLife = max(0, $usefulLife - $depreciation->period);
        
        $depreciationStatus = 'In Progress';
        if ($remainingLife <= 0) {
            $depreciationStatus = 'Fully Depreciated';
        } elseif ($remainingLife / $usefulLife <= 0.25) {
            $depreciationStatus = 'Near End of Life';
        }

        return [
            $asset->id,
            $asset->name,
            $asset->category ? $asset->category->name : '',
            $asset->purchase_date,
            $asset->purchase_cost,
            $depreciation->method,
            $usefulLife,
            $depreciation->salvage_value,
            $depreciation->date,
            $depreciation->amount,
            $depreciation->accumulated_depreciation,
            $depreciation->book_value,
            $remainingLife,
            $depreciationStatus,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $this->data['depreciations']->count() + 3;
                
                // Set title and date range
                $sheet->mergeCells('A1:N1');
                $sheet->setCellValue('A1', 'Depreciation Schedule');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Add date range if provided
                if (!empty($this->data['date_from']) || !empty($this->data['date_to'])) {
                    $dateRange = 'Date Range: ';
                    $dateRange .= !empty($this->data['date_from']) ? 'From ' . $this->data['date_from'] . ' ' : '';
                    $dateRange .= !empty($this->data['date_to']) ? 'To ' . $this->data['date_to'] : '';
                    
                    $sheet->mergeCells('A2:N2');
                    $sheet->setCellValue('A2', $dateRange);
                    $sheet->getStyle('A2')->getFont()->setBold(true);
                    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                
                // Set headers
                $headerRow = !empty($this->data['date_from']) || !empty($this->data['date_to']) ? 3 : 2;
                $sheet->fromArray($this->headings(), null, 'A' . $headerRow);
                $sheet->getStyle('A' . $headerRow . ':N' . $headerRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $headerRow . ':N' . $headerRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');
                
                // Format currency columns
                $currencyColumns = ['E', 'J', 'K', 'L'];
                foreach ($currencyColumns as $col) {
                    $sheet->getStyle($col . ($headerRow + 1) . ':' . $col . $lastRow)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                }
                
                // Format date columns
                $dateColumns = ['D', 'I'];
                foreach ($dateColumns as $col) {
                    $sheet->getStyle($col . ($headerRow + 1) . ':' . $col . $lastRow)
                        ->getNumberFormat()
                        ->setFormatCode('yyyy-mm-dd');
                }
                
                // Format percentage columns
                $sheet->getStyle('M' . ($headerRow + 1) . ':M' . $lastRow)
                    ->getNumberFormat()
                    ->setFormatCode('0.00" years"');
                
                // Add conditional formatting for depreciation status
                $statusColumn = 'N';
                $startRow = $headerRow + 1;
                
                // Set colors based on status
                $colors = [
                    'Fully Depreciated' => 'FFC6EFCE',  // Light green
                    'Near End of Life' => 'FFFFEB9C',     // Light yellow
                    'In Progress' => 'FFBDD7EE',          // Light blue
                ];
                
                for ($i = $startRow; $i <= $lastRow; $i++) {
                    $cell = $statusColumn . $i;
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
                $sheet->getStyle('A' . $headerRow . ':N' . $lastRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                
                // Add totals
                $totalRow = $lastRow + 1;
                $sheet->setCellValue('D' . $totalRow, 'Totals:');
                $sheet->setCellValue('E' . $totalRow, '=SUBTOTAL(9,E' . ($headerRow + 1) . ':E' . $lastRow . ')');
                $sheet->setCellValue('J' . $totalRow, '=SUBTOTAL(9,J' . ($headerRow + 1) . ':J' . $lastRow . ')');
                $sheet->setCellValue('K' . $totalRow, '=SUBTOTAL(9,K' . ($headerRow + 1) . ':K' . $lastRow . ')');
                $sheet->setCellValue('L' . $totalRow, '=SUBTOTAL(9,L' . ($headerRow + 1) . ':L' . $lastRow . ')');
                
                // Style totals row
                $sheet->getStyle('D' . $totalRow . ':N' . $totalRow)
                    ->getFont()
                    ->setBold(true);
                $sheet->getStyle('D' . $totalRow . ':N' . $totalRow)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFE6E6E6');
                
                // Format total row currency
                foreach ($currencyColumns as $col) {
                    $sheet->getStyle($col . $totalRow)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                }
                
                // Add generated info
                $sheet->setCellValue('A' . ($totalRow + 2), 'Generated by: ' . $this->data['generated_by']);
                $sheet->setCellValue('A' . ($totalRow + 3), 'Generated at: ' . $this->data['generated_at']->format('Y-m-d H:i:s'));
                
                // Freeze panes
                $sheet->freezePane('A' . ($headerRow + 1));
                
                // Add filters
                $sheet->setAutoFilter('A' . $headerRow . ':N' . $lastRow);
                
                // Set row height for header
                $sheet->getRowDimension($headerRow)->setRowHeight(25);
                
                // Set alignment for all cells
                $sheet->getStyle('A1:N' . $lastRow)
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                    
                // Set text alignment for specific columns
                $centerAlignColumns = ['F', 'G', 'H', 'I', 'M', 'N'];
                foreach ($centerAlignColumns as $col) {
                    $sheet->getStyle($col . $headerRow . ':' . $col . $lastRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            },
        ];
    }
}
