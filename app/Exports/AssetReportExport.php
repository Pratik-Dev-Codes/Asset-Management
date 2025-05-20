<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class AssetReportExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithEvents, WithColumnFormatting
{
    /**
     * The filters for the report.
     *
     * @var array
     */
    protected $filters;

    /**
     * Create a new export instance.
     *
     * @param array $filters
     * @return void
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $query = Asset::with(['category', 'location', 'assignedTo', 'vendor'])
            ->select([
                'assets.*',
                'categories.name as category_name',
                'locations.name as location_name',
                'users.name as assigned_to_name',
                'vendors.name as vendor_name'
            ])
            ->leftJoin('categories', 'assets.category_id', '=', 'categories.id')
            ->leftJoin('locations', 'assets.location_id', '=', 'locations.id')
            ->leftJoin('users', 'assets.assigned_to', '=', 'users.id')
            ->leftJoin('vendors', 'assets.vendor_id', '=', 'vendors.id');

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('assets.status', $this->filters['status']);
        }

        if (!empty($this->filters['purchase_date'])) {
            if (isset($this->filters['purchase_date']['start'])) {
                $query->whereDate('assets.purchase_date', '>=', $this->filters['purchase_date']['start']);
            }
            if (isset($this->filters['purchase_date']['end'])) {
                $query->whereDate('assets.purchase_date', '<=', $this->filters['purchase_date']['end']);
            }
        }

        return $query->orderBy('assets.id', 'asc');
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Asset Tag',
            'Name',
            'Serial Number',
            'Model',
            'Category',
            'Status',
            'Location',
            'Assigned To',
            'Purchase Date',
            'Purchase Cost',
            'Warranty (Months)',
            'Vendor',
            'Notes',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param mixed $asset
     *
     * @return array
     */
    public function map($asset): array
    {
        return [
            $asset->id,
            $asset->asset_tag,
            $asset->name,
            $asset->serial_number,
            $asset->model,
            $asset->category_name,
            ucfirst($asset->status),
            $asset->location_name,
            $asset->assigned_to_name,
            $asset->purchase_date ? Date::dateTimeToExcel(Carbon::parse($asset->purchase_date)) : null,
            $asset->purchase_cost,
            $asset->warranty_months,
            $asset->vendor_name,
            $asset->notes,
            Date::dateTimeToExcel($asset->created_at),
            Date::dateTimeToExcel($asset->updated_at),
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Assets_' . now()->format('Y-m-d');
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Auto-size columns
                $event->sheet->getDelegate()->getColumnDimension('A')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('B')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('C')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('D')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('E')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('F')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('G')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('H')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('I')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('J')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('K')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('L')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('M')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('N')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('O')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('P')->setAutoSize(true);

                // Set header style
                $event->sheet->getStyle('A1:P1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '3490dc'],
                    ],
                ]);

                // Set number format for currency and dates
                $event->sheet->getStyle('K2:K' . $event->sheet->getHighestRow())
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

                $event->sheet->getStyle('J2:J' . $event->sheet->getHighestRow())
                    ->getNumberFormat()
                    ->setFormatCode('yyyy-mm-dd');

                $event->sheet->getStyle('O2:P' . $event->sheet->getHighestRow())
                    ->getNumberFormat()
                    ->setFormatCode('yyyy-mm-dd hh:mm:ss');

                // Freeze the first row
                $event->sheet->getDelegate()->freezePane('A2');
            },
        ];
    }

    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'J' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'K' => NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
            'O' => NumberFormat::FORMAT_DATE_DATETIME,
            'P' => NumberFormat::FORMAT_DATE_DATETIME,
        ];
    }
}
