<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $postal_code
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Asset> $assets
 * @property-read int|null $assets_count
 * @property-read \Kalnoy\Nestedset\Collection<int, Location> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Department> $departments
 * @property-read int|null $departments_count
 * @property-read string $delete_url
 * @property-read string $edit_url
 * @property-read string $full_address
 * @property-read bool $has_children
 * @property-read bool $is_leaf
 * @property-read int $level
 * @property-read string $location_path
 * @property-read string $url
 * @property-read Location|null $parent
 *
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location active()
 * @method static \Kalnoy\Nestedset\Collection<int, static> all($columns = ['*'])
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location ancestorsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location ancestorsOf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location applyNestedSetScope(?string $table = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location countErrors()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location d()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location defaultOrder(string $dir = 'asc')
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location descendantsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location descendantsOf($id, array $columns = [], $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location fixSubtree($root)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location fixTree($root = null)
 * @method static \Kalnoy\Nestedset\Collection<int, static> get($columns = ['*'])
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location getNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location getPlainNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location getTotalErrors()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location hasChildren()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location hasParent()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location isBroken()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location leaves(array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location makeGap(int $cut, int $height)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location moveNode($key, $position)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location newModelQuery()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location newQuery()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location ofType($type)
 * @method static \Illuminate\Database\Eloquent\Builder|Location onlyTrashed()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location orWhereAncestorOf(bool $id, bool $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location orWhereDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location orWhereNodeBetween($values)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location orWhereNotDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location query()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location rebuildSubtree($root, array $data, $delete = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location rebuildTree(array $data, $delete = false, $root = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location reversed()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location root(array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereAddress($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereAncestorOf($id, $andSelf = false, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereAncestorOrSelf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereCity($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereCode($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereCountry($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereCreatedAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereDeletedAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereDescendantOf($id, $boolean = 'and', $not = false, $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereDescendantOrSelf(string $id, string $boolean = 'and', string $not = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereDescription($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereId($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereIsAfter($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereIsBefore($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereIsLeaf()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereIsRoot()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereName($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereNodeBetween($values, $boolean = 'and', $not = false, $query = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereNotDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereParentId($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location wherePostalCode($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereState($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location whereUpdatedAt($value)
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location withDepth(string $as = 'depth')
 * @method static \Illuminate\Database\Eloquent\Builder|Location withTrashed()
 * @method static \Kalnoy\Nestedset\QueryBuilder|Location withoutRoot()
 * @method static \Illuminate\Database\Eloquent\Builder|Location withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Location extends Model
{
    use HasFactory, NodeTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'contact_person',
        'contact_email',
        'contact_phone',
        'type',
        'parent_id',
        'is_active',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'full_address',
        'location_path',
        'is_leaf',
        'has_children',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Set default code if not provided
            if (empty($model->code)) {
                $model->code = static::generateUniqueCode($model->name);
            }

            // Set default active status if not provided
            if (! isset($model->is_active)) {
                $model->is_active = true;
            }
        });

        static::updating(function ($model) {
            // Prevent setting a location as its own parent
            if ($model->isDirty('parent_id') && $model->parent_id == $model->getKey()) {
                throw new \InvalidArgumentException('A location cannot be a parent of itself');
            }
        });
    }

    /**
     * Generate a unique code from the location name
     */
    protected static function generateUniqueCode(string $name): string
    {
        $baseCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 10));
        $code = $baseCode.'_'.substr(uniqid(), -6);

        // Ensure code is unique
        $count = 1;
        while (static::where('code', $code)->exists()) {
            $code = $baseCode.'_'.substr(uniqid(), -6);
            if ($count++ > 10) {
                throw new \RuntimeException('Unable to generate unique location code');
            }
        }

        return $code;
    }

    /**
     * Get all locations in a flat list with proper indentation.
     *
     * @param  int  $exceptId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getTree($exceptId = null)
    {
        $query = static::with('ancestors')
            ->orderBy('_lft');

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->get();
    }

    /**
     * Get the location's full address.
     *
     * @return string
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    /**
     * Get the location's path as a breadcrumb.
     *
     * @return string
     */
    public function getLocationPathAttribute()
    {
        if (! $this->parent_id) {
            return $this->name;
        }

        $ancestors = $this->ancestors()->pluck('name')->toArray();
        $ancestors[] = $this->name;

        return implode(' > ', $ancestors);
    }

    /**
     * Check if the location is a leaf (has no children).
     *
     * @return bool
     */
    public function getIsLeafAttribute()
    {
        return $this->children()->count() === 0;
    }

    /**
     * Check if the location has children.
     *
     * @return bool
     */
    public function getHasChildrenAttribute()
    {
        return $this->children()->count() > 0;
    }

    /**
     * Scope a query to only include active locations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include locations of a given type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the URL for viewing the location.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return route('locations.show', $this->id);
    }

    /**
     * Get the edit URL for the location.
     *
     * @return string
     */
    public function getEditUrlAttribute()
    {
        return route('locations.edit', $this->id);
    }

    /**
     * Get the delete URL for the location.
     *
     * @return string
     */
    public function getDeleteUrlAttribute()
    {
        return route('locations.destroy', $this->id);
    }

    /**
     * Get departments in this location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Get assets in this location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get the location's hierarchy level.
     *
     * @return int
     */
    public function getLevelAttribute()
    {
        return $this->ancestors()->count();
    }

    /**
     * Get the nested children of the location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    /**
     * Get all locations in a flat list with proper indentation for select dropdowns.
     *
     * @param  int  $exceptId
     * @return \Illuminate\Support\Collection
     */
    public static function getNestedList($exceptId = null)
    {
        $locations = static::with('children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        if ($exceptId) {
            $locations = $locations->filter(function ($location) use ($exceptId) {
                return $location->id != $exceptId;
            });
        }

        return $locations;
    }

    /**
     * Check if the location is a descendant of the given location.
     *
     * @param  int  $locationId
     * @return bool
     */
    public function isDescendantOf($locationId)
    {
        return $this->ancestors()->where('id', $locationId)->exists();
    }
}
