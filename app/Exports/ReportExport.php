<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    /**
     * The report data.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $data;

    /**
     * The report columns.
     *
     * @var array
     */
    protected $columns;

    /**
     * The report title.
     *
     * @var string
     */
    protected $title;

    /**
     * Create a new export instance.
     *
     * @param  mixed  $data
     * @return void
     */
    public function __construct($data, array $columns, string $title = 'Report')
    {
        $this->data = $data instanceof Collection ? $data : collect($data);
        $this->columns = $columns;
        $this->title = $title;
    }

    /**
     * Get the collection of data to be exported.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * Get the headings for the export.
     */
    public function headings(): array
    {
        return array_values($this->columns);
    }

    /**
     * Map the data for the export.
     *
     * @param  mixed  $row
     */
    public function map($row): array
    {
        $result = [];

        foreach (array_keys($this->columns) as $key) {
            // Handle nested data using dot notation
            $value = data_get($row, $key, '');

            // Format dates
            if ($value instanceof \DateTime || $value instanceof Carbon) {
                $value = $value->format('Y-m-d H:i:s');
            }

            // Convert arrays/objects to JSON
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT);
            }

            // Handle money formatting
            if (in_array(strtolower($key), ['price', 'cost', 'amount', 'total', 'subtotal', 'purchase_cost'])) {
                $value = is_numeric($value) ? number_format($value, 2) : $value;
            }

            $result[] = $value;
        }

        return $result;
    }

    /**
     * Get the title for the sheet.
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * Register events for the export.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Auto-size columns
                foreach (range('A', $sheet->getHighestColumn()) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Style the header row
                $event->sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => [
                            'argb' => 'FF2C3E50',
                        ],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => 'thin',
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Add filters to the header row
                $event->sheet->setAutoFilter('A1:'.$sheet->getHighestColumn().'1');

                // Freeze the first row
                $event->sheet->freezePane('A2');
            },
        ];
    }
}
