<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EnhancedReportDataService
{
    /**
     * Default number of items per page for pagination
     *
     * @var int
     */
    protected $perPage = 50;

    /**
     * Maximum number of items that can be exported at once
     *
     * @var int
     */
    protected $maxExportRows = 10000;

    /**
     * Get report data with pagination
     *
     * @param Report $report
     * @param int $page
     * @param int $perPage
     * @return array
     * @throws \Exception
     */
    public function getPaginatedData(Report $report, $page = 1, $perPage = null)
    {
        try {
            $perPage = $perPage ?: $this->perPage;
            $query = $this->buildBaseQuery($report);
            
            // Get total count for pagination
            $total = $this->getCachedCount($report, $query);
            
            // Apply pagination
            $items = $query->forPage($page, $perPage)->get();
            
            return [
                'data' => $items,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ];
            
        } catch (QueryException $e) {
            $this->logQueryError($e, $report);
            throw new \Exception('Failed to fetch report data. Please check your filters and try again.');
        }
    }
    
    /**
     * Get all report data for export
     * 
     * @param Report $report
     * @return \Illuminate\Support\Collection
     * @throws \Exception
     */
    public function getAllData(Report $report)
    {
        try {
            $query = $this->buildBaseQuery($report);
            
            // For exports, we need to ensure we're not loading too much data
            $count = $query->count();
            
            if ($count > $this->maxExportRows) {
                throw new \Exception(
                    "The report contains {$count} records, which exceeds the maximum allowed " . 
                    "export limit of {$this->maxExportRows} records. Please apply additional filters."
                );
            }
            
            return $query->get();
            
        } catch (QueryException $e) {
            $this->logQueryError($e, $report);
            throw new \Exception('Failed to fetch report data. Please check your filters and try again.');
        }
    }
    
    /**
     * Build the base query based on report type and filters
     * 
     * @param Report $report
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildBaseQuery(Report $report)
    {
        $query = DB::table($this->getTableName($report->type));
        
        // Apply columns selection
        $query->select($this->getSelectedColumns($report));
        
        // Apply filters
        $this->applyFilters($query, $report->filters ?? []);
        
        // Apply sorting
        $this->applySorting($query, $report->sorting ?? []);
        
        return $query;
    }
    
    /**
     * Get the table name for a report type
     * 
     * @param string $type
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getTableName($type)
    {
        $tables = [
            'asset' => 'assets',
            'user' => 'users',
            'accessory' => 'accessories',
            'consumable' => 'consumables',
            'license' => 'licenses',
        ];
        
        if (!isset($tables[$type])) {
            throw new \InvalidArgumentException("Invalid report type: {$type}");
        }
        
        return $tables[$type];
    }
    
    /**
     * Get the selected columns with proper table prefixes
     * 
     * @param Report $report
     * @return array
     */
    protected function getSelectedColumns(Report $report)
    {
        $table = $this->getTableName($report->type);
        $columns = $report->columns ?? [];
        
        // Always include ID for reference
        $selected = ["{$table}.id"];
        
        foreach ($columns as $column) {
            if ($column !== 'id') {
                $selected[] = "{$table}.{$column}";
            }
        }
        
        return $selected;
    }
    
    /**
     * Apply filters to the query
     * 
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                // Handle range filters
                if (isset($value['from']) || isset($value['to'])) {
                    if (!empty($value['from'])) {
                        $query->where($field, '>=', $value['from']);
                    }
                    if (!empty($value['to'])) {
                        $query->where($field, '<=', $value['to']);
                    }
                } 
                // Handle in_array filters
                elseif (!empty($value)) {
                    $query->whereIn($field, $value);
                }
            } else {
                // Handle simple equality
                $query->where($field, $value);
            }
        }
    }
    
    /**
     * Apply sorting to the query
     * 
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $sorting
     * @return void
     */
    protected function applySorting($query, array $sorting)
    {
        foreach ($sorting as $field => $direction) {
            $query->orderBy($field, $direction);
        }
    }
    
    /**
     * Get a cached count of the query results
     * 
     * @param Report $report
     * @param \Illuminate\Database\Query\Builder $query
     * @return int
     */
    protected function getCachedCount(Report $report, $query)
    {
        $cacheKey = "report_count_{$report->id}_" . md5(serialize($report->filters));
        
        return Cache::remember($cacheKey, now()->addHour(), function() use ($query) {
            return (clone $query)->count();
        });
    }
    
    /**
     * Log query errors with context
     * 
     * @param QueryException $e
     * @param Report $report
     * @return void
     */
    protected function logQueryError(QueryException $e, Report $report)
    {
        \Log::error('Report query error: ' . $e->getMessage(), [
            'report_id' => $report->id,
            'report_type' => $report->type,
            'filters' => $report->filters,
            'columns' => $report->columns,
            'sql' => $e->getSql(),
            'bindings' => $e->getBindings(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
