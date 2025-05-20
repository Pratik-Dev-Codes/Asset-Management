<?php

namespace App\Services;

use App\Contracts\Asset\AssetRepositoryInterface;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use App\Models\Maintenance;
use App\Models\MaintenanceLog;
use App\Models\AssetAttachment;
use App\Http\Filters\AssetFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AssetService
{
    /**
     * Cache constants
     */
    const CACHE_PREFIX = 'asset_';
    const CACHE_TTL = 3600; // 1 hour
    const MAX_PER_PAGE = 100;
    const DEFAULT_PER_PAGE = 15;
    
    /**
     * The asset repository instance.
     *
     * @var AssetRepositoryInterface
     */
    protected $assetRepository;
    
    /**
     * Get cache key for the given key and suffix.
     *
     * @param string $key
     * @param mixed $suffix
     * @return string
     */
    protected function getCacheKey(string $key, $suffix = ''): string
    {
        return self::CACHE_PREFIX . $key . ($suffix ? '_' . $suffix : '');
    }
    
    /**
     * Clear all asset-related caches.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::tags(['assets'])->flush();
    }
    
    /**
     * Get cache tags for the given type.
     *
     * @param string $type
     * @return array
     */
    protected function getCacheTags(string $type = 'list'): array
    {
        return ['assets', 'assets.' . $type];
    }

    /**
     * Create a new service instance.
     *
     * @param AssetRepositoryInterface $assetRepository
     * @return void
     */
    public function __construct(AssetRepositoryInterface $assetRepository)
    {
        $this->assetRepository = $assetRepository;
    }

    /**
     * Get all assets with advanced filtering, searching, sorting and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @param bool $useCache
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllAssets(array $filters = [], int $perPage = null, bool $useCache = true): LengthAwarePaginator
    {
        $perPage = min($perPage ?? self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);
        $page = request()->input('page', 1);
        $cacheKey = $this->getCacheKey('all_assets', md5(json_encode($filters) . '_page_' . $page . '_per_page_' . $perPage));
        
        if ($useCache && Cache::tags($this->getCacheTags('list'))->has($cacheKey)) {
            return Cache::tags($this->getCacheTags('list'))->get($cacheKey);
        }

        $query = $this->assetRepository->newQuery()
            ->with([
                'category:id,name', 
                'location:id,name', 
                'department:id,name',
                'assignedTo:id,name,email'
            ])
            ->select([
                'id', 'name', 'asset_code', 'status', 'category_id', 
                'location_id', 'department_id', 'assigned_to', 'purchase_cost',
                'purchase_date', 'created_at', 'updated_at'
            ]);
            
        // Apply filters
        $this->applyFilters($query, $filters);
        
        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = isset($filters['order']) && strtolower($filters['order']) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        
        // Execute query and cache results
        $result = $query->paginate($perPage);
        
        if ($useCache) {
            Cache::tags($this->getCacheTags('list'))->put($cacheKey, $result, now()->addMinutes(30));
        }
        
        return $result;
    }
    
    /**
     * Apply filters to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // Search filter
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('asset_code', 'like', $search)
                  ->orWhere('serial_number', 'like', $search)
                  ->orWhere('model', 'like', $search);
            });
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        // Location filter
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }
        
        // Department filter
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        
        // Assigned to filter
        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }
        
        // Purchase date range
        if (!empty($filters['purchase_date_from'])) {
            $query->whereDate('purchase_date', '>=', $filters['purchase_date_from']);
        }
        
        if (!empty($filters['purchase_date_to'])) {
            $query->whereDate('purchase_date', '<=', $filters['purchase_date_to']);
        }
    }
        
    /**
     * Get a single asset by ID with caching.
     *
     * @param int $id
     * @param bool $useCache
     * @return \App\Models\Asset
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getAssetById(int $id, bool $useCache = true): Asset
    {
        $cacheKey = $this->getCacheKey('asset', $id);
        
        if (!$useCache) {
            return $this->assetRepository->findOrFail($id);
        }
        
        return Cache::tags($this->getCacheTags('single'))->remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return $this->assetRepository->with([
                'category:id,name',
                'location:id,name',
                'department:id,name',
                'assignedTo:id,name,email',
                'attachments:id,asset_id,file_name,file_path,file_size,file_type,created_at',
                'maintenanceLogs' => function ($query) {
                    $query->latest()->limit(5);
                },
                'maintenanceSchedules' => function ($query) {
                    $query->where('is_active', true)
                          ->orderBy('next_maintenance_date')
                          ->limit(3);
                }
            ])->findOrFail($id);
        });
    }
    
    /**
     * Create a new asset with proper event dispatching.
     *
     * @param array $data
     * @return \App\Models\Asset
     */
    public function createAsset(array $data): Asset
    {
        return DB::transaction(function () use ($data) {
            $asset = $this->assetRepository->create($data);
            
            // Clear relevant caches
            $this->clearCache();
            
            // Dispatch event for any listeners
            Event::dispatch('asset.created', $asset);
            
            return $asset;
        });
    }
    
    /**
     * Update an existing asset.
     *
     * @param int $id
     * @param array $data
     * @return \App\Models\Asset
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateAsset(int $id, array $data): Asset
    {
        return DB::transaction(function () use ($id, $data) {
            $asset = $this->getAssetById($id, false);
            $asset->update($data);
            
            // Clear relevant caches
            $this->clearCache();
            
            // Dispatch event for any listeners
            Event::dispatch('asset.updated', $asset);
            
            return $asset;
        });
    }
    
    /**
     * Delete an asset.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteAsset(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $asset = $this->getAssetById($id, false);
            $result = $asset->delete();
            
            // Clear relevant caches
            $this->clearCache();
            
            // Dispatch event for any listeners
            Event::dispatch('asset.deleted', $id);
            
            return $result;
        });
    }
    
    /**
     * Get asset statistics with caching.
     *
     * @return array
     */
    public function getAssetStats(): array
    {
        $cacheKey = $this->getCacheKey('stats');
        
        return Cache::tags($this->getCacheTags('stats'))->remember($cacheKey, 3600, function () {
            return [
                'total_assets' => $this->assetRepository->count(),
                'total_value' => $this->assetRepository->sum('purchase_cost'),
                'by_status' => $this->assetRepository->groupBy('status')
                    ->select('status', DB::raw('count(*) as count'))
                    ->pluck('count', 'status'),
                'by_category' => $this->assetRepository->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
                    ->groupBy('asset_categories.name')
                    ->select('asset_categories.name', DB::raw('count(*) as count'))
                    ->pluck('count', 'name'),
                'recently_added' => $this->assetRepository->orderBy('created_at', 'desc')
                    ->with(['category:id,name', 'location:id,name'])
                    ->limit(5)
                    ->get(),
                'due_for_maintenance' => Maintenance::where('next_maintenance_date', '<=', now()->addDays(7))
                    ->where('is_active', true)
                    ->with(['asset:id,name,asset_code'])
                    ->orderBy('next_maintenance_date')
                    ->limit(5)
                    ->get()
            ];
        });
    }
    
    /**
     * Get assets due for maintenance.
     *
     * @param int $days
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDueForMaintenance(int $days = 7, int $limit = 10)
    {
        $cacheKey = $this->getCacheKey('due_for_maintenance', "{$days}_{$limit}");
        
        return Cache::tags($this->getCacheTags('maintenance'))->remember($cacheKey, 3600, function () use ($days, $limit) {
            return $this->assetRepository->whereHas('maintenanceSchedules', function ($query) use ($days) {
                $query->where('next_maintenance_date', '<=', now()->addDays($days))
                      ->where('is_active', true);
            })
            ->with(['category:id,name', 'location:id,name'])
            ->limit($limit)
            ->get();
        });
    }
    
    /**
     * Get recently added assets.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentlyAdded(int $limit = 5)
    {
        $cacheKey = $this->getCacheKey('recently_added', $limit);
        
        return Cache::tags($this->getCacheTags('list'))->remember($cacheKey, 3600, function () use ($limit) {
            return $this->assetRepository->with(['category:id,name', 'location:id,name'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }
            'category:id,name',
            'location:id,name',
            'department:id,name',
            'assignedTo:id,name'
        ]);
        
        // Apply filters
        $this->applyFilters($query, $filters);
        
        // Apply sorting
        $this->applySorting($query, $filters);
        
        // Execute query with pagination
        $result = $query->paginate($perPage);
        
        // Cache only essential data
        if ($useCache) {
            $cacheData = [
                'data' => $result->items(),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                ]
            ];
            Cache::put($cacheKey, $cacheData, now()->addMinutes($this->cacheDuration));
        }
        
        return $result;
    }
    
    /**
     * Apply filters to the query with optimized conditions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // Full-text search with index hints and optimized conditions
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('asset_code', 'like', "%{$searchTerm}%")
                  ->orWhere('serial_number', 'like', "%{$searchTerm}%")
                  ->orWhere('model', 'like', "%{$searchTerm}%")
                  ->orWhere('manufacturer', 'like', "%{$searchTerm}%")
                  ->orWhereHas('category', function($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%");
                  });
            })->useIndex('assets_search_idx');
        }
        
        // Apply filters with index hints
        $indexedFilters = [
            'status' => 'assets_status_idx',
            'category_id' => 'assets_category_id_idx',
            'location_id' => 'assets_location_id_idx',
            'department_id' => 'assets_department_id_idx',
            'assigned_to' => 'assets_assigned_to_idx'
        ];
        
        foreach ($indexedFilters as $field => $index) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field])->useIndex($index);
            }
        }
        
        // Date range filters with index
        if (!empty($filters['purchase_date_from'])) {
            $query->whereDate('purchase_date', '>=', $filters['purchase_date_from'])
                  ->useIndex('assets_purchase_date_idx');
        }
        
        if (!empty($filters['purchase_date_to'])) {
            $query->whereDate('purchase_date', '<=', $filters['purchase_date_to'])
                  ->useIndex('assets_purchase_date_idx');
        }
    }
    
    /**
     * Apply sorting to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $filters
     * @return void
     */
    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = isset($filters['order']) && strtolower($filters['order']) === 'asc' ? 'asc' : 'desc';
        
        // Handle sorting by related models
        if (Str::contains($sortBy, '.')) {
            $relation = explode('.', $sortBy)[0];
            $column = explode('.', $sortBy)[1];
            
            $query->select('assets.*')
                  ->join($relation, "assets.{$relation}_id", '=', "{$relation}.id")
                  ->orderBy("{$relation}.{$column}", $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }
    }
    
    /**
     * Get asset statistics for the dashboard with optimized caching.
     *
     * @return array
     */
    public function getAssetStatistics(): array
    {
        $cacheKey = 'asset_statistics';
        
        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () {
            $stats = [
                'total_assets' => $this->assetRepository->count(),
                'total_value' => $this->assetRepository->sum('purchase_cost'),
            ];
            
            // Get status counts in a single query
            $statusCounts = $this->assetRepository->getCountByStatus();
            foreach ($statusCounts as $status => $count) {
                $stats["total_{$status}"] = $count;
            }
            
            return $stats;
        });
    }

    /**
     * Get assets for export based on filters.
     *
     * @param  array  $filters
     * @return \Illuminate\Support\Collection
     */
    public function getAssetsForExport(array $filters = []): Collection
    {
        $query = $this->assetRepository->newQuery()
            ->select([
                'id', 'name', 'asset_code', 'status', 'category_id', 
                'location_id', 'department_id', 'assigned_to', 'purchase_cost',
                'purchase_date', 'serial_number', 'model', 'manufacturer'
            ])
            ->with(['category:id,name', 'location:id,name', 'department:id,name', 'assignedTo:id,name']);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->get();
    }

    /**
     * Get assets that require maintenance.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAssetsRequiringMaintenance(): Collection
    {
        return $this->assetRepository->newQuery()
            ->where('status', 'maintenance')
            ->orWhere('next_maintenance_date', '<=', now()->addDays(7))
            ->orderBy('next_maintenance_date', 'asc')
            ->get();
    }

    /**
     * Get assets with expiring warranty.
     *
     * @param  int  $days
     * @return \Illuminate\Support\Collection
     */
    public function getAssetsWithExpiringWarranty(int $days = 30): Collection
    {
        return $this->assetRepository->newQuery()
            ->where('warranty_months', '>', 0)
            ->whereRaw('DATE_ADD(purchase_date, INTERVAL warranty_months MONTH) BETWEEN ? AND ?', 
                [now(), now()->addDays($days)])
            ->orderByRaw('DATEDIFF(DATE_ADD(purchase_date, INTERVAL warranty_months MONTH), NOW())')
            ->get();
    }

    /**
     * Get asset value by category.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAssetValueByCategory(): Collection
    {
        return $this->assetRepository->newQuery()
            ->select('asset_categories.name as category', DB::raw('SUM(assets.purchase_cost) as total_value'))
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
            ->groupBy('asset_categories.name')
            ->orderBy('total_value', 'desc')
            ->get();
    }

    /**
     * Get asset count by status.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAssetCountByStatus(): Collection
    {
        return $this->assetRepository->newQuery()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');
    }

    /**
     * Get recent asset activities.
     *
     * @param  int  $limit
     * @return \Illuminate\Support\Collection
     */
    public function getRecentActivities(int $limit = 10): Collection
    {
        return DB::table('activity_log')
            ->where('log_name', 'asset')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new asset.
     *
     * @param  array  $data
     * @return \App\Models\Asset
     */
    public function createAsset(array $data): Asset
    {
        try {
            // Handle file upload if present
            if (isset($data['image'])) {
                $data['image_path'] = $this->uploadAssetImage($data['image']);
                unset($data['image']);
            }

            // Create the asset using repository
            return $this->assetRepository->create($data);
        } catch (\Exception $e) {
            Log::error('Failed to create asset: ' . $e->getMessage());
            throw new \RuntimeException('Failed to create asset: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing asset.
     *
     * @param  int  $id
     * @param  array  $data
     * @return \App\Models\Asset
     * @throws \RuntimeException
     */
    public function updateAsset(int $id, array $data): Asset
    {
        try {
            $asset = $this->assetRepository->findById($id);

            if (!$asset) {
                throw new \RuntimeException('Asset not found');
            }

            // Handle file upload if present
            if (isset($data['image'])) {
                // Delete old image if exists
                if ($asset->image_path) {
                    Storage::delete($asset->image_path);
                }
                $data['image_path'] = $this->uploadAssetImage($data['image']);
                unset($data['image']);
            }

            // Update the asset using repository
            $this->assetRepository->update($id, $data);
            return $this->assetRepository->findById($id);
        } catch (\Exception $e) {
            Log::error('Failed to update asset: ' . $e->getMessage());
            throw new \RuntimeException('Failed to update asset: ' . $e->getMessage());
        }
    }

    /**
     * Check out an asset to a user.
     *
     * @param  int  $assetId
     * @param  int  $userId
     * @param  string  $notes
     * @return \App\Models\Asset
     */
    public function checkOutAsset(int $assetId, int $userId, string $notes = ''): Asset
    {
        try {
            return $this->assetRepository->checkOut($assetId, $userId, $notes);
        } catch (\Exception $e) {
            Log::error('Failed to check out asset: ' . $e->getMessage());
            throw new \RuntimeException('Failed to check out asset: ' . $e->getMessage());
        }
    }

    /**
     * Get assets due for maintenance.
     *
     * @param  int  $days
     * @param  int  $limit
     * @return \Illuminate\Support\Collection
     */
    public function getDueForMaintenance(int $days = 7, int $limit = 10): Collection
    {
        return $this->assetRepository->getDueForMaintenance($days, $limit);
    }

    /**
     * Delete an asset.
     *
     * @param  int  $id
     * @return bool
     * @throws \Exception
     */
    public function deleteAsset(int $id): bool
    {
        try {
            $asset = $this->assetRepository->findById($id);

            if (!$asset) {
                throw new \RuntimeException('Asset not found');
            }

            // Delete associated image if exists
            if ($asset->image_path) {
                Storage::delete($asset->image_path);
            }

            return $this->assetRepository->deleteById($id);
        } catch (\Exception $e) {
            Log::error('Failed to delete asset: ' . $e->getMessage());
            throw new \RuntimeException('Failed to delete asset: ' . $e->getMessage());
        }
    }

    /**
     * Upload an asset image.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string
     */
    protected function uploadAssetImage($file): string
    {
        return $file->store('assets/images', 'public');
    }

    /**
     * Process a large set of assets in chunks with memory optimization.
     *
     * @param  callable  $callback
     * @param  int  $chunkSize
     * @return void
     */
    public function processLargeAssetSets(callable $callback, int $chunkSize = 50): void
    {
        $this->assetRepository->chunk($chunkSize, function ($assets) use ($callback) {
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
