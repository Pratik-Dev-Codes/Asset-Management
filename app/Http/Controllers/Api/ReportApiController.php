<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Category;
use App\Services\ReportExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportApiController extends Controller
{
    protected $reportExporter;

    public function __construct(ReportExporter $reportExporter)
    {
        $this->reportExporter = $reportExporter;
        $this->middleware('auth:api');
    }

    /**
     * Get assets report data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function assets(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'category_id' => 'nullable|exists:categories,id',
                'status' => 'nullable|string',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0|gt:min_price',
                'search' => 'nullable|string|max:255',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
            ]);

            // Base query with relationships
            $query = Asset::with([
                'category',
                'location',
                'department',
                'assignedTo',
                'maintenances' => function ($query) {
                    $query->latest()->limit(5);
                },
            ]);

            // Apply filters
            if ($request->filled('date_from')) {
                $query->where('purchase_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('purchase_date', '<=', $request->date_to);
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('min_price')) {
                $query->where('purchase_cost', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('purchase_cost', '<=', $request->max_price);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_tag', 'like', "%{$search}%")
                        ->orWhere('serial', 'like', "%{$search}%")
                        ->orWhere('model_number', 'like', "%{$search}%");
                });
            }

            // Get paginated results
            $perPage = $request->input('per_page', 15);
            $assets = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Get filter options for the frontend
            $filters = [
                'categories' => Category::orderBy('name')->pluck('name', 'id'),
                'statuses' => [
                    'deployed' => 'Deployed',
                    'pending' => 'Pending',
                    'archived' => 'Archived',
                    'out_of_service' => 'Out of Service',
                ],
            ];

            // Calculate stats for the dashboard
            $stats = [
                'total_assets' => Asset::count(),
                'total_value' => Asset::sum('purchase_cost'),
                'deployed_assets' => Asset::where('status', 'deployed')->count(),
                'pending_assets' => Asset::where('status', 'pending')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'assets' => $assets->items(),
                    'pagination' => [
                        'current_page' => $assets->currentPage(),
                        'last_page' => $assets->lastPage(),
                        'per_page' => $assets->perPage(),
                        'total' => $assets->total(),
                    ],
                    'filters' => $filters,
                    'stats' => $stats,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assets report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get financial report data
     */
    public function financial(Request $request)
    {
        try {
            $validated = $request->validate([
                'year' => 'nullable|integer|min:2000|max:'.(date('Y') + 5),
                'category_id' => 'nullable|exists:categories,id',
            ]);

            $year = $request->input('year', date('Y'));

            // Get monthly spending data
            $monthlyData = Asset::select(
                DB::raw('MONTH(purchase_date) as month'),
                DB::raw('SUM(purchase_cost) as total_cost'),
                DB::raw('COUNT(*) as asset_count')
            )
                ->whereYear('purchase_date', $year)
                ->when($request->filled('category_id'), function ($query) use ($request) {
                    return $query->where('category_id', $request->category_id);
                })
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Get category breakdown
            $categoryData = Asset::select(
                'categories.name as category_name',
                DB::raw('SUM(assets.purchase_cost) as total_cost'),
                DB::raw('COUNT(*) as asset_count')
            )
                ->join('categories', 'assets.category_id', '=', 'categories.id')
                ->whereYear('assets.purchase_date', $year)
                ->when($request->filled('category_id'), function ($query) use ($request) {
                    return $query->where('assets.category_id', $request->category_id);
                })
                ->groupBy('categories.name')
                ->orderBy('total_cost', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'year' => $year,
                    'monthly_data' => $monthlyData,
                    'category_data' => $categoryData,
                    'summary' => [
                        'total_spent' => $monthlyData->sum('total_cost'),
                        'total_assets' => $monthlyData->sum('asset_count'),
                        'avg_asset_cost' => $monthlyData->sum('total_cost') / max(1, $monthlyData->sum('asset_count')),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch financial report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get compliance report data
     */
    public function compliance(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'nullable|in:all,expired,expiring_soon,compliant',
                'days' => 'nullable|integer|min:1|max:365',
            ]);

            $type = $request->input('type', 'all');
            $days = $request->input('days', 30);
            $now = now();

            $query = Asset::with(['category', 'assignedTo']);

            switch ($type) {
                case 'expired':
                    $query->where('warranty_expires', '<', $now)
                        ->whereNotNull('warranty_expires');
                    break;
                case 'expiring_soon':
                    $query->whereBetween('warranty_expires', [
                        $now,
                        $now->copy()->addDays($days),
                    ]);
                    break;
                case 'compliant':
                    $query->where(function ($q) use ($now) {
                        $q->where('warranty_expires', '>', $now)
                            ->orWhereNull('warranty_expires');
                    });
                    break;
            }

            $assets = $query->orderBy('warranty_expires', 'asc')
                ->paginate($request->input('per_page', 15));

            // Calculate compliance stats
            $totalAssets = Asset::count();
            $expiredCount = Asset::where('warranty_expires', '<', $now)
                ->whereNotNull('warranty_expires')
                ->count();
            $expiringSoonCount = Asset::whereBetween('warranty_expires', [
                $now,
                $now->copy()->addDays($days),
            ])->count();
            $compliantCount = $totalAssets - $expiredCount - $expiringSoonCount;

            return response()->json([
                'success' => true,
                'data' => [
                    'assets' => $assets->items(),
                    'pagination' => [
                        'current_page' => $assets->currentPage(),
                        'last_page' => $assets->lastPage(),
                        'per_page' => $assets->perPage(),
                        'total' => $assets->total(),
                    ],
                    'stats' => [
                        'total_assets' => $totalAssets,
                        'expired_count' => $expiredCount,
                        'expiring_soon_count' => $expiringSoonCount,
                        'compliant_count' => max(0, $compliantCount),
                        'compliance_percentage' => $totalAssets > 0
                            ? round(($compliantCount / $totalAssets) * 100, 2)
                            : 0,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch compliance report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export report data in various formats
     */
    public function export(Request $request, $format)
    {
        try {
            $validated = $request->validate([
                'report_type' => 'required|in:assets,financial,compliance',
                // Add other validation rules based on report type
            ]);

            $data = [];
            $columns = [];
            $filename = '';

            // Fetch data based on report type
            switch ($request->report_type) {
                case 'assets':
                    $response = $this->assets($request);
                    $data = json_decode($response->content(), true);
                    $columns = [
                        'id' => 'ID',
                        'asset_tag' => 'Asset Tag',
                        'name' => 'Name',
                        'serial' => 'Serial',
                        'model_number' => 'Model',
                        'purchase_date' => 'Purchase Date',
                        'purchase_cost' => 'Cost',
                        'status' => 'Status',
                        'category.name' => 'Category',
                        'assignedTo.name' => 'Assigned To',
                    ];
                    $filename = 'assets_report_'.date('Y-m-d');
                    break;

                case 'financial':
                    $response = $this->financial($request);
                    $data = json_decode($response->content(), true);
                    $columns = [
                        'month' => 'Month',
                        'total_cost' => 'Total Cost',
                        'asset_count' => 'Asset Count',
                    ];
                    $filename = 'financial_report_'.date('Y-m-d');
                    break;

                case 'compliance':
                    $response = $this->compliance($request);
                    $data = json_decode($response->content(), true);
                    $columns = [
                        'asset_tag' => 'Asset Tag',
                        'name' => 'Name',
                        'serial' => 'Serial',
                        'warranty_expires' => 'Warranty Expires',
                        'days_until_expiry' => 'Days Until Expiry',
                        'category.name' => 'Category',
                        'assignedTo.name' => 'Assigned To',
                    ];
                    $filename = 'compliance_report_'.date('Y-m-d');
                    break;
            }

            if (empty($data) || ! $data['success']) {
                throw new \Exception('Failed to generate export data');
            }

            // Generate and return the export file
            return $this->reportExporter->export(
                $format,
                $data['data']['assets'] ?? $data['data']['monthly_data'] ?? $data['data'],
                $columns,
                $filename
            );

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Export failed',
                    'error' => $e->getMessage(),
                ], 500);
            }

            // For non-JSON requests (direct downloads), redirect back with error
            return redirect()->back()
                ->with('error', 'Failed to generate export: '.$e->getMessage());
        }
    }
}
