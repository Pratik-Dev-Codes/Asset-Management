<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseApiController
{
    /**
     * Get dashboard overview.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Sample dashboard data - replace with actual queries in a real implementation
        $data = [
            'total_assets' => DB::table('assets')->count(),
            'available_assets' => DB::table('assets')->where('status', 'available')->count(),
            'assigned_assets' => DB::table('assets')->where('status', 'assigned')->count(),
            'maintenance_assets' => DB::table('assets')->where('status', 'maintenance')->count(),
            'recent_activities' => [],
            'upcoming_maintenance' => [],
        ];

        return $this->success($data, 'Dashboard data retrieved successfully');
    }

    /**
     * Get dashboard statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $filters = $request->all();

        // Sample statistics data - replace with actual queries in a real implementation
        $data = [
            'assets_by_status' => [
                ['status' => 'Available', 'count' => DB::table('assets')->where('status', 'available')->count()],
                ['status' => 'Assigned', 'count' => DB::table('assets')->where('status', 'assigned')->count()],
                ['status' => 'Maintenance', 'count' => DB::table('assets')->where('status', 'maintenance')->count()],
                ['status' => 'Retired', 'count' => DB::table('assets')->where('status', 'retired')->count()],
            ],
            'assets_by_category' => DB::table('assets')
                ->join('categories', 'assets.category_id', '=', 'categories.id')
                ->select('categories.name as category', DB::raw('count(*) as count'))
                ->groupBy('categories.name')
                ->get(),
            'recent_activities' => [],
        ];

        return $this->success($data, 'Dashboard statistics retrieved successfully');
    }
}
