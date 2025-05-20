<?php

namespace App\Services;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\Department;
use App\Models\License;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\Manufacturer;
use App\Models\Report;
use App\Models\StatusLabel;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exceptions\ReportGenerationException;
use Illuminate\Support\Str;

class ReportDataService
{
    /**
     * Default cache TTL in minutes.
     */
    protected const CACHE_TTL = 60;

    /**
     * Maximum number of items per page.
     */
    protected const MAX_PER_PAGE = 1000;

    /**
     * Maximum number of rows that can be exported at once.
     */
    protected const MAX_EXPORT_ROWS = 50000;

    /**
     * Maximum number of columns allowed in a report.
     */
    protected const MAX_COLUMNS = 50;

    /**
     * Get report data with caching and pagination.
     *
     * @param  Report  $report
     * @param  array  $filters
     * @param  array  $options
     * @param  User|null  $user  The user requesting the report
     * @return array
     * @throws ReportGenerationException
     */
    public function getReportData(Report $report, array $filters = [], array $options = [], ?User $user = null): array
    {
        try {
            // Validate report configuration
            $this->validateReportConfiguration($report);
            
            // Validate user permissions
            if ($user && !$this->userCanAccessReport($user, $report)) {
                throw ReportGenerationException::permissionDenied();
            }
            
            // Validate filters
            $this->validateFilters($filters);
            
            // Generate cache key with user context
            $cacheKey = $this->getCacheKey($report, $filters, $options, $user);
            
            // Check if we should bypass cache
            $bypassCache = $options['bypass_cache'] ?? false;
            
            if ($bypassCache) {
                Cache::forget($cacheKey);
            }
            
            // Determine cache TTL
            $cacheTtl = $options['cache_ttl'] ?? self::CACHE_TTL;
            $cacheTtl = min($cacheTtl, 1440); // Max 24 hours
            
            return Cache::remember($cacheKey, now()->addMinutes($cacheTtl), function () use ($report, $filters, $options) {
                // Start query log to track performance
                DB::enableQueryLog();
                $startTime = microtime(true);
                
                try {
                    $query = $this->buildBaseQuery($report);
                    
                    // Apply filters with validation
                    $query = $this->applyFilters($query, $report, $filters);
                    
                    // Get total count before applying pagination
                    $total = $query->count();
                    
                    // Validate result set size
                    $this->validateResultSetSize($total, $options);
                    
                    // Apply sorting
                    $sorting = $options['sorting'] ?? $report->sorting;
                    $this->applySorting($query, $sorting);
                    
                    // Apply pagination
                    $perPage = min($options['per_page'] ?? $report->per_page ?? 25, self::MAX_PER_PAGE);
                    $page = $options['page'] ?? 1;
                    
                    // Only select the columns that are needed
                    $columns = $this->getSelectedColumns($report, $options);
                    
                    // Execute query with pagination
                    $items = $query->paginate(
                        $perPage, 
                        $columns,
                        'page', 
                        $page
                    );
                    
                    // Log query performance
                    $executionTime = microtime(true) - $startTime;
                    $queryLog = DB::getQueryLog();
                    
                    Log::debug('Report query executed', [
                        'report_id' => $report->id,
                        'execution_time' => $executionTime,
                        'query_count' => count($queryLog),
                        'result_count' => $total,
                    ]);
                    
                    return [
                        'data' => $items->items(),
                        'meta' => [
                            'total' => $items->total(),
                            'per_page' => $items->perPage(),
                            'current_page' => $items->currentPage(),
                            'last_page' => $items->lastPage(),
                            'from' => $items->firstItem(),
                            'to' => $items->lastItem(),
                            'query_time' => round($executionTime, 3),
                            'query_count' => count($queryLog),
                        ]
                    ];
                    
                } catch (\Exception $e) {
                    // Log detailed error information
                    Log::error('Report generation failed', [
                        'report_id' => $report->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'filters' => $filters,
                        'options' => $options,
                    ]);
                    
                    throw $e;
                } finally {
                    DB::disableQueryLog();
                }
            });
            
        } catch (ReportGenerationException $e) {
            // Re-throw ReportGenerationException as is
            throw $e;
        } catch (\Exception $e) {
            // Wrap other exceptions in a ReportGenerationException
            throw ReportGenerationException::queryError(
                'Failed to generate report data: ' . $e->getMessage()
            )->withData([
                'report_id' => $report->id,
                'exception' => get_class($e),
            ]);
        }
    }
    
    /**
     * Build the base query for the report.
     *
     * @param  Report  $report
     * @return \Illuminate\Database\Query\Builder
     * @throws ReportGenerationException
     */
    protected function buildBaseQuery(Report $report): Builder
    {
        $model = $this->getModelForType($report->type);
        
        if (!class_exists($model)) {
            throw ReportGenerationException::validationError("Invalid report type: {$report->type}");
        }
        
        return $model::query();
    }
    
    /**
     * Apply filters to the query.
     *
     * @param  Builder  $query
     * @param  Report  $report
     * @param  array  $filters
     * @return Builder
     */
    protected function applyFilters(Builder $query, Report $report, array $filters): Builder
    {
        $filters = array_merge($report->filters ?? [], $filters);
        
        foreach ($filters as $filter) {
            if (empty($filter['field']) || !isset($filter['operator'])) {
                continue;
            }
            
            $field = $filter['field'];
            $operator = $filter['operator'];
            $value = $filter['value'] ?? null;
            
            switch (strtolower($operator)) {
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
                case 'between':
                    if (is_array($value) && count($value) === 2) {
                        $query->whereBetween($field, $value);
                    }
                    break;
                case 'in':
                    if (is_array($value)) {
                        $query->whereIn($field, $value);
                    }
                    break;
                case 'not_in':
                    if (is_array($value)) {
                        $query->whereNotIn($field, $value);
                    }
                    break;
                case 'null':
                    $query->whereNull($field);
                    break;
                case 'not_null':
                    $query->whereNotNull($field);
                    break;
            }
        }
        
        return $query;
    }
    
    /**
     * Apply sorting to the query.
     *
     * @param  Builder  $query
     * @param  array|null  $sorting
     * @return void
     */
    protected function applySorting(Builder $query, ?array $sorting): void
    {
        if (empty($sorting['field'])) {
            return;
        }
        
        $direction = strtolower($sorting['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sorting['field'], $direction);
    }
    
    /**
     * Get the model class for the given report type.
     *
     * @param  string  $type
     * @return string
     * @throws ReportGenerationException
     */
    protected function getModelForType(string $type): string
    {
        $models = [
            'asset' => \App\Models\Asset::class,
            'user' => \App\Models\User::class,
            'accessory' => \App\Models\Accessory::class,
            'component' => \App\Models\Component::class,
            'consumable' => \App\Models\Consumable::class,
            'license' => \App\Models\License::class,
            'location' => \App\Models\Location::class,
            'supplier' => \App\Models\Supplier::class,
            'department' => \App\Models\Department::class,
            'status_label' => \App\Models\StatusLabel::class,
            'maintenance' => \App\Models\Maintenance::class,
        ];

        if (!array_key_exists($type, $models)) {
            throw ReportGenerationException::validationError("Invalid report type: {$type}");
        }
        
        return $models[$type];
    }
    
    /**
     * Validate the report configuration.
     *
     * @param  Report  $report
     * @return void
     * @throws ReportGenerationException
     */
    protected function validateReportConfiguration(Report $report): void
    {
        // Validate report type
        if (empty($report->type)) {
            throw ReportGenerationException::validationError('Report type is required');
        }
        
        // Validate columns
        if (empty($report->columns) || !is_array($report->columns)) {
            throw ReportGenerationException::validationError('Report must have at least one column');
        }
        
        // Validate column count
        if (count($report->columns) > self::MAX_COLUMNS) {
            throw ReportGenerationException::validationError(
                sprintf('Report cannot have more than %d columns', self::MAX_COLUMNS)
            );
        }
        
        // Validate filters if present
        if (!empty($report->filters) && !is_array($report->filters)) {
            throw ReportGenerationException::validationError('Invalid filters format');
        }
    }
    
    /**
     * Check if a user can access a report.
     *
     * @param  User  $user
     * @param  Report  $report
     * @return bool
     */
    protected function userCanAccessReport(User $user, Report $report): bool
    {
        // Public reports are accessible to everyone
        if ($report->is_public) {
            return true;
        }
        
        // Report creator can always access their own reports
        if ($report->created_by === $user->id) {
            return true;
        }
        
        // Check if user has permission to view all reports
        if ($user->hasPermissionTo('view_all_reports')) {
            return true;
        }
        
        // Check if user has been granted explicit access to this report
        if (method_exists($report, 'users') && $report->users()->where('user_id', $user->id)->exists()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate report filters.
     *
     * @param  array  $filters
     * @return void
     * @throws ReportGenerationException
     */
    protected function validateFilters(array $filters): void
    {
        if (empty($filters)) {
            return;
        }
        
        $allowedOperators = ['=', '!=', '>', '>=', '<', '<=', 'like', 'not like', 'in', 'not in', 'null', 'not null'];
        
        foreach ($filters as $filter) {
            if (!is_array($filter) || !isset($filter['field'], $filter['operator'])) {
                throw ReportGenerationException::validationError('Invalid filter format');
            }
            
            // Validate operator
            if (!in_array(strtolower($filter['operator']), $allowedOperators)) {
                throw ReportGenerationException::validationError(
                    sprintf('Invalid filter operator: %s', $filter['operator'])
                );
            }
            
            // Validate value for operators that require it
            if (!in_array(strtolower($filter['operator']), ['null', 'not null']) && !array_key_exists('value', $filter)) {
                throw ReportGenerationException::validationError(
                    sprintf('Filter on field %s is missing a value', $filter['field'])
                );
            }
            
            // Sanitize field name to prevent SQL injection
            if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filter['field'])) {
                throw ReportGenerationException::validationError(
                    sprintf('Invalid field name: %s', $filter['field'])
                );
            }
        }
    }
    
    /**
     * Validate the result set size.
     *
     * @param  int  $count
     * @param  array  $options
     * @return void
     * @throws ReportGenerationException
     */
    protected function validateResultSetSize(int $count, array $options): void
    {
        $isExport = $options['is_export'] ?? false;
        $maxRows = $isExport ? self::MAX_EXPORT_ROWS : self::MAX_PER_PAGE * 10; // Allow 10 pages for regular display
        
        if ($count > $maxRows) {
            throw ReportGenerationException::tooManyResults(
                sprintf(
                    'Query returned %d results, which exceeds the maximum of %d allowed for %s.',
                    $count,
                    $maxRows,
                    $isExport ? 'export' : 'display'
                ),
                $maxRows
            );
        }
    }
    
    /**
     * Get the columns to select for the report.
     *
     * @param  Report  $report
     * @param  array  $options
     * @return array
     */
    protected function getSelectedColumns(Report $report, array $options): array
    {
        // If columns are specified in options, use those; otherwise, use the report's columns
        $columns = $options['columns'] ?? $report->columns;
        
        // Always include the ID field for reference
        if (!in_array('id', $columns)) {
            array_unshift($columns, 'id');
        }
        
        return array_unique($columns);
    }
    
    /**
     * Generate a cache key for the report data.
     *
     * @param  Report  $report
     * @param  array  $filters
     * @param  array  $options
     * @param  User|null  $user
     * @return string
     */
    protected function getCacheKey(Report $report, array $filters, array $options, ?User $user = null): string
    {
        $key = sprintf(
            'report_%s_%s_%s',
            $report->id,
            md5(json_encode($filters)),
            md5(json_encode($options))
        );
        
        if ($user) {
            $key .= '_user_' . $user->id;
        }
        
        // Store this cache key for later invalidation
        $this->storeCacheKey($key, $report);
        
        return $key;
    }
    /**
     * Clear the cache for a report.
     *
     * @param  Report  $report
     * @return void
     */
    public function clearCache(Report $report): void
    {
        $prefix = 'report_' . $report->id . '_';
        
        // Get all cache keys for this report
        $keys = Cache::get('report_cache_keys_' . $report->id, []);
        
        // Delete all cached data for this report
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        // Clear the list of keys
        Cache::forget('report_cache_keys_' . $report->id);
    }
    
    /**
     * Store a cache key for a report.
     *
     * @param  string  $key
     * @param  Report  $report
     * @return void
     */
    protected function storeCacheKey(string $key, Report $report): void
    {
        $keys = Cache::get('report_cache_keys_' . $report->id, []);
        
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            Cache::forever('report_cache_keys_' . $report->id, $keys);
        }
    }
}
