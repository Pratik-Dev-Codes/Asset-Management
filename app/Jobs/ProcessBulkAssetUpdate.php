<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBulkAssetUpdate implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The asset IDs to update.
     *
     * @var array
     */
    protected $assetIds;

    /**
     * The updates to apply.
     *
     * @var array
     */
    protected $updates;

    /**
     * The user who initiated the update.
     *
     * @var User
     */
    protected $user;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 30;

    /**
     * Create a new job instance.
     *
     * @param array $assetIds
     * @param array $updates
     * @param User $user
     * @return void
     */
    public function __construct(array $assetIds, array $updates, User $user)
    {
        $this->assetIds = $assetIds;
        $this->updates = $updates;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Check if batch has been cancelled
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        try {
            // Update the assets in chunks to avoid memory issues
            Asset::whereIn('id', $this->assetIds)
                ->chunkById(100, function ($assets) {
                    foreach ($assets as $asset) {
                        // Skip if the asset doesn't exist anymore
                        if (!$asset) {
                            continue;
                        }

                        // Update the asset
                        $asset->update($this->updates);
                        
                        // Log the update
                        activity()
                            ->performedOn($asset)
                            ->causedBy($this->user)
                            ->withProperties([
                                'updates' => $this->updates,
                                'batch' => $this->batch() ? $this->batch()->id : null,
                            ])
                            ->log('Bulk update applied');
                    }
                });

        } catch (\Exception $e) {
            Log::error('Bulk asset update failed: ' . $e->getMessage(), [
                'asset_ids' => $this->assetIds,
                'updates' => $this->updates,
                'user_id' => $this->user->id,
                'exception' => $e,
            ]);
            
            throw $e; // Re-throw to allow for retries
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Bulk asset update job failed after all attempts', [
            'asset_ids' => $this->assetIds,
            'updates' => $this->updates,
            'user_id' => $this->user->id,
            'exception' => $exception,
        ]);
    }
}
