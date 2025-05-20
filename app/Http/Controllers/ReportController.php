<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use App\Models\Maintenance;
use App\Models\Depreciation;
use App\Models\Supplier;
use App\Services\AdvancedReportService;
use App\Services\ReportExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelType;
use App\Exports\AssetsExport;
use App\Exports\FinancialReportExport;
use App\Exports\ComplianceReportExport;
use App\Exports\CustomReportExport;
use Inertia\Inertia;

class ReportController extends Controller
{
    /**
     * The report exporter service.
     *
     * @var ReportExporter
     */
    protected $reportExporter;

    /**
     * Create a new controller instance.
     *
     * @param  ReportExporter  $reportExporter
     * @return void
     */
    public function __construct(ReportExporter $reportExporter)
    {
        $this->reportExporter = $reportExporter;
    }

    /**
     * Display the reports dashboard.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $stats = [
            'total_assets' => [
                'value' => number_format(Asset::count()),
                'label' => 'Total Assets',
                'trend' => 'up',
                'change' => '5%',
                'icon' => 'CollectionIcon',
            ],
            'total_value' => [
                'value' => '\$' . number_format(Asset::sum('purchase_cost'), 2),
                'label' => 'Total Value',
                'trend' => 'up',
                'change' => '12%',
                'icon' => 'CurrencyDollarIcon',
            ],
            'assets_due_for_maintenance' => [
                'value' => number_format(Maintenance::where('due_date', '<=', now()->addDays(30))->count()),
                'label' => 'Due for Maintenance',
                'trend' => 'down',
                'change' => '3%',
                'icon' => 'ClockIcon',
            ],
            'depreciating_assets' => [
                'value' => number_format(Depreciation::where('end_date', '>=', now())->count()),
                'label' => 'Depreciating Assets',
                'trend' => 'up',
                'change' => '8%',
                'icon' => 'TrendingDownIcon',
            ],
        ];

        // Get available filters
        $filters = [
            'date_range' => [
                'start' => now()->subYear()->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
            'categories' => AssetCategory::select('id', 'name')->get()->toArray(),
            'statuses' => Asset::select('status as id', 'status as name')
                ->distinct()
                ->get()
                ->toArray(),
            'locations' => Location::select('id', 'name')
                ->get()
                ->toArray(),
            'departments' => Department::select('id', 'name')
                ->get()
                ->toArray(),
        ];

        return Inertia::render('Reports/Index', [
            'stats' => $stats,
            'filters' => $filters,
            'report_columns' => $this->getReportColumns(),
        ]);
    }

    /**
     * Generate asset reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    /**
     * Get the columns for the report.
     *
     * @return array
     */
    protected function getReportColumns()
    {
        return [
            ['key' => 'id', 'label' => 'ID', 'type' => 'text'],
            ['key' => 'name', 'label' => 'Asset Name', 'type' => 'text', 'searchable' => true],
            ['key' => 'serial_number', 'label' => 'Serial Number', 'type' => 'text', 'searchable' => true],
            ['key' => 'category.name', 'label' => 'Category', 'type' => 'text'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'badge'],
            ['key' => 'purchase_date', 'label' => 'Purchase Date', 'type' => 'date'],
            ['key' => 'purchase_cost', 'label' => 'Purchase Cost', 'type' => 'currency'],
            ['key' => 'location.name', 'label' => 'Location', 'type' => 'text'],
            ['key' => 'assigned_to_user.name', 'label' => 'Assigned To', 'type' => 'text'],
        ];
    }

    /**
     * Generate asset reports with advanced filtering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Inertia\Response
     */
    public function assets(Request $request)
    {
        $request->validate([
            'format' => 'nullable|in:pdf,excel,csv,json',
            'category_id' => 'nullable|exists:asset_categories,id',
            'status' => 'nullable|string',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gt:min_price',
            'search' => 'nullable|string|max:255',
            'include_images' => 'boolean',
            'include_documents' => 'boolean',
            'per_page' => 'nullable|integer|min:1|max:1000',
            'sort_by' => 'nullable|string',
            'sort_direction' => 'nullable|in:asc,desc',
            'columns' => 'nullable|array',
            'columns.*' => 'string',
        ]);

        // Build the base query with relationships
        $query = Asset::with([
            'category',
            'location',
            'assignedToUser',
            'maintenances',
            'depreciation',
        ]);

        // Apply filters
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('serial_number', 'like', "%{$searchTerm}%")
                  ->orWhere('model_number', 'like', "%{$searchTerm}%")
                  ->orWhere('notes', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('department_id')) {
            $query->whereHas('assignedToUser', function($q) use ($request) {
                $q->where('department_id', $request->input('department_id'));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('min_price')) {
            $query->where('purchase_cost', '>=', $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('purchase_cost', '<=', $request->input('max_price'));
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // Handle export if requested
        if ($request->has('format')) {
            $assets = $query->get();
            $columns = $request->input('columns', $this->getReportColumns());
            
            // Transform data for export
            $exportData = $assets->map(function($asset) use ($columns) {
                $data = [];
                foreach ($columns as $column) {
                    $key = $column;
                    if (is_array($column)) {
                        $key = $column['key'];
                    }
                    $data[$key] = data_get($asset, $key);
                }
                return $data;
            });
            
            return $this->reportExporter->export(
                $request->input('format'),
                $exportData,
                is_array($columns[0] ?? null) ? $columns : $this->getReportColumns(),
                'assets_report_' . now()->format('Y-m-d')
            );
        }

        // Return paginated results for the web interface
        $perPage = $request->input('per_page', 15);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'columns' => $this->getReportColumns(),
        ]);
    }

    /**
     * Generate a custom report based on user-defined parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Inertia\Response
     */
    public function customReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:inventory,financial,maintenance,compliance,custom',
            'columns' => 'required|array|min:1',
            'columns.*' => 'required|string|in:name,asset_tag,serial_number,category,status,location,department,assigned_to,purchase_date,purchase_cost,current_value,warranty_expiry,notes',
            'filters' => 'nullable|array',
            'format' => 'nullable|in:pdf,excel,csv,json',
            'group_by' => 'nullable|string|in:category,status,location,department,year,month',
            'sort_by' => 'nullable|string',
            'sort_order' => 'nullable|in:asc,desc',
        ]);
        
        $reportService = new AdvancedReportService();
        $filters = $request->input('filters', []);
        
        // Generate the report data
        $reportData = $reportService->generateCustomReport(
            $request->report_type,
            $request->columns,
            $filters,
            $request->group_by,
            $request->sort_by,
            $request->sort_order
        );
        
        // If JSON is requested, return the data directly
        if ($request->wantsJson() || $request->input('format') === 'json') {
            return response()->json([
                'success' => true,
                'data' => $reportData,
                'meta' => [
                    'report_type' => $request->report_type,
                    'columns' => $request->columns,
                    'filters' => $filters,
                    'group_by' => $request->group_by,
                    'generated_at' => now()->toDateTimeString(),
                ]
            ]);
        }
        
        // If no format is specified, return the view
        if (!$request->has('format')) {
            return inertia('Reports/Custom', [
                'reportData' => $reportData,
                'filters' => $filters,
                'reportType' => $request->report_type,
                'columns' => $request->columns,
                'groupBy' => $request->group_by,
                'availableColumns' => [
                    'name' => 'Asset Name',
                    'asset_tag' => 'Asset Tag',
                    'serial_number' => 'Serial Number',
                    'category' => 'Category',
                    'status' => 'Status',
                    'location' => 'Location',
                    'department' => 'Department',
                    'assigned_to' => 'Assigned To',
                    'purchase_date' => 'Purchase Date',
                    'purchase_cost' => 'Purchase Cost',
                    'current_value' => 'Current Value',
                    'warranty_expiry' => 'Warranty Expiry',
                    'notes' => 'Notes',
                ],
                'filterOptions' => [
                    'categories' => AssetCategory::orderBy('name')->pluck('name', 'id'),
                    'statuses' => Asset::distinct()->pluck('status')->filter()->values(),
                    'locations' => Location::orderBy('name')->pluck('name', 'id'),
                    'departments' => Department::orderBy('name')->pluck('name', 'id'),
                    'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
                ],
            ]);
        }
        
        // Prepare for export
        $export = new CustomReportExport($reportData, $request->columns, $request->report_type);
        $fileName = 'custom-report-' . Str::slug($request->report_type) . '-' . now()->format('Y-m-d');
        
        switch ($request->format) {
            case 'pdf':
                $pdf = PDF::loadView('exports.custom.pdf', [
                    'reportData' => $reportData,
                    'columns' => $request->columns,
                    'reportType' => $request->report_type,
                    'filters' => $filters,
                    'generated_at' => now(),
                    'generated_by' => Auth::user()->name,
                ]);
                return $pdf->download($fileName . '.pdf');
                
            case 'csv':
                return Excel::download($export, $fileName . '.csv', ExcelType::CSV);
                
            case 'xlsx':
            default:
                return Excel::download($export, $fileName . '.xlsx', ExcelType::XLSX);
        }
    }

    /**
     * Generate financial reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function financial(Request $request)
    {
        $request->validate([
            'format' => 'required|in:pdf,excel,csv',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 5),
        ]);

        $year = $request->year;
        $months = collect(range(1, 12))->mapWithKeys(function ($month) use ($year) {
            $date = Carbon::create($year, $month, 1);
            $start = $date->startOfMonth()->format('Y-m-d');
            $end = $date->endOfMonth()->format('Y-m-d');
            
            $purchases = Asset::whereBetween('purchase_date', [$start, $end])->sum('purchase_cost');
            $depreciation = Depreciation::whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('amount');
                
            return [
                $date->format('M') => [
                    'purchases' => $purchases,
                    'depreciation' => $depreciation,
                    'total' => $purchases + $depreciation,
                ]
            ];
        });

        $data = [
            'year' => $year,
            'months' => $months,
            'total_purchases' => $months->sum('purchases'),
            'total_depreciation' => $months->sum('depreciation'),
            'grand_total' => $months->sum('total'),
            'generated_at' => now(),
            'generated_by' => Auth::user()->name,
        ];

        if ($request->format === 'pdf') {
            $pdf = PDF::loadView('reports.financial.pdf', $data);
            return $pdf->download('financial-report-' . $year . '.pdf');
        }

        $export = new FinancialReportExport($data);
        $format = $request->format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        
        return Excel::download($export, 'financial-report-' . $year . '.' . $request->format, $format);
    }

    /**
     * Generate compliance reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function compliance(Request $request)
    {
        $request->validate([
            'format' => 'required|in:pdf,excel,csv',
        ]);

        // Get assets with expired or expiring soon warranties and maintenances
        $warrantyThreshold = now()->addDays(90);
        $maintenanceThreshold = now()->addDays(30);

        $assets = Asset::with(['category', 'warranty', 'maintenances'])
            ->whereHas('warranty', function ($q) use ($warrantyThreshold) {
                $q->where('expires_at', '<=', $warrantyThreshold);
            })
            ->orWhereHas('maintenances', function ($q) use ($maintenanceThreshold) {
                $q->where('scheduled_date', '<=', $maintenanceThreshold);
            })
            ->get();

        $data = [
            'assets' => $assets,
            'warranty_threshold' => $warrantyThreshold,
            'maintenance_threshold' => $maintenanceThreshold,
            'generated_at' => now(),
            'generated_by' => Auth::user()->name,
        ];

        if ($request->format === 'pdf') {
            $pdf = PDF::loadView('reports.compliance.pdf', $data);
            return $pdf->download('compliance-report-' . now()->format('Y-m-d') . '.pdf');
        }

        $export = new ComplianceReportExport($data);
        $format = $request->format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        
        return Excel::download($export, 'compliance-report-' . now()->format('Y-m-d') . '.' . $request->format, $format);
    }

    /**
     * Generate custom reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function custom(Request $request)
    {
        $request->validate([
            'format' => 'required|in:pdf,excel,csv',
            'report_type' => 'required|in:asset_audit,depreciation_schedule,maintenance_history',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'category_id' => 'nullable|exists:asset_categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $data = [
            'report_type' => $request->report_type,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'filters' => $request->only(['category_id', 'location_id', 'department_id']),
            'generated_at' => now(),
            'generated_by' => Auth::user()->name,
        ];

        // Generate report based on type
        switch ($request->report_type) {
            case 'asset_audit':
                $data['assets'] = $this->getAssetAuditData($request);
                $view = 'reports.custom.asset-audit';
                $filename = 'asset-audit-report-' . now()->format('Y-m-d');
                break;
                
            case 'depreciation_schedule':
                $data['depreciations'] = $this->getDepreciationScheduleData($request);
                $view = 'reports.custom.depreciation-schedule';
                $filename = 'depreciation-schedule-' . now()->format('Y-m-d');
                break;
                
            case 'maintenance_history':
                $data['maintenances'] = $this->getMaintenanceHistoryData($request);
                $view = 'reports.custom.maintenance-history';
                $filename = 'maintenance-history-' . now()->format('Y-m-d');
                break;
        }

        if ($request->format === 'pdf') {
            $pdf = PDF::loadView($view, $data);
            return $pdf->download($filename . '.pdf');
        }

        $exportClass = 'App\\Exports\\' . ucfirst(camel_case($request->report_type)) . 'Export';
        $export = new $exportClass($data);
        $format = $request->format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        
        return Excel::download($export, $filename . '.' . $request->format, $format);
    }

    /**
     * Get asset audit data for custom reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAssetAuditData($request)
    {
        return Asset::with(['category', 'location', 'department', 'assignedTo'])
            ->when($request->category_id, function ($q) use ($request) {
                return $q->where('category_id', $request->category_id);
            })
            ->when($request->location_id, function ($q) use ($request) {
                return $q->where('location_id', $request->location_id);
            })
            ->when($request->department_id, function ($q) use ($request) {
                return $q->where('department_id', $request->department_id);
            })
            ->when($request->date_from, function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->date_to, function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->get();
    }

    /**
     * Get depreciation schedule data for custom reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getDepreciationScheduleData($request)
    {
        return Depreciation::with(['asset.category', 'asset.location'])
            ->when($request->date_from, function ($q) use ($request) {
                return $q->whereDate('date', '>=', $request->date_from);
            })
            ->when($request->date_to, function ($q) use ($request) {
                return $q->whereDate('date', '<=', $request->date_to);
            })
            ->when($request->category_id, function ($q) use ($request) {
                return $q->whereHas('asset', function ($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            })
            ->get();
    }

    /**
     * Get maintenance history data for custom reports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getMaintenanceHistoryData($request)
    {
        return Maintenance::with(['asset', 'technician', 'location'])
            ->when($request->date_from, function ($q) use ($request) {
                return $q->whereDate('start_date', '>=', $request->date_from);
            })
            ->when($request->date_to, function ($q) use ($request) {
                return $q->whereDate('start_date', '<=', $request->date_to);
            })
            ->when($request->category_id, function ($q) use ($request) {
                return $q->whereHas('asset', function ($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            })
            ->get();
    }

    /**
     * Track export progress
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportProgress()
    {
        try {
            $progress = Session::get('export_progress', 0);
            
            return response()->json([
                'success' => true,
                'progress' => (float)$progress,
                'status' => $progress >= 100 ? 'completed' : 'processing',
                'estimated_time_remaining' => $this->calculateRemainingTime($progress)
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking export progress: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check export progress.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Calculate remaining time for export
     *
     * @param  float  $progress
     * @return string
     */
    protected function calculateRemainingTime($progress)
    {
        if ($progress <= 0) return 'Calculating...';
        if ($progress >= 100) return 'Almost done...';
        
        $elapsedTime = microtime(true) - (Session::get('export_start_time', microtime(true)));
        $estimatedTotalTime = $elapsedTime / ($progress / 100);
        $remainingSeconds = max(1, round(($estimatedTotalTime - $elapsedTime) / 1000));
        
        if ($remainingSeconds < 60) {
            return "About {$remainingSeconds} seconds remaining";
        }
        
        $minutes = ceil($remainingSeconds / 60);
        return "About {$minutes} minute".($minutes > 1 ? 's' : '')." remaining";
    }

    /**
     * Export the report in the specified format.
     *
     * @param  string  $id
     * @param  string  $format
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     * @throws \Exception
     */
    public function export($id, $format = 'pdf')
    {
        // Set initial progress
        Session::put('export_progress', 0);
        Session::put('export_start_time', microtime(true));
        
        try {
            $report = $this->getReportWithCache($id);
            
            if (!$report->is_public && $report->created_by !== Auth::id()) {
                abort(403, 'You do not have permission to export this report.');
            }

            // Get total count for progress
            $query = $this->buildReportQuery($report);
            $total = $query->count();
            $chunkSize = 500;
            $data = collect();
            $processed = 0;

            // Process data in chunks and update progress
            $query->chunk($chunkSize, function ($items) use (&$data, &$processed, $total) {
                $data = $data->merge($items);
                $processed += $items->count();
                $progress = min(95, round(($processed / $total) * 90, 2)); // Cap at 95% until complete
                
                // Update progress in session
                Session::put('export_progress', $progress);
                
                // Small delay to allow UI updates
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                
                // Small delay to prevent server overload
                if (function_exists('usleep')) {
                    usleep(100000); // 100ms delay
                }
            });
            
            // Mark as complete
            Session::put('export_progress', 100);

            // Prepare the data for export
            $exportData = $data->map(function($item) use ($report) {
                $row = [];
                foreach ($report->columns as $column) {
                    // Handle nested relationships (e.g., 'user.name')
                    if (str_contains($column, '.')) {
                        $value = $item;
                        foreach (explode('.', $column) as $segment) {
                            $value = $value->$segment ?? null;
                            if ($value === null) break;
                        }
                        $row[$column] = $value;
                    } else {
                        $row[$column] = $item->$column ?? '';
                    }
                }
                return $row;
            })->toArray();

            $filename = Str::slug($report->name) . '-' . now()->format('Y-m-d');
            $export = new ReportExport($exportData, $report->columns, $report->name);

            switch (strtolower($format)) {
                case 'pdf':
                    // For PDF, we'll generate a simple HTML table
                    $html = view('reports.exports.pdf', [
                        'report' => $report,
                        'data' => $exportData,
                        'columns' => $report->columns,
                        'generatedAt' => now()->format('Y-m-d H:i:s'),
                    ]);
                    
                    $pdf = PDF::loadHTML($html);
                    return $pdf->download($filename . '.pdf');
                    
                case 'excel':
                    return Excel::download($export, $filename . '.xlsx');
                    
                case 'csv':
                    return Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
                    
                default:
                    throw new \Exception('Invalid export format specified.');
            }

        } catch (\Exception $e) {
            Log::error("Failed to export report {$id} as {$format}: " . $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Failed to export report. Please try again later.');
        }
    }

    // ... [Rest of the controller methods remain unchanged]
}
