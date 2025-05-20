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

class ProcessBulkAssetDelete implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The asset IDs to delete.
     *
     * @var array
     */
    protected $assetIds;

    /**
     * The user who initiated the delete.
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
     * @return void
     */
    public function __construct(array $assetIds, User $user)
    {
        $this->assetIds = $assetIds;
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
            // Soft delete the assets in chunks to avoid memory issues
            Asset::whereIn('id', $this->assetIds)
                ->chunkById(100, function ($assets) {
                    foreach ($assets as $asset) {
                        // Skip if the asset doesn't exist anymore
                        if (! $asset) {
                            continue;
                        }

                        // Log the deletion before actually deleting
                        activity()
                            ->performedOn($asset)
                            ->causedBy($this->user)
                            ->withProperties([
                                'batch' => $this->batch() ? $this->batch()->id : null,
                            ])
                            ->log('Asset deleted in bulk operation');

                        // Delete the asset (soft delete)
                        $asset->delete();
                    }
                });

        } catch (\Exception $e) {
            Log::error('Bulk asset delete failed: '.$e->getMessage(), [
                'asset_ids' => $this->assetIds,
                'user_id' => $this->user->id,
                'exception' => $e,
            ]);

            throw $e; // Re-throw to allow for retries
        }
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Bulk asset delete job failed after all attempts', [
            'asset_ids' => $this->assetIds,
            'user_id' => $this->user->id,
            'exception' => $exception,
        ]);
    }
}
