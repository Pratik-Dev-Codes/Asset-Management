<?php

namespace App\Services;

use App\Models\User;
use App\Models\Asset;
use App\Models\AssetStatus;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Get dashboard overview data.
     *
     * @param User $user
     * @return array
     */
    public function getOverview(User $user): array
    {
        $totalAssets = Asset::count();
        $deployedAssets = Asset::where('status_id', AssetStatus::DEPLOYED)->count();
        $undeployedAssets = Asset::where('status_id', AssetStatus::READY_TO_DEPLOY)->count();
        $pendingAssets = Asset::where('status_id', AssetStatus::PENDING)->count();

        return [
            'total_assets' => $totalAssets,
            'deployed_assets' => $deployedAssets,
            'undeployed_assets' => $undeployedAssets,
            'pending_assets' => $pendingAssets,
            'deployment_rate' => $totalAssets > 0 ? round(($deployedAssets / $totalAssets) * 100, 2) : 0,
        ];
    }

    /**
     * Get dashboard statistics.
     *
     * @param User $user
     * @param array $filters
     * @return array
     */
    public function getStatistics(User $user, array $filters = []): array
    {
        $query = Asset::query();

        // Apply filters if any
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        // Get assets by status
        $assetsByStatus = $this->getAssetsByStatus($query);
        
        // Get assets by category
        $assetsByCategory = $this->getAssetsByCategory($query);
        
        // Get recent activities
        $recentActivities = $this->getRecentActivities($user);

        return [
            'assets_by_status' => $assetsByStatus,
            'assets_by_category' => $assetsByCategory,
            'recent_activities' => $recentActivities,
            'stats' => [
                'total_value' => $query->sum('purchase_cost'),
                'total_assets' => $query->count(),
                'depreciating_assets' => $query->where('depreciate', true)->count(),
            ]
        ];
    }

    /**
     * Get assets grouped by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return Collection
     */
    protected function getAssetsByStatus($query): Collection
    {
        return $query->clone()
            ->selectRaw('status_id, count(*) as count')
            ->with('status')
            ->groupBy('status_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status->name => $item->count];
            });
    }

    /**
     * Get assets grouped by category.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return Collection
     */
    protected function getAssetsByCategory($query): Collection
    {
        return $query->clone()
            ->selectRaw('category_id, count(*) as count')
            ->with('category')
            ->groupBy('category_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->category->name => $item->count];
            });
    }

    /**
     * Get recent activities.
     *
     * @param User $user
     * @return array
     */
    protected function getRecentActivities(User $user): array
    {
        // This is a simplified example. In a real application, you would query an Activity model
        // or use a package like spatie/laravel-activitylog
        return [
            [
                'id' => 1,
                'description' => 'New asset added',
                'type' => 'asset_created',
                'user' => $user->name,
                'date' => now()->subMinutes(5),
            ],
            [
                'id' => 2,
                'description' => 'Asset checked out to John Doe',
                'type' => 'asset_checked_out',
                'user' => $user->name,
                'date' => now()->subHours(2),
            ],
            // Add more recent activities as needed
        ];
    }
}
