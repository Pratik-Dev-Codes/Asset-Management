<?php

namespace App\Repositories\Asset;

use App\Contracts\Asset\AssetRepositoryInterface;
use App\Models\Asset;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AssetRepository extends BaseRepository implements AssetRepositoryInterface
{
    protected array $searchableColumns = [
        'name',
        'asset_code',
        'serial_number',
        'model',
        'manufacturer',
        'notes',
    ];

    /**
     * AssetRepository constructor.
     */
    public function __construct(Asset $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getByStatus(string $status, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model
            ->with($relations)
            ->where('status', $status)
            ->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function getByLocation(int $locationId, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model
            ->with($relations)
            ->where('location_id', $locationId)
            ->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function getByCategory(int $categoryId, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model
            ->with($relations)
            ->where('category_id', $categoryId)
            ->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function getByDepartment(int $departmentId, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model
            ->with($relations)
            ->where('department_id', $departmentId)
            ->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function search(array $filters = [], int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        $query = $this->newQuery();

        // Apply search filters with index hints
        if (! empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function (Builder $q) use ($searchTerm) {
                foreach ($this->searchableColumns as $column) {
                    $q->orWhere($column, 'LIKE', "%{$searchTerm}%");
                }
            })->useIndex('assets_search_idx');
        }

        // Apply filters with index hints
        $indexedFilters = [
            'status' => 'assets_status_idx',
            'category_id' => 'assets_category_id_idx',
            'location_id' => 'assets_location_id_idx',
            'department_id' => 'assets_department_id_idx',
            'assigned_to' => 'assets_assigned_to_idx',
        ];

        foreach ($indexedFilters as $field => $index) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field])->useIndex($index);
            }
        }

        // Apply date range filters with index
        if (! empty($filters['purchase_date_from'])) {
            $query->whereDate('purchase_date', '>=', $filters['purchase_date_from'])
                ->useIndex('assets_purchase_date_idx');
        }

        if (! empty($filters['purchase_date_to'])) {
            $query->whereDate('purchase_date', '<=', $filters['purchase_date_to'])
                ->useIndex('assets_purchase_date_idx');
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Load only necessary relations
        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function checkOut(int $assetId, int $userId, string $notes = ''): Asset
    {
        $asset = $this->findById($assetId);

        $asset->assigned_to = $userId;
        $asset->assigned_date = now();
        $asset->status = 'assigned';
        $asset->save();

        // Log the checkout activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($asset)
            ->withProperties([
                'assigned_to' => $userId,
                'notes' => $notes,
            ])
            ->log('checked out');

        return $asset;
    }

    /**
     * {@inheritDoc}
     */
    public function checkIn(int $assetId, string $notes = ''): Asset
    {
        $asset = $this->findById($assetId);

        // Log the check-in activity before updating
        activity()
            ->causedBy(auth()->user())
            ->performedOn($asset)
            ->withProperties([
                'previous_assigned_to' => $asset->assigned_to,
                'notes' => $notes,
            ])
            ->log('checked in');

        // Update the asset
        $asset->assigned_to = null;
        $asset->assigned_date = null;
        $asset->status = 'available';
        $asset->save();

        return $asset;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatistics(): array
    {
        return [
            'total_assets' => $this->model->count(),
            'total_value' => (float) $this->model->sum('purchase_cost'),
            'total_available' => $this->model->where('status', 'available')->count(),
            'total_assigned' => $this->model->where('status', 'assigned')->count(),
            'total_in_maintenance' => $this->model->where('status', 'maintenance')->count(),
            'total_retired' => $this->model->where('status', 'retired')->count(),
            'total_lost' => $this->model->where('status', 'lost')->count(),
            'total_damaged' => $this->model->where('status', 'damaged')->count(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDueForMaintenance(int $days = 7, int $limit = 10): Collection
    {
        return $this->model
            ->select(['id', 'name', 'asset_code', 'status', 'last_maintenance_date'])
            ->where('status', '!=', 'retired')
            ->where(function ($query) use ($days) {
                $query->whereNull('last_maintenance_date')
                    ->orWhere('last_maintenance_date', '<=', now()->subDays($days));
            })
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentlyAdded(int $limit = 5): Collection
    {
        return $this->model
            ->select(['id', 'name', 'asset_code', 'status', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalValue(): float
    {
        return (float) $this->model->sum('purchase_cost');
    }

    /**
     * {@inheritDoc}
     */
    public function getCountByStatus(): Collection
    {
        return $this->model
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getCountByCategory(): Collection
    {
        return $this->model
            ->select('category_id', DB::raw('count(*) as count'))
            ->groupBy('category_id')
            ->get();
    }

    /**
     * Get a new query builder instance with optimized select.
     */
    public function newQuery(): Builder
    {
        return $this->model->newQuery()
            ->select([
                'id', 'name', 'asset_code', 'status', 'category_id',
                'location_id', 'department_id', 'assigned_to', 'purchase_cost',
                'purchase_date', 'created_at', 'updated_at',
            ]);
    }

    /**
     * Count assets by status with optimized query.
     */
    public function countByStatus(string $status): int
    {
        return $this->model->where('status', $status)
            ->select('id')
            ->count();
    }

    /**
     * Get total count of assets with optimized query.
     */
    public function count(): int
    {
        return $this->model->select('id')->count();
    }

    /**
     * Get sum of a column with optimized query.
     */
    public function sum(string $column): float
    {
        return (float) $this->model->select($column)->sum($column);
    }

    /**
     * Process assets in chunks with memory optimization.
     */
    public function chunk(int $chunkSize, callable $callback): void
    {
        $this->model->select([
            'id', 'name', 'asset_code', 'status', 'category_id',
            'location_id', 'department_id', 'assigned_to', 'purchase_cost',
            'purchase_date', 'created_at', 'updated_at',
        ])->chunk($chunkSize, function ($assets) use ($callback) {
            // Clear any cached data to free memory
            Cache::forget('asset_statistics');
            Cache::forget('asset_status_counts');
            Cache::forget('asset_category_counts');

            $callback($assets);

            // Force garbage collection after each chunk
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });
    }
}
