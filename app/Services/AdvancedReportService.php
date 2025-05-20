<?php

namespace App\Services;

use App\Exports\AssetsExport;
use App\Exports\ComplianceReportExport;
use App\Exports\CustomReportExport;
use App\Exports\FinancialReportExport;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Depreciation;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel;

class AdvancedReportService
{
    /**
     * Generate asset report with advanced filtering
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function generateAssetReport(array $filters)
    {
        $query = Asset::with([
            'category',
            'location',
            'department',
            'assignedTo',
            'supplier',
            'maintenances',
            'depreciations',
        ]);

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply sorting
        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        return $query;
    }

    /**
     * Generate a custom report based on user-defined parameters
     */
    public function generateCustomReport(
        string $reportType,
        array $columns,
        array $filters = [],
        ?string $groupBy = null,
        ?string $sortBy = null,
        string $sortOrder = 'asc'
    ): array {
        $query = Asset::query();

        // Apply common filters
        $this->applyFilters($query, $filters);

        // Apply report type specific logic
        switch ($reportType) {
            case 'financial':
                // Add financial specific filters and relationships
                $query->with(['depreciations', 'maintenances'])
                    ->where('purchase_cost', '>', 0);
                break;

            case 'maintenance':
                // Add maintenance specific filters and relationships
                $query->whereHas('maintenances')
                    ->with(['maintenances' => function ($q) {
                        $q->orderBy('completed_at', 'desc');
                    }]);
                break;

            case 'compliance':
                // Add compliance specific filters
                $query->where(function ($q) {
                    $q->whereNotNull('warranty_expiry')
                        ->orWhereHas('maintenances', function ($q) {
                            $q->where('completed_at', '<=', now()->subYear());
                        });
                });
                break;
        }

        // Apply grouping if specified
        if ($groupBy) {
            return $this->applyGrouping($query, $groupBy, $columns);
        }

        // Apply sorting
        if ($sortBy) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $data = $query->get();

        // Format the data according to selected columns
        return $this->formatReportData($data, $columns, $reportType);
    }

    /**
     * Apply grouping to the report data
     */
    protected function applyGrouping(Builder $query, string $groupBy, array $columns): array
    {
        $groupedData = [];

        switch ($groupBy) {
            case 'category':
                $groups = AssetCategory::withCount(['assets'])->get();
                foreach ($groups as $group) {
                    $groupedData[] = [
                        'group' => $group->name,
                        'count' => $group->assets_count,
                        'total_value' => $group->assets()->sum('purchase_cost'),
                    ];
                }
                break;

            case 'status':
                $statuses = Asset::select('status', DB::raw('count(*) as count'),
                    DB::raw('sum(purchase_cost) as total_value'))
                    ->groupBy('status')
                    ->get();

                foreach ($statuses as $status) {
                    $groupedData[] = [
                        'group' => $status->status ?? 'N/A',
                        'count' => $status->count,
                        'total_value' => $status->total_value,
                    ];
                }
                break;

            case 'location':
                $locations = Location::withCount(['assets'])->get();
                foreach ($locations as $location) {
                    $groupedData[] = [
                        'group' => $location->name,
                        'count' => $location->assets_count,
                        'total_value' => $location->assets()->sum('purchase_cost'),
                    ];
                }
                break;

            case 'department':
                $departments = Department::withCount(['assets'])->get();
                foreach ($departments as $dept) {
                    $groupedData[] = [
                        'group' => $dept->name,
                        'count' => $dept->assets_count,
                        'total_value' => $dept->assets()->sum('purchase_cost'),
                    ];
                }
                break;

            case 'year':
                $years = Asset::select(
                    DB::raw('YEAR(purchase_date) as year'),
                    DB::raw('count(*) as count'),
                    DB::raw('sum(purchase_cost) as total_value')
                )->groupBy('year')->get();

                foreach ($years as $year) {
                    $groupedData[] = [
                        'group' => $year->year,
                        'count' => $year->count,
                        'total_value' => $year->total_value,
                    ];
                }
                break;

            case 'month':
                $months = Asset::select(
                    DB::raw('DATE_FORMAT(purchase_date, "%Y-%m") as month'),
                    DB::raw('count(*) as count'),
                    DB::raw('sum(purchase_cost) as total_value')
                )->groupBy('month')->get();

                foreach ($months as $month) {
                    $groupedData[] = [
                        'group' => $month->month,
                        'count' => $month->count,
                        'total_value' => $month->total_value,
                    ];
                }
                break;
        }

        return $groupedData;
    }

    /**
     * Format the report data according to selected columns
     */
    protected function formatReportData(Collection $data, array $columns, string $reportType): array
    {
        $formattedData = [];

        foreach ($data as $item) {
            $row = [];

            foreach ($columns as $column) {
                switch ($column) {
                    case 'name':
                        $row[$column] = $item->name;
                        break;

                    case 'asset_tag':
                        $row[$column] = $item->asset_tag;
                        break;

                    case 'serial_number':
                        $row[$column] = $item->serial_number;
                        break;

                    case 'category':
                        $row[$column] = $item->category ? $item->category->name : 'N/A';
                        break;

                    case 'status':
                        $row[$column] = $item->status;
                        break;

                    case 'location':
                        $row[$column] = $item->location ? $item->location->name : 'N/A';
                        break;

                    case 'department':
                        $row[$column] = $item->department ? $item->department->name : 'N/A';
                        break;

                    case 'assigned_to':
                        $row[$column] = $item->assignedTo ? $item->assignedTo->name : 'Unassigned';
                        break;

                    case 'purchase_date':
                        $row[$column] = $item->purchase_date ? $item->purchase_date->format('Y-m-d') : 'N/A';
                        break;

                    case 'purchase_cost':
                        $row[$column] = number_format($item->purchase_cost, 2);
                        break;

                    case 'current_value':
                        $currentValue = $this->calculateCurrentValue($item);
                        $row[$column] = number_format($currentValue, 2);
                        break;

                    case 'warranty_expiry':
                        $row[$column] = $item->warranty_expiry ? $item->warranty_expiry->format('Y-m-d') : 'N/A';
                        break;

                    case 'notes':
                        $row[$column] = $item->notes;
                        break;
                }
            }

            // Add report type specific data
            if ($reportType === 'financial') {
                $row['depreciation'] = $this->calculateDepreciation($item);
                $row['maintenance_costs'] = $this->calculateMaintenanceCosts($item);
            } elseif ($reportType === 'maintenance') {
                $lastMaintenance = $item->maintenances->first();
                $row['last_maintenance_date'] = $lastMaintenance ? $lastMaintenance->completed_at->format('Y-m-d') : 'N/A';
                $row['maintenance_count'] = $item->maintenances->count();
            } elseif ($reportType === 'compliance') {
                $row['is_warranty_valid'] = $item->warranty_expiry ?
                    ($item->warranty_expiry->isFuture() ? 'Yes' : 'No') : 'N/A';
                $row['last_inspection'] = $item->maintenances->max('completed_at')?->format('Y-m-d') ?? 'N/A';
            }

            $formattedData[] = $row;
        }

        return $formattedData;
    }

    /**
     * Calculate current value of an asset based on depreciation
     */
    protected function calculateCurrentValue(Asset $asset): float
    {
        if (! $asset->purchase_date || ! $asset->purchase_cost) {
            return 0;
        }

        $yearsOld = now()->diffInYears($asset->purchase_date);
        $depreciationRate = $asset->depreciation_rate ?? 10; // Default to 10% per year if not set

        $currentValue = $asset->purchase_cost;
        for ($i = 0; $i < $yearsOld; $i++) {
            $currentValue -= ($currentValue * ($depreciationRate / 100));
        }

        return max(0, $currentValue);
    }

    /**
     * Calculate total maintenance costs for an asset
     */
    protected function calculateMaintenanceCosts(Asset $asset): float
    {
        return $asset->maintenances->sum('cost');
    }

    /**
     * Calculate total depreciation for an asset
     */
    protected function calculateDepreciation(Asset $asset): float
    {
        if (! $asset->purchase_date || ! $asset->purchase_cost) {
            return 0;
        }

        $currentValue = $this->calculateCurrentValue($asset);

        return $asset->purchase_cost - $currentValue;
    }

    /**
     * Get asset count grouped by category
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function getCountByCategory($query): array
    {
        $counts = (clone $query)
            ->select('category_id', DB::raw('count(*) as count'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->category->name => $item->count];
            });

        return $counts->toArray();
    }

    /**
     * Get asset count grouped by status
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function getCountByStatus($query): array
    {
        return (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get asset count grouped by location
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function getCountByLocation($query): array
    {
        $counts = (clone $query)
            ->select('location_id', DB::raw('count(*) as count'))
            ->groupBy('location_id')
            ->with('location')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->location ? $item->location->name : 'Unassigned' => $item->count];
            });

        return $counts->toArray();
    }

    /**
     * Get asset count grouped by department
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function getCountByDepartment($query): array
    {
        $counts = (clone $query)
            ->select('department_id', DB::raw('count(*) as count'))
            ->groupBy('department_id')
            ->with('department')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->department ? $item->department->name : 'Unassigned' => $item->count];
            });

        return $counts->toArray();
    }

    /**
     * Get total asset value by month for the past year
     */
    public function getMonthlyAssetValue(): array
    {
        $cacheKey = 'monthly_asset_value_'.now()->format('Y-m');

        return Cache::remember($cacheKey, now()->addDay(), function () {
            $data = Asset::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(purchase_cost) as total_value')
            )
                ->where('created_at', '>=', now()->subYear())
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            return $data->map(function ($item) {
                return [
                    'month' => Carbon::createFromDate($item->year, $item->month, 1)->format('M Y'),
                    'value' => (float) $item->total_value,
                ];
            })->toArray();
        });
    }

    /**
     * Get maintenance statistics
     */
    public function getMaintenanceStats(): array
    {
        $cacheKey = 'maintenance_stats_'.now()->format('Y-m');

        return Cache::remember($cacheKey, now()->addDay(), function () {
            $stats = [];

            // Total maintenance count
            $stats['total_maintenances'] = Maintenance::count();

            // Average maintenance cost
            $stats['avg_maintenance_cost'] = Maintenance::avg('cost') ?? 0;

            // Maintenance by type
            $stats['by_type'] = Maintenance::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            // Maintenance by status
            $stats['by_status'] = Maintenance::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Upcoming maintenances
            $stats['upcoming'] = Maintenance::where('scheduled_date', '>=', now())
                ->where('scheduled_date', '<=', now()->addDays(30))
                ->count();

            // Overdue maintenances
            $stats['overdue'] = Maintenance::where('scheduled_date', '<', now())
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->count();

            return $stats;
        });
    }

    /**
     * Get depreciation statistics
     */
    public function getDepreciationStats(): array
    {
        $cacheKey = 'depreciation_stats_'.now()->format('Y-m');

        return Cache::remember($cacheKey, now()->addDay(), function () {
            $stats = [];

            // Total depreciating assets
            $assets = Asset::whereHas('depreciations')->with('depreciations')->get();

            $stats['total_depreciating_assets'] = $assets->count();
            $stats['total_original_value'] = $assets->sum('purchase_cost');
            $stats['total_current_value'] = $assets->sum(function ($asset) {
                return $this->calculateCurrentValue($asset);
            });
            $stats['total_depreciation'] = $assets->sum(function ($asset) {
                return $this->calculateDepreciation($asset);
            });

            // Depreciation by category
            $stats['by_category'] = [];
            $categories = AssetCategory::with(['assets' => function ($q) {
                $q->whereHas('depreciations');
            }])->get();

            foreach ($categories as $category) {
                if ($category->assets->isNotEmpty()) {
                    $originalValue = $category->assets->sum('purchase_cost');
                    $currentValue = $category->assets->sum(function ($asset) {
                        return $this->calculateCurrentValue($asset);
                    });

                    $stats['by_category'][$category->name] = [
                        'count' => $category->assets->count(),
                        'original_value' => $originalValue,
                        'current_value' => $currentValue,
                        'depreciation' => $originalValue - $currentValue,
                    ];
                }
            }

            return $stats;
        });
    }

    /**
     * Apply advanced filters to the query
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyFilters($query, array $filters): void
    {
        // Text search
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('asset_tag', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Category filter
        if (! empty($filters['category_id'])) {
            $categories = is_array($filters['category_id']) ? $filters['category_id'] : [$filters['category_id']];
            $query->whereIn('category_id', $categories);
        }

        // Status filter
        if (! empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
            $query->whereIn('status', $statuses);
        }

        // Location filter
        if (! empty($filters['location_id'])) {
            $locations = is_array($filters['location_id']) ? $filters['location_id'] : [$filters['location_id']];
            $query->whereIn('location_id', $locations);
        }

        // Department filter
        if (! empty($filters['department_id'])) {
            $departments = is_array($filters['department_id']) ? $filters['department_id'] : [$filters['department_id']];
            $query->whereIn('department_id', $departments);
        }

        // Supplier filter
        if (! empty($filters['supplier_id'])) {
            $suppliers = is_array($filters['supplier_id']) ? $filters['supplier_id'] : [$filters['supplier_id']];
            $query->whereIn('supplier_id', $suppliers);
        }

        // Date range filters
        if (! empty($filters['date_from'])) {
            $query->whereDate('purchase_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('purchase_date', '<=', $filters['date_to']);
        }

        // Price range filters
        if (! empty($filters['min_price'])) {
            $query->where('purchase_cost', '>=', $filters['min_price']);
        }
        if (! empty($filters['max_price'])) {
            $query->where('purchase_cost', '<=', $filters['max_price']);
        }

        // Warranty status filter
        if (isset($filters['warranty_status'])) {
            if ($filters['warranty_status'] === 'expired') {
                $query->whereNotNull('warranty_expiry')
                    ->where('warranty_expiry', '<', now());
            } elseif ($filters['warranty_status'] === 'active') {
                $query->whereNotNull('warranty_expiry')
                    ->where('warranty_expiry', '>=', now());
            }
        }

        // Warranty filters
        if (isset($filters['under_warranty'])) {
            $query->whereNotNull('warranty_expiry_date')
                ->where('warranty_expiry_date', '>=', now());
        }
    }

    /**
     * Export report to different formats
     *
     * @return mixed
     */
    public function exportReport(string $format, array $filters)
    {
        $data = $this->generateAssetReport($filters);
        $filename = 'assets-report-'.now()->format('Y-m-d-H-i-s');

        return Excel::download(
            new AssetsExport($data),
            $filename.'.'.strtolower($format),
            $this->getExcelWriterType($format)
        );
    }

    /**
     * Get Excel writer type based on format
     */
    protected function getExcelWriterType(string $format): string
    {
        return match (strtolower($format)) {
            'csv' => Excel::CSV,
            'pdf' => Excel::DOMPDF,
            'html' => Excel::HTML,
            'xlsx' => Excel::XLSX,
            'xls' => Excel::XLS,
            default => Excel::XLSX,
        };
    }

    /**
     * Get filter options for the report form
     */
    public function getFilterOptions(): array
    {
        return [
            'categories' => AssetCategory::orderBy('name')->pluck('name', 'id'),
            'statuses' => Asset::distinct()->pluck('status'),
            'locations' => Location::orderBy('name')->pluck('name', 'id'),
            'departments' => Department::orderBy('name')->pluck('name', 'id'),
            'years' => $this->getPurchaseYears(),
        ];
    }

    /**
     * Get unique years from purchase dates
     */
    protected function getPurchaseYears(): array
    {
        return Asset::select(DB::raw('YEAR(purchase_date) as year'))
            ->distinct()
            ->whereNotNull('purchase_date')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
    }

    /**
     * Get report statistics
     */
    public function getReportStatistics(array $filters): array
    {
        $query = Asset::query();
        $this->applyFilters($query, $filters);

        return [
            'total_assets' => (clone $query)->count(),
            'total_value' => (clone $query)->sum('purchase_cost'),
            'by_category' => $this->getCountByCategory($query),
            'by_status' => $this->getCountByStatus($query),
            'by_location' => $this->getCountByLocation($query),
        ];
    }

    /**
     * Get asset count by category
     */
    protected function getCountByCategory($query): Collection
    {
        return (clone $query)
            ->select('category_id', DB::raw('count(*) as count'))
            ->with('category')
            ->groupBy('category_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->category->name => $item->count];
            });
    }

    /**
     * Get asset count by status
     */
    protected function getCountByStatus($query): Collection
    {
        return (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');
    }

    /**
     * Get asset count by location
     */
    protected function getCountByLocation($query): Collection
    {
        return (clone $query)
            ->select('location_id', DB::raw('count(*) as count'))
            ->with('location')
            ->groupBy('location_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->location->name => $item->count];
            });
    }
}
