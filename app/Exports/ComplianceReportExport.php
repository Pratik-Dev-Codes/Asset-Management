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

class ComplianceReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithEvents
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
        return 'Compliance Report';
    }

    public function headings(): array
    {
        return [
            'Asset ID',
            'Asset Name',
            'Category',
            'Serial Number',
            'Status',
            'Warranty Expiry',
            'Days Until Warranty Expires',
            'Last Maintenance',
            'Next Maintenance Due',
            'Days Until Next Maintenance',
            'Compliance Status',
        ];
    }

    public function map($asset): array
    {
        $warrantyExpiry = $asset->warranty ? $asset->warranty->expires_at : null;
        $lastMaintenance = $asset->maintenances->sortByDesc('completed_date')->first();
        $nextMaintenance = $asset->maintenances->where('scheduled_date', '>=', now())->sortBy('scheduled_date')->first();
        
        $daysUntilWarrantyExpires = $warrantyExpiry ? now()->diffInDays($warrantyExpiry, false) : null;
        $daysUntilNextMaintenance = $nextMaintenance ? now()->diffInDays($nextMaintenance->scheduled_date, false) : null;
        
        $complianceStatus = 'Compliant';
        if (($warrantyExpiry && $daysUntilWarrantyExpires <= 90) || 
            ($nextMaintenance && $daysUntilNextMaintenance <= 30)) {
            $complianceStatus = 'Attention Needed';
        }
        if (($warrantyExpiry && $daysUntilWarrantyExpires < 0) || 
            ($nextMaintenance && $daysUntilNextMaintenance < 0)) {
            $complianceStatus = 'Non-Compliant';
        }

        return [
            $asset->id,
            $asset->name,
            $asset->category ? $asset->category->name : '',
            $asset->serial_number,
            $asset->status,
            $warrantyExpiry ? $warrantyExpiry->format('Y-m-d') : 'N/A',
            $daysUntilWarrantyExpires ?? 'N/A',
            $lastMaintenance ? $lastMaintenance->completed_date->format('Y-m-d') : 'N/A',
            $nextMaintenance ? $nextMaintenance->scheduled_date->format('Y-m-d') : 'N/A',
            $daysUntilNextMaintenance ?? 'N/A',
            $complianceStatus,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $this->data['assets']->count() + 3;
                
                // Set title
                $sheet->mergeCells('A1:K1');
                $sheet->setCellValue('A1', 'Asset Compliance Report - Generated ' . now()->format('Y-m-d'));
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Set headers
                $sheet->fromArray($this->headings(), null, 'A3');
                $sheet->getStyle('A3:K3')->getFont()->setBold(true);
                $sheet->getStyle('A3:K3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');
                
                // Add conditional formatting for compliance status
                $complianceColumn = 'K';
                $startRow = 4;
                
                // Set colors based on compliance status
                $compliantColor = 'FFC6EFCE'; // Light green
                $attentionColor = 'FFFFEB9C'; // Light yellow
                $nonCompliantColor = 'FFFFC7CE'; // Light red
                
                for ($i = $startRow; $i <= $lastRow; $i++) {
                    $cell = $complianceColumn . $i;
                    $sheet->getStyle($cell)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB(
                            $sheet->getCell($cell)->getValue() === 'Compliant' ? $compliantColor :
                            ($sheet->getCell($cell)->getValue() === 'Attention Needed' ? $attentionColor : $nonCompliantColor)
                        );
                }
                
                // Add borders
                $sheet->getStyle("A3:K{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                // Add generated info
                $sheet->setCellValue("A" . ($lastRow + 2), 'Generated by: ' . $this->data['generated_by']);
                $sheet->setCellValue("A" . ($lastRow + 3), 'Generated at: ' . $this->data['generated_at']->format('Y-m-d H:i:s'));
                
                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(10);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(15);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(15);
                $sheet->getColumnDimension('J')->setWidth(15);
                $sheet->getColumnDimension('K')->setWidth(20);
                
                // Freeze panes
                $sheet->freezePane('A4');
                
                // Add filters
                $sheet->setAutoFilter("A3:K3");
            },
        ];
    }
}
