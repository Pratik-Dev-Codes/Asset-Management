<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Location;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Default cache TTL in minutes
     */
    protected const DEFAULT_CACHE_TTL = 60; // 1 hour

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('cache.service', function ($app) {
            return $this;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Cache frequently accessed data
        $this->cacheReferenceData();
        
        // Clear cache on model events
        $this->registerModelEvents();
    }
    
    /**
     * Cache reference data that's frequently accessed
     */
    protected function cacheReferenceData(): void
    {
        try {
            // Cache asset categories
            Cache::remember('asset_categories', self::DEFAULT_CACHE_TTL, function () {
                return AssetCategory::orderBy('name')->get();
            });
            
            // Cache departments
            Cache::remember('departments', self::DEFAULT_CACHE_TTL, function () {
                return Department::orderBy('name')->get();
            });
            
            // Cache locations
            Cache::remember('locations', self::DEFAULT_CACHE_TTL, function () {
                return Location::orderBy('name')->get();
            });
            
            // Cache asset counts by status
            Cache::remember('asset_counts', self::DEFAULT_CACHE_TTL, function () {
                return [
                    'total' => Asset::count(),
                    'active' => Asset::where('status', 'active')->count(),
                    'in_maintenance' => Asset::where('status', 'in_maintenance')->count(),
                    'retired' => Asset::where('status', 'retired')->count(),
                ];
            });
            
        } catch (\Exception $e) {
            Log::error('Failed to cache reference data: ' . $e->getMessage());
        }
    }
    
    /**
     * Register model events to clear cache
     */
    protected function registerModelEvents(): void
    {
        $models = [
            Asset::class,
            AssetCategory::class,
            Department::class,
            Location::class,
        ];
        
        foreach ($models as $model) {
            $model::saved(function () use ($model) {
                $this->clearModelCache($model);
            });
            
            $model::deleted(function () use ($model) {
                $this->clearModelCache($model);
            });
        }
    }
    
    /**
     * Clear cache for a specific model
     */
    protected function clearModelCache(string $model): void
    {
        $prefix = strtolower(class_basename($model));
        Cache::forget($prefix . '_all');
        
        // Clear related caches
        if ($model === Asset::class) {
            Cache::forget('asset_counts');
        }
    }
    
    /**
     * Get cached data or retrieve and cache it
     */
    public function remember(string $key, \Closure $callback, ?int $minutes = null)
    {
        return Cache::remember($key, $minutes ?? self::DEFAULT_CACHE_TTL, $callback);
    }
    
    /**
     * Clear all application caches
     */
    public function clearAllCaches(): void
    {
        try {
            Cache::flush();
            $this->cacheReferenceData(); // Rebuild caches
        } catch (\Exception $e) {
            Log::error('Failed to clear caches: ' . $e->getMessage());
        }
    }
}
