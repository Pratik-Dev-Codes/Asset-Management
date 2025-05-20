<?php

namespace App\Exports;

use App\Models\Report;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReportsExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithTitle
{
    protected $report;

    protected $data;

    protected $columns;

    protected $format;

    public function __construct(Report $report, $data, array $columns, string $format = 'xlsx')
    {
        $this->report = $report;
        $this->data = $data;
        $this->columns = $columns;
        $this->format = $format;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return array_map(function ($column) {
            return $column['label'] ?? $column['id'];
        }, $this->columns);
    }

    /**
     * @param  mixed  $row
     */
    public function map($row): array
    {
        $result = [];

        foreach ($this->columns as $column) {
            $value = $row[$column['id']] ?? null;
            $result[] = $this->formatValue($value, $column);
        }

        return $result;
    }

    /**
     * Format value based on column type
     */
    protected function formatValue($value, $column)
    {
        if (is_null($value)) {
            return '';
        }

        $type = $column['type'] ?? 'string';

        switch ($type) {
            case 'date':
            case 'datetime':
                return $value ? Date::dateTimeToExcel(new Carbon($value)) : '';
            case 'currency':
            case 'number':
            case 'decimal':
                return (float) $value;
            case 'boolean':
                return $value ? 'Yes' : 'No';
            case 'array':
                return is_array($value) ? implode(', ', $value) : $value;
            default:
                return (string) $value;
        }
    }

    public function title(): string
    {
        return substr(preg_replace('/[^\w\s-]/', '', $this->report->name), 0, 31);
    }

    public function columnFormats(): array
    {
        $formats = [];

        foreach ($this->columns as $index => $column) {
            $columnLetter = $this->getColumnLetter($index + 1);

            switch ($column['type'] ?? 'string') {
                case 'date':
                    $formats[$columnLetter] = NumberFormat::FORMAT_DATE_DDMMYYYY;
                    break;
                case 'datetime':
                    $formats[$columnLetter] = 'dd/mm/yyyy hh:mm';
                    break;
                case 'currency':
                    $formats[$columnLetter] = '_-$* #,##0.00_-;-\$* #,##0.00_-;_-$* "-"??_-;_-@_-';
                    break;
                case 'number':
                case 'decimal':
                    $decimals = $column['decimals'] ?? 2;
                    $formats[$columnLetter] = '0'.($decimals > 0 ? '.'.str_repeat('0', $decimals) : '');
                    break;
                case 'percentage':
                    $formats[$columnLetter] = '0.00%';
                    break;
            }
        }

        return $formats;
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $sheet = $event->sheet;

                // Set title
                $sheet->setCellValue('A1', $this->report->name);
                $sheet->mergeCells('A1:'.$this->getColumnLetter(count($this->columns)).'1');

                // Style the title
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Style the header row
                $headerRange = 'A2:'.$this->getColumnLetter(count($this->columns)).'2';
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Add filters to the header
                $sheet->setAutoFilter($headerRange);

                // Set row height for header
                $sheet->getRowDimension(2)->setRowHeight(25);

                // Add description if exists
                if (! empty($this->report->description)) {
                    $sheet->setCellValue('A'.($sheet->getHighestRow() + 2), 'Description:');
                    $sheet->setCellValue('B'.$sheet->getHighestRow(), $this->report->description);
                    $sheet->mergeCells('B'.$sheet->getHighestRow().':'.$this->getColumnLetter(count($this->columns)).$sheet->getHighestRow());
                }

                // Add generated at timestamp
                $sheet->setCellValue('A'.($sheet->getHighestRow() + 2), 'Generated:');
                $sheet->setCellValue('B'.$sheet->getHighestRow(), now()->format('Y-m-d H:i:s'));

                // Freeze the header row
                $sheet->freezePane('A3');
            },
        ];
    }

    /**
     * Convert column number to letter (e.g., 1 -> A, 27 -> AA)
     */
    protected function getColumnLetter($num)
    {
        $numeric = $num % 26;
        $letter = chr(65 + $numeric - 1);
        $num2 = (int) ($num / 26);

        if ($num2 > 0) {
            return $this->getColumnLetter($num2).$letter;
        } else {
            return $letter;
        }
    }
}
