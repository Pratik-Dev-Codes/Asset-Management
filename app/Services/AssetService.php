<?php

namespace App\Services;

use App\Contracts\Asset\AssetRepositoryInterface;
use App\Models\Asset;
use App\Models\Maintenance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class AssetService
{
    /**
     * Cache constants
     */
    private const CACHE_PREFIX = 'asset_';
    private const CACHE_TTL = 3600; // 1 hour
    private const MAX_PER_PAGE = 100;
    private const DEFAULT_PER_PAGE = 15;
    
    /**
     * The asset repository instance.
     *
     * @var AssetRepositoryInterface
     */
    protected $assetRepository;
    
    /**
     * Create a new service instance.
     *
     * @param  AssetRepositoryInterface  $assetRepository
     * @return void
     */
    public function __construct(AssetRepositoryInterface $assetRepository)
    {
        $this->assetRepository = $assetRepository;
    }

    /**
     * Get all assets with optional filters and pagination.
     *
     * @param  array<string, mixed>  $filters
     * @param  int|null  $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * @throws \InvalidArgumentException If invalid filter parameters are provided
     */
    public function getAllAssets(array $filters = [], ?int $perPage = null): \Illuminate\Pagination\LengthAwarePaginator
    {
        try {
            $perPage = $perPage ? min($perPage, self::MAX_PER_PAGE) : self::DEFAULT_PER_PAGE;
            
            return $this->assetRepository->search(
                $filters,
                $perPage,
                ['*'],
                ['category', 'location', 'department']
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve assets: ' . $e->getMessage());
            throw new \RuntimeException('Failed to retrieve assets: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get a single asset by ID.
     *
     * @param  int  $id
     * @param  bool  $withRelations
     * @return \App\Models\Asset
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getAssetById(int $id, bool $withRelations = true): Asset
    {
        $relations = $withRelations ? ['category', 'location', 'department'] : [];
        $asset = $this->assetRepository->findById($id, ['*'], $relations);
        
        if (!$asset) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Asset not found with ID: {$id}");
        }
        
        return $asset;
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
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image_path'] = $this->uploadAssetImage($data['image']);
                unset($data['image']);
            }

            // Create the asset using repository
            return $this->assetRepository->create($data);
        } catch (\Exception $e) {
            Log::error('Failed to create asset: ' . $e->getMessage());
            throw new RuntimeException('Failed to create asset: ' . $e->getMessage());
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
                throw new RuntimeException('Asset not found');
            }

            // Handle file upload if present
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
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
            throw new RuntimeException('Failed to update asset: ' . $e->getMessage());
        }
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
            $asset = $this->getAssetById($id, false);
            
            // Delete associated image if exists
            if ($asset->image_path) {
                Storage::delete($asset->image_path);
            }
            
            // Delete the asset
            return $this->assetRepository->deleteById($id);
        } catch (\Exception $e) {
            Log::error('Failed to delete asset: ' . $e->getMessage());
            throw new RuntimeException('Failed to delete asset: ' . $e->getMessage());
        }
    }

    /**
     * Get assets that are due or overdue for maintenance.
     *
     * @param  int  $days  Number of days to look ahead for due maintenance
     * @param  int  $limit  Maximum number of assets to return
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Asset>
     * @throws \RuntimeException If there's an error retrieving maintenance data
     */
    public function getDueForMaintenance(int $days = 7, int $limit = 10): Collection
    {
        try {
            $cacheKey = $this->getCacheKey('due_for_maintenance', "{$days}_{$limit}");
            
            return Cache::tags($this->getCacheTags('maintenance'))
                ->remember($cacheKey, self::CACHE_TTL, function () use ($days, $limit) {
                    return $this->assetRepository->getDueForMaintenance($days, $limit);
                });
        } catch (\Exception $e) {
            Log::error('Failed to retrieve assets due for maintenance: ' . $e->getMessage());
            throw new \RuntimeException('Failed to retrieve maintenance data: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get recent activities for assets.
     *
     * @param  int  $limit  Maximum number of activities to return (max 100)
     * @return \Illuminate\Support\Collection<int, object>
     * @throws \RuntimeException If there's an error retrieving activity data
     */
    public function getRecentActivities(int $limit = 10): Collection
    {
        try {
            $limit = min($limit, 100); // Enforce maximum limit
            
            return DB::table('activity_log')
                ->where('log_name', 'asset')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to retrieve recent activities: ' . $e->getMessage());
            throw new \RuntimeException('Failed to retrieve activity data: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Upload an asset image to storage.
     *
     * @param  \Illuminate\Http\UploadedFile  $file  The uploaded file
     * @return string  The path to the stored file
     * @throws \RuntimeException If file upload fails
     */
    protected function uploadAssetImage(UploadedFile $file): string
    {
        try {
            if (!$file->isValid()) {
                throw new \RuntimeException('Invalid file upload');
            }
            
            $path = $file->store('assets/images', 'public');
            
            if (!$path) {
                throw new \RuntimeException('Failed to store uploaded file');
            }
            
            return $path;
        } catch (\Exception $e) {
            Log::error('Failed to upload asset image: ' . $e->getMessage());
            throw new \RuntimeException('Failed to upload asset image: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get cache key for the given key and suffix.
     *
     * @param string $key
     * @param mixed $suffix
     * @return string
     */
    protected function getCacheKey(string $key, $suffix = ''): string
    {
        $key = self::CACHE_PREFIX . $key;
        
        if (!empty($suffix)) {
            $key .= '_' . md5(serialize($suffix));
        }
        
        return $key;
    }

    /**
     * Get cache tags for the given type.
     *
     * @param string $type
     * @return array
     */
    protected function getCacheTags(string $type = 'default'): array
    {
        return [self::CACHE_PREFIX . $type];
    }

    /**
     * Clear all cached data for assets.
     *
     * @return void
     * @throws \RuntimeException If cache clearing fails
     */
    public function clearCache(): void
    {
        try {
            $tags = [
                self::CACHE_PREFIX . 'list',
                self::CACHE_PREFIX . 'single',
                self::CACHE_PREFIX . 'maintenance'
            ];
            
            if (!Cache::supportsTags()) {
                // Fallback to clearing all cache if tags aren't supported
                Cache::flush();
                return;
            }
            
            Cache::tags($tags)->flush();
        } catch (\Exception $e) {
            Log::error('Failed to clear asset cache: ' . $e->getMessage());
            throw new \RuntimeException('Failed to clear asset cache: ' . $e->getMessage(), 0, $e);
        }
    }
}
