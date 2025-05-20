<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Asset> $assets
 * @property-read int|null $assets_count
 * @property-write mixed $name
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Tag ofType($type = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Tag query()
 *
 * @mixin \Eloquent
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'order_column',
    ];

    protected $casts = [
        'order_column' => 'integer',
    ];

    /**
     * Get the route key name for Laravel's route model binding.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get all of the assets that are assigned this tag.
     */
    public function assets()
    {
        return $this->morphedByMany(Asset::class, 'taggable');
    }

    /**
     * Scope a query to only include tags of a given type.
     */
    public function scopeOfType($query, $type = null)
    {
        if (is_null($type)) {
            return $query->whereNull('type');
        }

        return $query->where('type', $type);
    }

    /**
     * Set the name and slug attributes.
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    /**
     * Find a tag by name, or create it if it doesn't exist.
     */
    public static function findOrCreate($name, $type = null)
    {
        $tag = static::where('name', $name)->where('type', $type)->first();

        return $tag ?: static::create([
            'name' => $name,
            'type' => $type,
        ]);
    }
}
