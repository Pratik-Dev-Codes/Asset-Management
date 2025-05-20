<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property string $frequency
 * @property int|null $interval_days
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int $is_active
 * @property string|null $instructions
 * @property string|null $estimated_cost
 * @property int|null $assigned_to
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Asset $asset
 * @property-read \App\Models\User|null $assignedTo
 * @property-read bool $is_overdue
 * @property-read string $status
 * @property-read string $status_badge
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule active()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule dueWithinDays($days = 7)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereEstimatedCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereIntervalDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereMaintenanceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|MaintenanceSchedule withoutTrashed()
 * @mixin \Eloquent
 */
class MaintenanceSchedule extends Model
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
    protected static $ignoreChangedAttributes = ['updated_at', 'next_due_date'];
    
    /**
     * Configure the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('maintenance-schedule');
    }
    
    /**
     * Get the description for the event.
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Maintenance schedule for asset ID {$this->asset_id} has been {$eventName}";
    }

    protected $fillable = [
        'asset_id',
        'title',
        'description',
        'frequency',
        'interval',
        'start_date',
        'end_date',
        'next_due_date',
        'assigned_to',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_due_date' => 'date',
    ];

    /**
     * Get the asset that this maintenance schedule belongs to.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the user assigned to this maintenance schedule.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    
    /**
     * Scope a query to only include active maintenance schedules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * Scope a query to only include schedules due within the next X days.
     */
    public function scopeDueWithinDays($query, $days = 7)
    {
        return $query->where('next_due_date', '<=', now()->addDays($days))
                    ->where('is_active', true);
    }
    
    /**
     * Check if the schedule is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->next_due_date && $this->next_due_date->isPast();
    }
    
    /**
     * Get the status of the maintenance schedule.
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }
        
        if ($this->is_overdue) {
            return 'Overdue';
        }
        
        if ($this->next_due_date && $this->next_due_date->isToday()) {
            return 'Due Today';
        }
        
        return 'Upcoming';
    }
    
    /**
     * Get the status badge HTML for the maintenance schedule.
     */
    public function getStatusBadgeAttribute(): string
    {
        $status = $this->status;
        
        $classes = [
            'Inactive' => 'bg-gray-100 text-gray-800',
            'Overdue' => 'bg-red-100 text-red-800',
            'Due Today' => 'bg-yellow-100 text-yellow-800',
            'Upcoming' => 'bg-blue-100 text-blue-800',
        ];
        
        $class = $classes[$status] ?? 'bg-gray-100 text-gray-800';
        
        return sprintf(
            '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full %s">%s</span>', 
            $class, 
            $status
        );
    }
    
    /**
     * Calculate the next due date based on the frequency and interval.
     */
    public function calculateNextDueDate(): ?Carbon
    {
        if (!$this->is_active || !$this->frequency || !$this->next_due_date) {
            return null;
        }
        
        $nextDueDate = clone $this->next_due_date;
        
        switch ($this->frequency) {
            case 'daily':
                return $nextDueDate->addDays($this->interval);
            case 'weekly':
                return $nextDueDate->addWeeks($this->interval);
            case 'monthly':
                return $nextDueDate->addMonths($this->interval);
            case 'quarterly':
                return $nextDueDate->addMonths($this->interval * 3);
            case 'yearly':
                return $nextDueDate->addYears($this->interval);
            default:
                return null;
        }
    }
}