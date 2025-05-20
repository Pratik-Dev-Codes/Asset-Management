<?php

namespace App\Observers;

use App\Models\Asset;
use Illuminate\Support\Facades\Log;

class AssetObserver
{
    /**
     * Handle the Asset "created" event.
     */
    public function created(Asset $asset): void
    {
        Log::info('New asset created', [
            'asset_id' => $asset->id,
            'name' => $asset->name,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle the Asset "updated" event.
     */
    public function updated(Asset $asset): void
    {
        // Log changes to sensitive fields
        $changes = [];

        // Example of tracking specific field changes
        foreach ($asset->getDirty() as $key => $value) {
            $changes[$key] = [
                'old' => $asset->getOriginal($key),
                'new' => $value,
            ];
        }

        if (! empty($changes)) {
            Log::info('Asset updated', [
                'asset_id' => $asset->id,
                'changes' => $changes,
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Handle the Asset "deleted" event.
     */
    public function deleted(Asset $asset): void
    {
        Log::warning('Asset deleted', [
            'asset_id' => $asset->id,
            'name' => $asset->name,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle the Asset "restored" event.
     */
    public function restored(Asset $asset): void
    {
        Log::info('Asset restored', [
            'asset_id' => $asset->id,
            'name' => $asset->name,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle the Asset "force deleted" event.
     */
    public function forceDeleted(Asset $asset): void
    {
        Log::warning('Asset force deleted', [
            'asset_id' => $asset->id,
            'name' => $asset->name,
            'user_id' => auth()->id(),
        ]);
    }
}
