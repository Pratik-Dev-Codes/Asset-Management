<?php

namespace App\Exports;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AssetsExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithTitle
{
    protected $assets;

    protected $filters;

    protected $includeImages;

    protected $includeDocuments;

    public function __construct($assets, array $filters = [], bool $includeImages = false, bool $includeDocuments = false)
    {
        $this->assets = $assets;
        $this->filters = $filters;
        $this->includeImages = $includeImages;
        $this->includeDocuments = $includeDocuments;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->assets;
    }

    public function headings(): array
    {
        $headings = [
            'ID',
            'Asset Code',
            'Name',
            'Category',
            'Serial Number',
            'Model',
            'Manufacturer',
            'Status',
            'Condition',
            'Location',
            'Department',
            'Assigned To',
            'Purchase Date',
            'Purchase Cost',
            'Current Value',
            'Warranty Start Date',
            'Warranty Expiry Date',
            'Warranty Provider',
            'Supplier',
            'Purchase Order Number',
            'Notes',
            'Created At',
            'Updated At',
        ];

        if ($this->includeImages) {
            $headings[] = 'Image URLs';
        }

        if ($this->includeDocuments) {
            $headings[] = 'Document URLs';
        }

        return $headings;
    }

    /**
     * @param  mixed  $asset
     */
    public function map($asset): array
    {
        $row = [
            $asset->id,
            $asset->asset_code,
            $asset->name,
            $asset->category ? $asset->category->name : '',
            $asset->serial_number,
            $asset->model,
            $asset->manufacturer,
            $asset->status,
            $asset->condition,
            $asset->location ? $asset->location->name : '',
            $asset->department ? $asset->department->name : '',
            $asset->assignedTo ? $asset->assignedTo->name : '',
            $asset->purchase_date ? Date::dateTimeToExcel($asset->purchase_date) : null,
            $asset->purchase_cost,
            $asset->current_value,
            $asset->warranty_start_date ? Date::dateTimeToExcel($asset->warranty_start_date) : null,
            $asset->warranty_expiry_date ? Date::dateTimeToExcel($asset->warranty_expiry_date) : null,
            $asset->warranty_provider,
            $asset->supplier ? $asset->supplier->name : '',
            $asset->purchase_order_number,
            $asset->notes,
            $asset->created_at ? Date::dateTimeToExcel($asset->created_at) : null,
            $asset->updated_at ? Date::dateTimeToExcel($asset->updated_at) : null,
        ];

        if ($this->includeImages) {
            $row[] = $asset->image_url ?: 'No image';
        }

        if ($this->includeDocuments) {
            $documents = $asset->documents->pluck('document_path')->implode("\n");
            $row[] = $documents ?: 'No documents';
        }

        return $row;
    }

    public function title(): string
    {
        $title = 'Assets Report - '.now()->format('Y-m-d');

        // Add filter info to title if filters are applied
        $filterParts = [];

        if (! empty($this->filters['category_id'])) {
            $categories = AssetCategory::whereIn('id', (array) $this->filters['category_id'])
                ->pluck('name')
                ->implode(', ');
            $filterParts[] = 'Categories: '.$categories;
        }

        if (! empty($this->filters['status'])) {
            $statuses = implode(', ', (array) $this->filters['status']);
            $filterParts[] = 'Status: '.$statuses;
        }

        if (! empty($this->filters['location_id'])) {
            $locations = Location::whereIn('id', (array) $this->filters['location_id'])
                ->pluck('name')
                ->implode(', ');
            $filterParts[] = 'Locations: '.$locations;
        }

        if (! empty($filterParts)) {
            $title .= ' ('.implode(', ', $filterParts).')';
        }

        return $title;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Set title
                $event->sheet->setTitle(substr($this->title(), 0, 31)); // Excel sheet title max 31 chars

                // Style the header row
                $event->sheet->getStyle('A1:'.$event->sheet->getHighestColumn().'1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4A89DC'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Auto-size all columns
                foreach (range('A', $event->sheet->getHighestColumn()) as $col) {
                    $event->sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Set number format for currency and date columns
                $lastRow = $event->sheet->getHighestRow();
                $event->sheet->getStyle('N2:O'.$lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
                $event->sheet->getStyle('M2:M'.$lastRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $event->sheet->getStyle('P2:Q'.$lastRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                $event->sheet->getStyle('V2:W'.$lastRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm');

                // Add filter
                $event->sheet->setAutoFilter('A1:'.$event->sheet->getHighestColumn().'1');

                // Freeze the first row
                $event->sheet->freezePane('A2');
            },
        ];
    }

    public function columnFormats(): array
    {
        return [
            'M' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'N' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'O' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'P' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'Q' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'V' => NumberFormat::FORMAT_DATE_DATETIME,
            'W' => NumberFormat::FORMAT_DATE_DATETIME,
        ];
    }
}
