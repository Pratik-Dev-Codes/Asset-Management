<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Document;
use App\Models\Location;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Display the main dashboard with widgets and statistics.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Get data for the dashboard view
        $data = [
            'stats' => $this->getDashboardStats(),
            'recentActivities' => $this->getRecentActivities(10),
            'assetStatus' => $this->getAssetStatusOverview(),
            'upcomingMaintenance' => $this->getUpcomingMaintenance(5),
            'expiringWarranties' => $this->getExpiringWarranties(30, 5),
            'assetValueByCategory' => $this->getAssetValueByCategory(),
            'assetCountByLocation' => $this->getAssetCountByLocation(),
            'recentMaintenanceLogs' => MaintenanceLog::with('asset')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'asset_name' => $log->asset ? $log->asset->name : 'N/A',
                        'type' => $log->maintenance_type,
                        'description' => $log->description,
                        'date' => $log->created_at->format('M d, Y'),
                        'status' => $log->status,
                    ];
                }),
        ];

        return Inertia::render('Dashboard', $data);
    }

    /**
     * Get dashboard statistics.
     *
     * @return array
     */
    protected function getDashboardStats()
    {
        return [
            'total_assets' => Cache::remember('total_assets', 3600, fn () => Asset::count()),
            'assets_under_maintenance' => Cache::remember('assets_under_maintenance', 3600,
                fn () => MaintenanceLog::where('status', 'in_progress')->count()
            ),
            'total_asset_value' => Cache::remember('total_asset_value', 3600,
                fn () => number_format(Asset::sum('purchase_cost'), 2)
            ),
            'total_categories' => Cache::remember('total_categories', 3600, fn () => AssetCategory::count()),
            'total_locations' => Cache::remember('total_locations', 3600, fn () => Location::count()),
            'total_departments' => Cache::remember('total_departments', 3600, fn () => Department::count()),
        ];
    }

    /**
     * Get recent activities.
     *
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRecentActivities($limit = 10)
    {
        return Activity::with('causer')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Display the asset dashboard with detailed asset statistics
     */
    public function assetDashboard()
    {
        $data = [
            'assetStats' => $this->getAssetStatistics(),
            'assetDistribution' => $this->getAssetDistribution(),
            'assetStatus' => $this->getAssetStatus(),
            'recentlyAdded' => Asset::with('category', 'location')
                ->latest()
                ->take(5)
                ->get(),
            'assetValueByCategory' => $this->getAssetValueByCategory(),
        ];

        if (request()->ajax()) {
            return response()->json($data);
        }

        return view('dashboards.assets', $data);
    }

    /**
     * Display the maintenance dashboard with maintenance statistics
     */
    public function maintenanceDashboard()
    {
        $data = [
            'maintenanceStats' => $this->getMaintenanceStats(),
            'upcomingMaintenance' => $this->getUpcomingMaintenance(),
            'recentMaintenance' => MaintenanceLog::with(['asset', 'technician'])
                ->latest()
                ->take(10)
                ->get(),
            'maintenanceByType' => $this->getMaintenanceByType(),
        ];

        if (request()->ajax()) {
            return response()->json($data);
        }

        return view('dashboards.maintenance', $data);
    }

    /**
     * Get asset status overview.
     *
     * @return array
     */
    protected function getAssetStatusOverview()
    {
        return Asset::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get upcoming maintenance.
     *
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getUpcomingMaintenance($limit = 5)
    {
        return MaintenanceSchedule::with('asset')
            ->where('next_due_date', '>=', now())
            ->where('is_active', true)
            ->orderBy('next_due_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Get expiring warranties.
     *
     * @param  int  $days
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getExpiringWarranties($days = 30, $limit = 5)
    {
        return Asset::whereNotNull('warranty_end_date')
            ->where('warranty_end_date', '>=', now())
            ->where('warranty_end_date', '<=', now()->addDays($days))
            ->orderBy('warranty_end_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Get asset value by category.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getAssetValueByCategory()
    {
        return Asset::join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->select('asset_categories.name as category', DB::raw('SUM(assets.purchase_cost) as total_value'))
            ->groupBy('asset_categories.name')
            ->get();
    }

    /**
     * Get asset count by location.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAssetCountByLocation()
    {
        return Location::withCount('assets')
            ->orderBy('assets_count', 'desc')
            ->get();
    }

    /**
     * Show the bulk update form.
     *
     * @return \Illuminate\View\View
     */
    public function showBulkUpdateForm()
    {
        $categories = AssetCategory::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('assets.bulk-update', compact('categories', 'locations', 'departments'));
    }

    /**
     * Process bulk update.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'assets' => 'required|array',
            'assets.*' => 'exists:assets,id',
            'category_id' => 'nullable|exists:asset_categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'nullable|in:available,in_use,maintenance,retired',
        ]);

        $updates = $request->only(['category_id', 'location_id', 'department_id', 'status']);
        $updates = array_filter($updates); // Remove null values

        if (empty($updates)) {
            return redirect()->back()->with('error', 'No fields to update.');
        }

        $count = Asset::whereIn('id', $request->assets)->update($updates);

        // Log the bulk update activity
        activity()
            ->causedBy(auth()->user())
            ->withProperties(['count' => $count, 'updates' => $updates])
            ->log('Bulk updated '.$count.' assets');

        return redirect()->route('assets.index')
            ->with('success', "Successfully updated $count assets.");
    }

    /**
     * Show the bulk delete confirmation.
     *
     * @return \Illuminate\View\View
     */
    public function showBulkDeleteForm()
    {
        return view('assets.bulk-delete');
    }

    /**
     * Process bulk delete.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'assets' => 'required|array',
            'assets.*' => 'exists:assets,id',
            'confirmation' => 'required|in:DELETE',
        ], [
            'confirmation.in' => 'Please type DELETE to confirm.',
        ]);

        $count = count($request->assets);
        $assets = Asset::whereIn('id', $request->assets)->get();

        // Log the deletion of each asset
        foreach ($assets as $asset) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($asset)
                ->log('Deleted asset: '.$asset->name);
        }

        // Delete the assets
        Asset::whereIn('id', $request->assets)->delete();

        return redirect()->route('assets.index')
            ->with('success', "Successfully deleted $count assets.");
    }

    /**
     * Process bulk status change.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'assets' => 'required|array',
            'assets.*' => 'exists:assets,id',
            'status' => 'required|in:available,in_use,maintenance,retired',
        ]);

        $count = Asset::whereIn('id', $request->assets)
            ->update(['status' => $request->status]);

        // Log the bulk status update
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'count' => $count,
                'status' => $request->status,
                'asset_ids' => $request->assets,
            ])
            ->log('Bulk updated status for '.$count.' assets to '.$request->status);

        return response()->json([
            'success' => true,
            'message' => "Updated status for $count assets.",
            'count' => $count,
        ]);
    }

    /**
     * Get assets for bulk operations.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAssetsForBulkOperation(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:asset_categories,id',
            'status' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Asset::query()
            ->with(['category', 'location', 'department'])
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('serial_number', 'like', '%'.$request->search.'%')
                        ->orWhere('asset_tag', 'like', '%'.$request->search.'%');
                });
            })
            ->when($request->category_id, function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            });

        $assets = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $assets->items(),
            'meta' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
        ]);
    }

    /**
     * Show the bulk import form.
     *
     * @return \Illuminate\View\View
     */
    public function showBulkImportForm()
    {
        return view('assets.bulk-import');
    }

    /**
     * Process bulk import.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        $file = $request->file('import_file');
        $extension = $file->getClientOriginalExtension();

        try {
            $import = new AssetsImport;

            if ($extension === 'xlsx' || $extension === 'xls') {
                Excel::import($import, $file);
            } else {
                Excel::import($import, $file, null, \Maatwebsite\Excel\Excel::CSV);
            }

            $imported = $import->getRowCount();
            $skipped = $import->getSkippedCount();

            $message = "Successfully imported $imported assets.";
            if ($skipped > 0) {
                $message .= " $skipped rows were skipped due to errors.";
            }

            return redirect()->route('assets.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error importing file: '.$e->getMessage());
        }
    }

    /**
     * Get maintenance statistics for the last 6 months
     */
    protected function getMaintenanceStatistics(): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subMonths(5)->startOfMonth();

        // Initialize the result array with all months in the range
        $result = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $monthKey = $currentDate->format('Y-m');
            $result[$monthKey] = [
                'month' => $currentDate->format('M Y'),
                'scheduled' => 0,
                'completed' => 0,
                'overdue' => 0,
            ];
            $currentDate->addMonth();
        }

        // Get scheduled maintenance counts by month
        $scheduled = MaintenanceSchedule::select(
            DB::raw('DATE_FORMAT(next_due_date, "%Y-%m") as month'),
            DB::raw('COUNT(*) as count')
        )
            ->where('next_due_date', '>=', $startDate)
            ->where('next_due_date', '<=', $endDate)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Get completed maintenance counts by month
        $completed = MaintenanceLog::select(
            DB::raw('DATE_FORMAT(completion_datetime, "%Y-%m") as month'),
            DB::raw('COUNT(*) as count')
        )
            ->where('completion_datetime', '>=', $startDate)
            ->where('completion_datetime', '<=', $endDate)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Get overdue maintenance counts by month
        $overdue = MaintenanceSchedule::select(
            DB::raw('DATE_FORMAT(next_due_date, "%Y-%m") as month'),
            DB::raw('COUNT(*) as count')
        )
            ->where('next_due_date', '<', now())
            ->where('next_due_date', '>=', $startDate)
            ->where('next_due_date', '<=', $endDate)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Merge the counts into the result array
        foreach ($result as $month => &$data) {
            if (isset($scheduled[$month])) {
                $data['scheduled'] = $scheduled[$month];
            }
            if (isset($completed[$month])) {
                $data['completed'] = $completed[$month];
            }
            if (isset($overdue[$month])) {
                $data['overdue'] = $overdue[$month];
            }
        }

        return array_values($result);
    }

    /**
     * Get asset value trends for the last 12 months
     */
    protected function getAssetValueTrends(): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subMonths(11)->startOfMonth();

        $result = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $monthKey = $currentDate->format('Y-m');
            $result[$monthKey] = [
                'month' => $currentDate->format('M Y'),
                'value' => 0,
                'count' => 0,
            ];
            $currentDate->addMonth();
        }

        // Get asset values by month
        $assets = Asset::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('SUM(purchase_cost) as total_value'),
            DB::raw('COUNT(*) as asset_count')
        )
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->groupBy('month')
            ->get();

        // Merge the values into the result array
        foreach ($assets as $asset) {
            if (isset($result[$asset->month])) {
                $result[$asset->month]['value'] = (float) $asset->total_value;
                $result[$asset->month]['count'] = (int) $asset->asset_count;
            }
        }

        return array_values($result);
    }

    /**
     * Get maintenance statistics
     */
    protected function getMaintenanceStats()
    {
        return [
            'total' => MaintenanceLog::count(),
            'preventive' => MaintenanceLog::where('type', 'preventive')->count(),
            'corrective' => MaintenanceLog::where('type', 'corrective')->count(),
            'scheduled' => MaintenanceLog::where('is_scheduled', true)->count(),
            'unscheduled' => MaintenanceLog::where('is_scheduled', false)->count(),
        ];
    }
}
