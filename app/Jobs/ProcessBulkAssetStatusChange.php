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

class ProcessBulkAssetStatusChange implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The asset IDs to update.
     *
     * @var array
     */
    protected $assetIds;

    /**
     * The new status.
     *
     * @var string
     */
    protected $status;

    /**
     * Status change notes.
     *
     * @var string|null
     */
    protected $notes;

    /**
     * The user who initiated the status change.
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
    public function __construct(array $assetIds, string $status, ?string $notes, User $user)
    {
        $this->assetIds = $assetIds;
        $this->status = $status;
        $this->notes = $notes;
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
                        if (! $asset) {
                            continue;
                        }

                        // Store the old status for logging
                        $oldStatus = $asset->status;

                        // Update the status
                        $asset->status = $this->status;

                        // Update status notes if provided
                        if ($this->notes) {
                            $asset->status_notes = $this->notes;
                        }

                        $asset->save();

                        // Log the status change
                        activity()
                            ->performedOn($asset)
                            ->causedBy($this->user)
                            ->withProperties([
                                'old_status' => $oldStatus,
                                'new_status' => $this->status,
                                'notes' => $this->notes,
                                'batch' => $this->batch() ? $this->batch()->id : null,
                            ])
                            ->log('Status updated in bulk operation');
                    }
                });

        } catch (\Exception $e) {
            Log::error('Bulk asset status update failed: '.$e->getMessage(), [
                'asset_ids' => $this->assetIds,
                'status' => $this->status,
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
        Log::error('Bulk asset status update job failed after all attempts', [
            'asset_ids' => $this->assetIds,
            'status' => $this->status,
            'user_id' => $this->user->id,
            'exception' => $exception,
        ]);
    }
}
