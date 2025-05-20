<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssetReportService
{
    public function getAssetStatusSummary()
    {
        return Asset::select('asset_statuses.name as status', DB::raw('count(*) as count'))
            ->join('asset_statuses', 'assets.status_id', '=', 'asset_statuses.id')
            ->groupBy('asset_statuses.name')
            ->get()
            ->pluck('count', 'status');
    }

    public function getAssetValueByCategory()
    {
        return AssetModel::select(
            'asset_models.name as model',
            DB::raw('count(assets.id) as count'),
            DB::raw('sum(assets.purchase_cost) as total_value')
        )
            ->leftJoin('assets', 'asset_models.id', '=', 'assets.model_id')
            ->groupBy('asset_models.name')
            ->orderBy('total_value', 'desc')
            ->get();
    }

    public function getAssetDepreciationReport()
    {
        $assets = Asset::with(['model', 'status'])
            ->whereNotNull('purchase_date')
            ->where('purchase_cost', '>', 0)
            ->get();

        return $assets->map(function ($asset) {
            $depreciation = $this->calculateDepreciation(
                $asset->purchase_cost,
                $asset->purchase_date,
                $asset->warranty_months
            );

            return [
                'asset_tag' => $asset->asset_tag,
                'name' => $asset->name,
                'purchase_date' => $asset->purchase_date,
                'purchase_cost' => $asset->purchase_cost,
                'current_value' => $depreciation['current_value'],
                'depreciation' => $depreciation['depreciation'],
                'depreciation_percentage' => $depreciation['depreciation_percentage'],
                'end_of_life' => $depreciation['end_of_life'],
            ];
        });
    }

    public function getMaintenanceHistory($days = 365)
    {
        return Asset::with(['maintenance' => function ($query) use ($days) {
            $query->where('completed_at', '>=', now()->subDays($days));
        }])
            ->whereHas('maintenance')
            ->get()
            ->map(function ($asset) {
                return [
                    'asset_tag' => $asset->asset_tag,
                    'name' => $asset->name,
                    'maintenance_count' => $asset->maintenance->count(),
                    'total_cost' => $asset->maintenance->sum('cost'),
                    'last_maintenance' => $asset->maintenance->max('completed_at'),
                ];
            });
    }

    public function getUserAssetReport()
    {
        return User::withCount('assets')
            ->has('assets')
            ->withSum('assets', 'purchase_cost')
            ->orderBy('assets_count', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'user' => $user->name,
                    'email' => $user->email,
                    'asset_count' => $user->assets_count,
                    'total_asset_value' => $user->assets_sum_purchase_cost,
                    'department' => $user->department->name ?? 'N/A',
                ];
            });
    }

    protected function calculateDepreciation($purchaseCost, $purchaseDate, $warrantyMonths = 36)
    {
        $purchaseDate = Carbon::parse($purchaseDate);
        $monthsInUse = $purchaseDate->diffInMonths(now());

        // If warranty period is not set, default to 3 years (36 months)
        $warrantyMonths = $warrantyMonths ?: 36;

        // Calculate depreciation (straight-line method)
        $depreciationPerMonth = $purchaseCost / $warrantyMonths;
        $totalDepreciation = $depreciationPerMonth * min($monthsInUse, $warrantyMonths);
        $currentValue = max(0, $purchaseCost - $totalDepreciation);

        return [
            'current_value' => round($currentValue, 2),
            'depreciation' => round($totalDepreciation, 2),
            'depreciation_percentage' => $monthsInUse >= $warrantyMonths ? 100 : round(($monthsInUse / $warrantyMonths) * 100, 2),
            'end_of_life' => $purchaseDate->copy()->addMonths($warrantyMonths),
        ];
    }

    public function generateCustomReport($filters)
    {
        $query = Asset::query()->with(['model', 'status', 'assignedTo']);

        // Apply filters
        if (! empty($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }

        if (! empty($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        if (! empty($filters['purchase_date_start'])) {
            $query->whereDate('purchase_date', '>=', $filters['purchase_date_start']);
        }

        if (! empty($filters['purchase_date_end'])) {
            $query->whereDate('purchase_date', '<=', $filters['purchase_date_end']);
        }

        // Add more filters as needed

        return $query->get()->map(function ($asset) {
            $depreciation = $this->calculateDepreciation(
                $asset->purchase_cost,
                $asset->purchase_date,
                $asset->warranty_months
            );

            return [
                'asset_tag' => $asset->asset_tag,
                'name' => $asset->name,
                'model' => $asset->model->name ?? 'N/A',
                'status' => $asset->status->name ?? 'N/A',
                'assigned_to' => $asset->assignedTo->name ?? 'Unassigned',
                'purchase_date' => $asset->purchase_date,
                'purchase_cost' => $asset->purchase_cost,
                'current_value' => $depreciation['current_value'],
                'depreciation' => $depreciation['depreciation'],
                'depreciation_percentage' => $depreciation['depreciation_percentage'],
                'end_of_life' => $depreciation['end_of_life'],
                'location' => $asset->location->name ?? 'N/A',
                'notes' => $asset->notes,
            ];
        });
    }
}
