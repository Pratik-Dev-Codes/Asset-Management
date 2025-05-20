<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportFile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;
use Barryvdh\DomPDF\Facade as PDF;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use ZipArchive;

class ReportService
{
    /**
     * Default cache TTL in minutes.
     */
    public const CACHE_TTL = 60;

    /**
     * Default number of items per page.
     */
    public const DEFAULT_PER_PAGE = 25;

    /**
     * Maximum number of rows to process in a single chunk.
     */
    public const CHUNK_SIZE = 1000;

    /**
     * Allowed export formats.
     */
    public const ALLOWED_FORMATS = ['xlsx', 'pdf', 'csv'];

    /**
     * @var array Default sorting options
     */
    protected array $defaultSorting = [
        'field' => 'id',
        'direction' => 'asc',
    ];
    /**
     * Get report data based on the report configuration with caching.
     *
     * @param Report $report
     * @param array $filters
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function getReportData(Report $report, array $filters = [], array $options = []): array
    {
        $cacheKey = $this->getReportCacheKey($report, $filters, $options);
        
        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($report, $filters, $options) {
            try {
                // Start query logging if in debug mode
                if (config('app.debug')) {
                    DB::enableQueryLog();
                }

                $query = $this->buildBaseQuery($report);
                
                // Apply filters
                $query = $this->applyFilters($query, $report, $filters);
                
                // Get total count before pagination
                $total = $query->count();
                
                // Apply sorting
                $sorting = $options['sorting'] ?? $report->sorting ?? $this->defaultSorting;
                $this->applySorting($query, $sorting);
                
                // Apply pagination
                $perPage = $this->getPerPage($options);
                $page = $this->getCurrentPage($options);
                
                $items = $query->paginate($perPage, ['*'], 'page', $page);
                
                // Format the data
                $formattedData = $this->formatReportData($items->items(), $report);
                
                $result = [
                    'data' => $formattedData,
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'from' => $items->firstItem(),
                    'to' => $items->lastItem(),
                ];
                
                // Log the query if in debug mode
                if (config('app.debug')) {
                    Log::debug('Report query executed', [
                        'report_id' => $report->id,
                        'queries' => DB::getQueryLog(),
                        'execution_time' => microtime(true) - LARAVEL_START,
                    ]);
                }
                
                return $result;
                
            } catch (\Exception $e) {
                Log::error('Failed to generate report data', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                throw $e;
            }
        });
    }
    
    /**
     * Generate a cache key for the report data.
     *
     * @param Report $report
     * @param array $filters
     * @param array $options
     * @return string
     */
    protected function getReportCacheKey(Report $report, array $filters, array $options): string
    {
        return sprintf(
            'report_data_%s_%s_%s',
            $report->id,
            md5(json_encode($filters)),
            md5(json_encode($options))
        );
    }
    
    /**
     * Apply sorting to the query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $sorting
     * @return void
     */
    protected function applySorting($query, array $sorting): void
    {
        if (!empty($sorting['field'])) {
            $direction = strtolower($sorting['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $query->orderBy($sorting['field'], $direction);
        }
    }
    
    /**
     * Get the number of items per page from options or use default.
     *
     * @param array $options
     * @return int
     */
    protected function getPerPage(array $options): int
    {
        $perPage = $options['per_page'] ?? self::DEFAULT_PER_PAGE;
        $maxPerPage = config('reports.max_per_page', 100);
        
        return min(max(1, (int)$perPage), $maxPerPage);
    }
    
    /**
     * Get the current page number from options or use default.
     *
     * @param array $options
     * @return int
     */
    protected function getCurrentPage(array $options): int
    {
        return max(1, (int)($options['page'] ?? 1));
    }
    
    /**
     * Export report data to the specified format.
     *
     * @param Report $report
     * @param string $format
     * @param array $filters
     * @return ReportFile
     * @throws \Exception
     */
    public function export(Report $report, string $format = 'xlsx', array $filters = []): ReportFile
    {
        try {
            // Validate format
            if (!in_array($format, self::ALLOWED_FORMATS)) {
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
            }
            
            // Get all data without pagination
            $query = $this->buildBaseQuery($report);
            $query = $this->applyFilters($query, $report, $filters);
            
            // Get the data in chunks to reduce memory usage
            $data = collect();
            $query->chunk(self::CHUNK_SIZE, function ($chunk) use (&$data) {
                $data = $data->merge($chunk);
            });
            
            // Generate filename and path
            $filename = $this->generateExportFilename($report, $format);
            $path = $this->getExportPath($report, $filename);
            
            // Create the export file record
            $file = $report->files()->create([
                'filename' => $filename,
                'path' => $path,
                'format' => $format,
                'generated_by' => Auth::id(),
                'generated_at' => now(),
            ]);
            
            // Generate the export file
            $this->generateExportFile($data, $file, $format);
            
            // Update the file size
            $file->update([
                'size' => Storage::size($path),
            ]);
            
            // Update the report's last generated timestamp
            $report->update([
                'last_generated_at' => now(),
                'next_run_at' => $this->calculateNextRunDate($report->schedule_frequency),
            ]);
            
            return $file;
            
        } catch (\Exception $e) {
            Log::error('Failed to export report', [
                'report_id' => $report->id,
                'format' => $format,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Generate a filename for the export file.
     *
     * @param Report $report
     * @param string $format
     * @return string
     */
    protected function generateExportFilename(Report $report, string $format): string
    {
        return sprintf(
            '%s_%s.%s',
            Str::slug($report->name),
            now()->format('Y-m-d_His'),
            $format
        );
    }
    
    /**
     * Get the storage path for the export file.
     *
     * @param Report $report
     * @param string $filename
     * @return string
     */
    protected function getExportPath(Report $report, string $filename): string
    {
        return 'reports/' . $report->id . '/' . $filename;
    }
    
    /**
     * Generate the export file in the specified format.
     *
     * @param \Illuminate\Support\Collection $data
     * @param ReportFile $file
     * @param string $format
     * @return void
     */
    protected function generateExportFile($data, ReportFile $file, string $format): void
    {
        $path = storage_path('app/' . $file->path);
        
        // Ensure the directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        
        switch ($format) {
            case 'xlsx':
            case 'csv':
                // Convert collection to array for export
                $exportData = $data->map(function($item) use ($file) {
                    $row = [];
                    foreach ($file->report->columns as $column) {
                        $row[$column] = $item->$column ?? '';
                    }
                    return $row;
                })->toArray();
                
                $export = new ReportExport(
                    $exportData, 
                    $file->report->columns, 
                    $file->report->name
                );
                
                Excel::store(
                    $export,
                    $file->path,
                    'local',
                    $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX
                );
                break;
                
            case 'pdf':
                // Generate a simple PDF table
                $html = '<h1>'.$file->report->name.'</h1>';
                $html .= '<table border="1" cellspacing="0" cellpadding="5">';
                
                // Add table headers
                $html .= '<tr>';
                foreach ($file->report->columns as $column) {
                    $html .= '<th>'.ucfirst(str_replace('_', ' ', $column)).'</th>';
                }
                $html .= '</tr>';
                
                // Add table rows
                foreach ($data as $row) {
                    $html .= '<tr>';
                    foreach ($file->report->columns as $column) {
                        $value = $row->$column ?? '';
                        $html .= '<td>'.e($value).'</td>';
                    }
                    $html .= '</tr>';
                }
                
                $html .= '</table>';
                
                // Generate PDF
                $pdf = app('dompdf.wrapper');
                $pdf->loadHTML($html);
                $pdf->save($path);
                break;
                
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }
    
    /**
     * Calculate the next run date based on the schedule frequency.
     *
     * @param string|null $frequency
     * @return \Carbon\Carbon|null
     */
    public function calculateNextRunDate(?string $frequency): ?Carbon
    {
        if (empty($frequency)) {
            return null;
        }
        
        $now = now();
        
        return match ($frequency) {
            'daily' => $now->addDay(),
            'weekly' => $now->addWeek(),
            'monthly' => $now->addMonth(),
            'quarterly' => $now->addQuarter(),
            'yearly' => $now->addYear(),
            default => null,
        };
    }
    
    /**
     * Clean up old report files.
     *
     * @param int $daysOld
     * @return int Number of files deleted
     */
    public function cleanupOldFiles(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $deleted = 0;
        
        ReportFile::query()
            ->where('created_at', '<', $cutoffDate)
            ->chunk(100, function ($files) use (&$deleted) {
                foreach ($files as $file) {
                    try {
                        if (Storage::exists($file->path)) {
                            Storage::delete($file->path);
                        }
                        $file->delete();
                        $deleted++;
                    } catch (\Exception $e) {
                        Log::error('Failed to delete report file', [
                            'file_id' => $file->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
        
        return $deleted;
    }
    
    /**
     * Build the base query based on report type
     */
    protected function buildBaseQuery(Report $report)
    {
        $query = DB::table($this->getTableName($report->type));
        
        // Select only the columns specified in the report
        if (!empty($report->columns)) {
            $query->select($report->columns);
        }
        
        // Apply any base filters from the report
        if (!empty($report->filters)) {
            $this->applyQueryFilters($query, $report->filters);
        }
        
        return $query;
    }
    
    /**
     * Apply filters to the query
     */
    protected function applyFilters($query, Report $report, array $filters)
    {
        if (empty($filters)) {
            return $query;
        }
        
        foreach ($filters as $filter) {
            if (empty($filter['field']) || !isset($filter['operator'])) {
                continue;
            }
            
            $field = $filter['field'];
            $operator = $filter['operator'];
            $value = $filter['value'] ?? null;
            
            switch ($operator) {
                case 'equals':
                    $query->where($field, '=', $value);
                    break;
                case 'not_equals':
                    $query->where($field, '!=', $value);
                    break;
                case 'contains':
                    $query->where($field, 'LIKE', "%{$value}%");
                    break;
                case 'not_contains':
                    $query->where($field, 'NOT LIKE', "%{$value}%");
                    break;
                case 'starts_with':
                    $query->where($field, 'LIKE', "{$value}%");
                    break;
                case 'ends_with':
                    $query->where($field, 'LIKE', "%{$value}");
                    break;
                case 'greater_than':
                    $query->where($field, '>', $value);
                    break;
                case 'less_than':
                    $query->where($field, '<', $value);
                    break;
                case 'greater_than_equals':
                    $query->where($field, '>=', $value);
                    break;
                case 'less_than_equals':
                    $query->where($field, '<=', $value);
                    break;
                case 'in':
                    $query->whereIn($field, (array)$value);
                    break;
                case 'not_in':
                    $query->whereNotIn($field, (array)$value);
                    break;
                case 'is_null':
                    $query->whereNull($field);
                    break;
                case 'is_not_null':
                    $query->whereNotNull($field);
                    break;
                case 'between':
                    if (is_array($value) && count($value) === 2) {
                        $query->whereBetween($field, $value);
                    }
                    break;
                case 'not_between':
                    if (is_array($value) && count($value) === 2) {
                        $query->whereNotBetween($field, $value);
                    }
                    break;
            }
        }
        
        return $query;
    }
    
    /**
     * Apply query filters from report configuration
     */
    protected function applyQueryFilters($query, array $filters)
    {
        foreach ($filters as $filter) {
            if (empty($filter['field']) || !isset($filter['operator'])) {
                continue;
            }
            
            $field = $filter['field'];
            $operator = $filter['operator'];
            $value = $filter['value'] ?? null;
            $boolean = $filter['boolean'] ?? 'and';
            
            if ($operator === 'where') {
                $query->where($field, '=', $value, $boolean);
                continue;
            }
            
            $method = 'where' . str_replace('_', '', ucwords($operator, '_'));
            
            if (method_exists($query, $method)) {
                $query->$method($field, $value, $boolean);
            }
        }
        
        return $query;
    }
    
    /**
     * Format the report data according to the column definitions
     */
    protected function formatReportData(array $data, Report $report): array
    {
        $formattedData = [];
        
        foreach ($data as $item) {
            $formattedItem = [];
            
            foreach ($report->columns as $column) {
                $columnId = is_array($column) ? ($column['id'] ?? null) : $column;
                $columnType = is_array($column) ? ($column['type'] ?? 'string') : 'string';
                
                if (!$columnId) {
                    continue;
                }
                
                $value = $item->$columnId ?? null;
                
                // Format value based on type
                $formattedItem[$columnId] = $this->formatValue($value, $columnType, $column);
                
                // Add URL if this is a link column
                if (is_array($column) && ($column['type'] ?? null) === 'link' && isset($column['url'])) {
                    $formattedItem[$columnId . '_url'] = $this->formatLink($column['url'], $item);
                }
            }
            
            $formattedData[] = $formattedItem;
        }
        
        return $formattedData;
    }
    
    /**
     * Format a single value based on its type
     */
    protected function formatValue($value, string $type, array $column = [])
    {
        if ($value === null) {
            return null;
        }
        
        switch ($type) {
            case 'date':
                return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
                
            case 'datetime':
                return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s') : null;
                
            case 'currency':
                $currency = $column['currency'] ?? 'USD';
                $decimals = $column['decimals'] ?? 2;
                return number_format((float)$value, $decimals, '.', ',');
                
            case 'number':
            case 'decimal':
                $decimals = $column['decimals'] ?? 2;
                return number_format((float)$value, $decimals, '.', '');
                
            case 'percentage':
                $decimals = $column['decimals'] ?? 2;
                return number_format((float)$value * 100, $decimals) . '%';
                
            case 'boolean':
                return (bool)$value ? 'Yes' : 'No';
                
            case 'array':
                return is_array($value) ? implode(', ', $value) : $value;
                
            case 'json':
                return is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT);
                
            case 'link':
                return $value; // URL will be added as a separate field
                
            default:
                return (string)$value;
        }
    }
    
    /**
     * Format a link URL with dynamic parameters
     */
    protected function formatLink(string $url, $item): string
    {
        // Replace placeholders like {id} with actual values from the item
        return preg_replace_callback('/\{([^}]+)\}/', function($matches) use ($item) {
            $key = $matches[1];
            return $item->$key ?? $matches[0];
        }, $url);
    }
    
    /**
     * Get the table name based on report type
     */
    protected function getTableName(string $type): string
    {
        $tables = [
            'asset' => 'assets',
            'inventory' => 'inventory_items',
            'maintenance' => 'maintenance_records',
            'depreciation' => 'depreciation_records',
            'user' => 'users',
            'custom' => 'custom_reports',
        ];
        
        return $tables[$type] ?? 'reports';
    }
    
    /**
     * Get summary statistics for the report
     */
    public function getReportSummary(Report $report, array $filters = []): array
    {
        $query = $this->buildBaseQuery($report);
        $query = $this->applyFilters($query, $report, $filters);
        
        $summary = [
            'total_records' => $query->count(),
            'columns' => [],
        ];
        
        // Add column summaries if specified in the report
        if (!empty($report->summary_columns)) {
            foreach ($report->summary_columns as $column) {
                $columnId = is_array($column) ? ($column['id'] ?? null) : $column;
                $columnType = is_array($column) ? ($column['type'] ?? 'string') : 'string';
                
                if (!$columnId) {
                    continue;
                }
                
                $summary['columns'][$columnId] = $this->getColumnSummary($query, $columnId, $columnType, $column);
            }
        }
        
        return $summary;
    }
    
    /**
     * Get summary statistics for a specific column
     */
    protected function getColumnSummary($query, string $column, string $type, array $options = []): array
    {
        $summary = [
            'type' => $type,
            'distinct' => $query->distinct()->count($column),
        ];
        
        // Only calculate numeric aggregations for numeric types
        if (in_array($type, ['number', 'decimal', 'currency', 'percentage'])) {
            $aggregates = $query->selectRaw(
                "COUNT($column) as count, " .
                "SUM($column) as sum, " .
                "AVG($column) as avg, " .
                "MIN($column) as min, " .
                "MAX($column) as max"
            )->first();
            
            $summary = array_merge($summary, [
                'sum' => (float)($aggregates->sum ?? 0),
                'avg' => (float)($aggregates->avg ?? 0),
                'min' => (float)($aggregates->min ?? 0),
                'max' => (float)($aggregates->max ?? 0),
                'count' => (int)($aggregates->count ?? 0),
            ]);
        }
        
        // For date/datetime columns, add date-specific aggregations
        if (in_array($type, ['date', 'datetime'])) {
            $dateAggregates = $query->selectRaw(
                "MIN($column) as earliest, " .
                "MAX($column) as latest"
            )->first();
            
            $summary = array_merge($summary, [
                'earliest' => $dateAggregates->earliest,
                'latest' => $dateAggregates->latest,
            ]);
        }
        
        return $summary;
    }
    
    /**
     * Get chart data for the report
     */
    public function getChartData(Report $report, array $filters = [], array $chartOptions = []): array
    {
        if (empty($chartOptions['type'])) {
            return [];
        }
        
        $query = $this->buildBaseQuery($report);
        $query = $this->applyFilters($query, $report, $filters);
        
        $xAxis = $chartOptions['x_axis'] ?? null;
        $yAxis = $chartOptions['y_axis'] ?? null;
        $groupBy = $chartOptions['group_by'] ?? null;
        
        if (!$xAxis || !$yAxis) {
            return [];
        }
        
        // Build the base query with the required columns
        $selectColumns = [$xAxis, $yAxis];
        if ($groupBy) {
            $selectColumns[] = $groupBy;
        }
        
        $query->select($selectColumns);
        
        // Apply grouping if specified
        if ($groupBy) {
            $query->groupBy($groupBy, $xAxis);
        } else {
            $query->groupBy($xAxis);
        }
        
        // Get the data
        $data = $query->get();
        
        // Format the data for the chart
        $chartData = [
            'labels' => [],
            'datasets' => [],
        ];
        
        if ($groupBy) {
            // Group data by the group_by field
            $groupedData = $data->groupBy($groupBy);
            
            foreach ($groupedData as $group => $items) {
                $dataset = [
                    'label' => $group,
                    'data' => [],
                ];
                
                foreach ($items as $item) {
                    if (!in_array($item->$xAxis, $chartData['labels'])) {
                        $chartData['labels'][] = $item->$xAxis;
                    }
                    
                    $dataset['data'][] = $item->$yAxis;
                }
                
                $chartData['datasets'][] = $dataset;
            }
        } else {
            // No grouping, single dataset
            $dataset = [
                'label' => $chartOptions['title'] ?? $yAxis,
                'data' => [],
            ];
            
            foreach ($data as $item) {
                $chartData['labels'][] = $item->$xAxis;
                $dataset['data'][] = $item->$yAxis;
            }
            
            $chartData['datasets'][] = $dataset;
        }
        
        return $chartData;
    }
}
