<?php

namespace App\Models;

use App\Models\AssetAttachment;
use App\Models\AssetCategory;
use App\Models\AssetCustomField;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $asset_code
 * @property string $name
 * @property string|null $description
 * @property int $category_id
 * @property int $location_id
 * @property int|null $department_id
 * @property string $status
 * @property string|null $manufacturer
 * @property string|null $model
 * @property string|null $serial_number
 * @property \Illuminate\Support\Carbon|null $purchase_date
 * @property string|null $purchase_cost
 * @property int|null $supplier_id
 * @property string|null $purchase_order_number
 * @property string|null $condition
 * @property string|null $warranty_start_date
 * @property string|null $warranty_expiry_date
 * @property string|null $warranty_provider
 * @property string|null $warranty_details
 * @property string|null $depreciation_method
 * @property int|null $expected_lifetime_years
 * @property string|null $salvage_value
 * @property string|null $current_value
 * @property string|null $depreciation_rate
 * @property string|null $depreciation_start_date
 * @property string|null $depreciation_frequency
 * @property string|null $insurer_company
 * @property string|null $policy_number
 * @property string|null $coverage_details
 * @property string|null $insurance_start_date
 * @property string|null $insurance_end_date
 * @property string|null $premium_amount
 * @property string|null $barcode
 * @property string|null $qr_code
 * @property int|null $assigned_to
 * @property string|null $assigned_date
 * @property string|null $notes
 * @property int $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $assignedTo
 * @property-read AssetCategory $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AssetCustomField> $customFields
 * @property-read int|null $custom_fields_count
 * @property-read \App\Models\Department|null $department
 * @property-read mixed $custom_fields
 * @property-read mixed $formatted_purchase_cost
 * @property-read mixed $image_url
 * @property-read mixed $status_badge
 * @property-read mixed $tag_list
 * @property-read \App\Models\Location $location
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceLog> $maintenanceLogs
 * @property-read int|null $maintenance_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MaintenanceSchedule> $maintenanceSchedules
 * @property-read int|null $maintenance_schedules_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Tag> $tags
 * @property-read int|null $tags_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Asset dueForMaintenance($days = 7)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Asset query()
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereAssetCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereAssignedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereCoverageDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereCurrentValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereDepreciationFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereDepreciationMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereDepreciationRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereDepreciationStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereExpectedLifetimeYears($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereInsuranceEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereInsuranceStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereInsurerCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereManufacturer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset wherePolicyNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset wherePremiumAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset wherePurchaseCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset wherePurchaseOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereQrCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereSalvageValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereWarrantyDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereWarrantyExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereWarrantyProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Asset whereWarrantyStartDate($value)
 *
 * @mixin \Eloquent
 */
/**
 * Asset Model
 *
 * Represents a physical or digital asset in the system.
 *
 * @property int $id
 * @property string $name
 * @property string $asset_code
 * @property string $status
 * @property int $category_id
 * @property int $location_id
 * @property int $department_id
 * @property int|null $assigned_to
 * @property float $purchase_cost
 * @property \Illuminate\Support\Carbon $purchase_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AssetCategory $category
 * @property-read \App\Models\Location $location
 * @property-read \App\Models\Department $department
 * @property-read \App\Models\User|null $assignedTo
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Maintenance[] $maintenance
 */
class Asset extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['category', 'location', 'department', 'assignedTo'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        parent::booted();

        static::saved(function ($asset) {
            Cache::tags(['assets'])->flush();
        });

        static::deleted(function ($asset) {
            Cache::tags(['assets'])->flush();
        });
    }

    /**
     * The accessors to append to the model's array form.
     * Empty by default to prevent unnecessary calculations.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'purchase_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_expiry_date' => 'date',
        'depreciation_start_date' => 'date',
        'insurance_start_date' => 'date',
        'insurance_end_date' => 'date',
        'assigned_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:2',
        'premium_amount' => 'decimal:2',
        'expected_lifetime_years' => 'integer',
        'warranty_months' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'custom_fields' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'asset_tag',
        'serial_number',
        'model',
        'manufacturer',
        'category_id',
        'location_id',
        'department_id',
        'status',
        'purchase_date',
        'purchase_cost',
        'supplier_id',
        'purchase_order_number',
        'condition',
        'warranty_start_date',
        'warranty_expiry_date',
        'warranty_provider',
        'warranty_details',
        'depreciation_method',
        'expected_lifetime_years',
        'salvage_value',
        'current_value',
        'depreciation_rate',
        'depreciation_start_date',
        'depreciation_frequency',
        'insurer_company',
        'policy_number',
        'coverage_details',
        'insurance_start_date',
        'insurance_end_date',
        'premium_amount',
        'barcode',
        'qr_code',
        'assigned_to',
        'assigned_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be logged for all events.
     * Reduced to only essential attributes to minimize memory usage.
     *
     * @var array
     */
    protected static $logAttributes = [
        'name', 'status', 'category_id', 'location_id', 'department_id',
        'assigned_to', 'purchase_cost', 'purchase_date',
    ];

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
            ->logOnly($this->logAttributes)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('asset');
    }

    /**
     * Get the description for the event.
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        return "Asset {$this->name} has been {$eventName}";
    }

    /**
     * Get the category that owns the asset.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo(AssetCategory::class)->withDefault();
    }

    /**
     * Get the location where the asset is located.
     *
     * @return BelongsTo
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the department that owns the asset.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user to whom the asset is assigned.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get all attachments for the asset.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachments()
    {
        return $this->hasMany(AssetAttachment::class);
    }

    /**
     * Get the maintenance logs for the asset.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function maintenanceLogs()
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    /**
     * Get the maintenance schedules for the asset.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function maintenanceSchedules()
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    /**
     * Scope a query to only include assets that are due for maintenance.
     * Optimized to use subquery for better performance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDueForMaintenance($query, $days = 7)
    {
        return $query->whereExists(function ($subquery) use ($days) {
            $subquery->select(DB::raw(1))
                ->from('maintenance_schedules')
                ->whereColumn('maintenance_schedules.asset_id', 'assets.id')
                ->where('next_maintenance_date', '<=', now()->addDays($days))
                ->where('is_active', true);
        });
    }

    /**
     * Scope a query to only include assets with the given status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include assets assigned to a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope a query to only include assets in a specific location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $locationId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope a query to only include assets in a specific department.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $departmentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope a query to only include assets in a specific category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Get the count of assets by status with optimized caching.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getCountByStatus()
    {
        return Cache::remember('asset_status_counts', now()->addHours(6), function () {
            return self::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();
        });
    }

    /**
     * Get the count of assets by category with optimized caching.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getCountByCategory()
    {
        return Cache::remember('asset_category_counts', now()->addDay(), function () {
            return self::select('asset_categories.name', DB::raw('count(*) as total'))
                ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
                ->groupBy('asset_categories.name')
                ->orderBy('total', 'desc')
                ->pluck('total', 'name')
                ->toArray();
        });
    }

    /**
     * Get the total value of all assets with optimized caching.
     *
     * @return float
     */
    public static function getTotalValue()
    {
        return Cache::remember('asset_total_value', now()->addDay(), function () {
            return (float) self::sum('purchase_cost');
        });
    }

    /**
     * Get the total count of assets.
     *
     * @return int
     */
    public static function getTotalCount()
    {
        return Cache::remember('asset_total_count', now()->addDay(), function () {
            return self::count();
        });
    }

    /**
     * Get the recently added assets with optimized caching and selective loading.
     *
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecent($limit = 5)
    {
        return Cache::remember('recent_assets_'.$limit, now()->addHour(), function () use ($limit) {
            return self::select(['id', 'name', 'asset_code', 'status', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get the assets that are due for maintenance with optimized caching.
     *
     * @param  int  $days
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getDueForMaintenance($days = 7, $limit = 10)
    {
        $cacheKey = "due_for_maintenance_{$days}_{$limit}";

        return Cache::remember($cacheKey, now()->addHour(), function () use ($days, $limit) {
            return self::select(['id', 'name', 'asset_code', 'status', 'last_maintenance_date'])
                ->whereHas('maintenanceSchedules', function ($query) use ($days) {
                    $query->where('next_maintenance_date', '<=', now()->addDays($days))
                        ->where('is_active', true);
                })
                ->with(['maintenanceSchedules' => function ($query) use ($days) {
                    $query->where('next_maintenance_date', '<=', now()->addDays($days))
                        ->where('is_active', true)
                        ->select(['id', 'asset_id', 'next_maintenance_date']);
                }])
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get the URL to the asset's image or a placeholder if not available.
     */
    public function getImageUrlAttribute()
    {
        return $this->image_path
            ? asset('storage/'.$this->image_path)
            : asset('images/placeholder-asset.png');
    }

    /**
     * Get the formatted purchase cost with currency symbol.
     */
    public function getFormattedPurchaseCostAttribute()
    {
        return '₹'.number_format($this->purchase_cost, 2);
    }

    /**
     * Get the status badge HTML for the asset.
     */
    public function getStatusBadgeAttribute()
    {
        $status = $this->status;
        $badgeClass = 'bg-gray-100 text-gray-800';

        if ($status === 'assigned') {
            $badgeClass = 'bg-blue-100 text-blue-800';
        } elseif ($status === 'in_maintenance') {
            $badgeClass = 'bg-yellow-100 text-yellow-800';
        } elseif ($status === 'retired') {
            $badgeClass = 'bg-red-100 text-red-800';
        } elseif ($status === 'available') {
            $badgeClass = 'bg-green-100 text-green-800';
        }

        return '<span class="px-2 py-1 text-xs font-medium rounded-full '.$badgeClass.'">'.ucfirst(str_replace('_', ' ', $status)).'</span>';
    }

    /**
     * Get all custom fields for the asset.
     */
    public function customFields()
    {
        return $this->hasMany(AssetCustomField::class);
    }

    /**
     * Get the custom fields as a key-value array.
     */
    public function getCustomFieldsAttribute()
    {
        return $this->customFields()->pluck('field_value', 'field_name');
    }

    /**
     * Get all of the tags for the asset.
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Get the tag list for the asset.
     */
    public function getTagListAttribute()
    {
        return $this->tags->pluck('name');
    }

    /**
     * Sync tags for the asset.
     */
    public function syncTags($tags)
    {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        $tagIds = [];
        foreach ($tags as $tagName) {
            $tag = Tag::firstOrCreate(['name' => trim($tagName)]);
            $tagIds[] = $tag->id;
        }

        $this->tags()->sync($tagIds);
    }

    /**
     * Set a custom field value.
     */
    public function setCustomField($fieldName, $value)
    {
        return $this->customFields()->updateOrCreate(
            ['field_name' => $fieldName],
            ['field_value' => $value]
        );
    }

    /**
     * Get a custom field value.
     */
    public function getCustomField($fieldName, $default = null)
    {
        $field = $this->customFields()->where('field_name', $fieldName)->first();

        return $field ? $field->field_value : $default;
    }

    /**
     * Get the default relationships to load.
     */
    public static function getDefaultRelations(): array
    {
        return ['category', 'location'];
    }

    /**
     * Get the default appends to include.
     */
    public static function getDefaultAppends(): array
    {
        return ['image_url', 'formatted_purchase_cost', 'status_badge'];
    }

    /**
     * Convert the model instance to an array with optimized attribute loading.
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        // Only append attributes that are actually needed
        $neededAppends = array_intersect($this->appends, static::getDefaultAppends());

        foreach ($neededAppends as $attribute) {
            if ($this->shouldAppend($attribute)) {
                $array[$attribute] = $this->getAttribute($attribute);
            }
        }

        return $array;
    }

    /**
     * Check if an attribute should be appended.
     */
    protected function shouldAppend(string $attribute): bool
    {
        return in_array($attribute, $this->appends) || in_array($attribute, static::getDefaultAppends());
    }

    /**
     * Load the default relationships.
     *
     * @return $this
     */
    public function loadDefaults()
    {
        return $this->load(static::getDefaultRelations());
    }

    /**
     * Load all available relationships.
     *
     * @return $this
     */
    public function loadAll()
    {
        return $this->load([
            'category',
            'location',
            'department',
            'assignedTo',
            'customFields',
            'tags',
            'attachments',
            'maintenanceLogs',
            'maintenanceSchedules',
        ]);
    }
}
