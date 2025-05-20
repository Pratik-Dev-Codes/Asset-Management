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

class MaintenanceHistoryExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data['maintenances']);
    }

    public function title(): string
    {
        return 'Maintenance History';
    }

    public function headings(): array
    {
        return [
            'Work Order #',
            'Asset ID',
            'Asset Name',
            'Category',
            'Maintenance Type',
            'Title',
            'Priority',
            'Status',
            'Scheduled Date',
            'Start Date',
            'Completion Date',
            'Days Open',
            'Assigned To',
            'Vendor',
            'Labor Cost',
            'Parts Cost',
            'Total Cost',
            'Description',
            'Resolution',
            'Created By',
            'Created At',
            'Updated At',
        ];
    }

    public function map($maintenance): array
    {
        $asset = $maintenance->asset;
        $startDate = $maintenance->start_date ? \Carbon\Carbon::parse($maintenance->start_date) : null;
        $completionDate = $maintenance->completion_date ? \Carbon\Carbon::parse($maintenance->completion_date) : null;
        $scheduledDate = $maintenance->scheduled_date ? \Carbon\Carbon::parse($maintenance->scheduled_date) : null;
        $createdAt = $maintenance->created_at ? $maintenance->created_at->format('Y-m-d H:i:s') : '';
        $updatedAt = $maintenance->updated_at ? $maintenance->updated_at->format('Y-m-d H:i:s') : '';
        
        $daysOpen = 'N/A';
        if ($startDate && $completionDate) {
            $daysOpen = $startDate->diffInDays($completionDate);
        } elseif ($startDate && !$completionDate && in_array($maintenance->status, ['In Progress', 'On Hold'])) {
            $daysOpen = $startDate->diffInDays(now());
        }

        return [
            $maintenance->work_order_number,
            $asset ? $asset->id : 'N/A',
            $asset ? $asset->name : 'Asset Deleted',
            $asset && $asset->category ? $asset->category->name : 'N/A',
            $maintenance->maintenance_type,
            $maintenance->title,
            $maintenance->priority,
            $maintenance->status,
            $scheduledDate ? $scheduledDate->format('Y-m-d') : 'N/A',
            $startDate ? $startDate->format('Y-m-d H:i') : 'N/A',
            $completionDate ? $completionDate->format('Y-m-d H:i') : 'N/A',
            $daysOpen,
            $maintenance->assignedTo ? $maintenance->assignedTo->name : 'Unassigned',
            $maintenance->vendor ? $maintenance->vendor->name : 'N/A',
            $maintenance->labor_cost,
            $maintenance->parts_cost,
            $maintenance->total_cost,
            $maintenance->description,
            $maintenance->resolution,
            $maintenance->createdBy ? $maintenance->createdBy->name : 'System',
            $createdAt,
            $updatedAt,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $this->data['maintenances']->count() + 3;
                
                // Set title and date range
                $sheet->mergeCells('A1:V1');
                $sheet->setCellValue('A1', 'Maintenance History Report');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Add date range if provided
                if (!empty($this->data['date_from']) || !empty($this->data['date_to'])) {
                    $dateRange = 'Date Range: ';
                    $dateRange .= !empty($this->data['date_from']) ? 'From ' . $this->data['date_from'] . ' ' : '';
                    $dateRange .= !empty($this->data['date_to']) ? 'To ' . $this->data['date_to'] : '';
                    
                    $sheet->mergeCells('A2:V2');
                    $sheet->setCellValue('A2', $dateRange);
                    $sheet->getStyle('A2')->getFont()->setBold(true);
                    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                
                // Set headers
                $headerRow = !empty($this->data['date_from']) || !empty($this->data['date_to']) ? 3 : 2;
                $sheet->fromArray($this->headings(), null, 'A' . $headerRow);
                $sheet->getStyle('A' . $headerRow . ':V' . $headerRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $headerRow . ':V' . $headerRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');
                
                // Format currency columns
                $currencyColumns = ['O', 'P', 'Q'];
                foreach ($currencyColumns as $col) {
                    $sheet->getStyle($col . ($headerRow + 1) . ':' . $col . $lastRow)
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                }
                
                // Format date columns
                $dateColumns = ['I', 'J', 'K', 'U', 'V'];
                foreach ($dateColumns as $col) {
                    $sheet->getStyle($col . ($headerRow + 1) . ':' . $col . $lastRow)
                        ->getNumberFormat()
                        ->setFormatCode('yyyy-mm-dd hh:mm');
                }
                
                // Add conditional formatting for status
                $statusColumn = 'H';
                $priorityColumn = 'G';
                $startRow = $headerRow + 1;
                
                // Set colors based on status
                $statusColors = [
                    'Completed' => 'FFC6EFCE',  // Light green
                    'In Progress' => 'FFBDD7EE', // Light blue
                    'On Hold' => 'FFFFEB9C',     // Light yellow
                    'Cancelled' => 'FFD9D9D9',   // Light gray
                    'Open' => 'FFFFC7CE',        // Light red
                ];
                
                // Set colors based on priority
                $priorityColors = [
                    'High' => 'FFFFC7CE',       // Light red
                    'Medium' => 'FFFFEB9C',      // Light yellow
                    'Low' => 'FFC6EFCE',         // Light green
                ];
                
                for ($i = $startRow; $i <= $lastRow; $i++) {
                    // Status formatting
                    $statusCell = $statusColumn . $i;
                    $status = $sheet->getCell($statusCell)->getValue();
                    if (isset($statusColors[$status])) {
                        $sheet->getStyle($statusCell)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setARGB($statusColors[$status]);
                    }
                    
                    // Priority formatting
                    $priorityCell = $priorityColumn . $i;
                    $priority = $sheet->getCell($priorityCell)->getValue();
                    if (isset($priorityColors[$priority])) {
                        $sheet->getStyle($priorityCell)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()
                            ->setARGB($priorityColors[$priority]);
                    }
                }
                
                // Add borders
                $sheet->getStyle('A' . $headerRow . ':V' . $lastRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
                
                // Add totals
                $totalRow = $lastRow + 1;
                $sheet->setCellValue('N' . $totalRow, 'Totals:');
                $sheet->setCellValue('O' . $totalRow, '=SUBTOTAL(9,O' . ($headerRow + 1) . ':O' . $lastRow . ')');
                $sheet->setCellValue('P' . $totalRow, '=SUBTOTAL(9,P' . ($headerRow + 1) . ':P' . $lastRow . ')');
                $sheet->setCellValue('Q' . $totalRow, '=SUBTOTAL(9,Q' . ($headerRow + 1) . ':Q' . $lastRow . ')');
                
                // Style totals row
                $sheet->getStyle('N' . $totalRow . ':V' . $totalRow)
                    ->getFont()
                    ->setBold(true);
                $sheet->getStyle('N' . $totalRow . ':V' . $totalRow)
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
                $sheet->setAutoFilter('A' . $headerRow . ':V' . $lastRow);
                
                // Set row height for header
                $sheet->getRowDimension($headerRow)->setRowHeight(25);
                
                // Set alignment for all cells
                $sheet->getStyle('A1:V' . $lastRow)
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
                    
                // Set text alignment for specific columns
                $centerAlignColumns = ['A', 'B', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];
                foreach ($centerAlignColumns as $col) {
                    $sheet->getStyle($col . $headerRow . ':' . $col . $lastRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                
                // Set wrap text for description and resolution columns
                $sheet->getStyle('R' . ($headerRow + 1) . ':S' . $lastRow)
                    ->getAlignment()
                    ->setWrapText(true);
                    
                // Auto-size rows for wrapped text
                foreach (range($headerRow + 1, $lastRow) as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(-1);
                }
            },
        ];
    }
}
