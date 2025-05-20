<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * 
 *
 * @property int $id
 * @property int $asset_id
 * @property string $maintenance_type
 * @property string $description
 * @property string $scheduled_date
 * @property string|null $completion_date
 * @property string $status
 * @property string|null $cost
 * @property int|null $assigned_to
 * @property int|null $performed_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Asset $asset
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Document> $documents
 * @property-read int|null $documents_count
 * @property-read mixed $formatted_cost
 * @property-read string $status_badge
 * @property-read \App\Models\User|null $performedBy
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog completed()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog pending()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereCompletionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereMaintenanceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog wherePerformedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereScheduledDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceLog withoutTrashed()
 * @mixin \Eloquent
 */
class MaintenanceLog extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    
    /**
     * The attributes that should be logged for all events.
     *
     * @var array
     */
    protected static $logAttributes = ['*'];
    
    /**
     * The attributes that should be ignored for diff.
     *
     * @var array
     */
    protected static $ignoreChangedAttributes = ['updated_at'];
    
    /**
     * Configure the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('maintenance');
    }
    
    /**
     * Get the description for the event.
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Maintenance log for asset ID {$this->asset_id} has been {$eventName}";
    }

    protected $fillable = [
        'asset_id',
        'title',
        'description',
        'maintenance_type',
        'maintenance_date',
        'completion_datetime',
        'cost',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'maintenance_date' => 'datetime',
        'completion_datetime' => 'datetime',
    ];

    /**
     * Get the asset that this maintenance log belongs to.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the user who performed the maintenance.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get all of the maintenance log's documents.
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
    
    /**
     * Scope a query to only include completed maintenance logs.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completion_datetime');
    }
    
    /**
     * Scope a query to only include pending maintenance logs.
     */
    public function scopePending($query)
    {
        return $query->whereNull('completion_datetime');
    }
    
    /**
     * Get the formatted cost with currency symbol.
     */
    public function getFormattedCostAttribute()
    {
        return $this->cost ? '₹' . number_format($this->cost, 2) : 'N/A';
    }
    
    /**
     * Get the status of the maintenance.
     */
    public function getStatusAttribute(): string
    {
        return $this->completion_datetime ? 'Completed' : 'Pending';
    }
    
    /**
     * Get the status badge HTML for the maintenance log.
     */
    public function getStatusBadgeAttribute(): string
    {
        $status = $this->status;
        $class = $status === 'Completed' 
            ? 'bg-green-100 text-green-800' 
            : 'bg-yellow-100 text-yellow-800';
            
        return sprintf(
            '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full %s">%s</span>', 
            $class, 
            $status
        );
    }
}