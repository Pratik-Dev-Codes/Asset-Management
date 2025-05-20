<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $field_name
 * @property string $field_type
 * @property bool $is_required
 * @property array|null $field_options
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\Asset|null $asset
 * @property-read mixed $display_value
 * @property array $options
 *
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField ofType($type)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField query()
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField whereFieldName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField whereFieldOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField whereFieldType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCustomField whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AssetCustomField extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'asset_id',
        'field_name',
        'field_label',
        'field_type',
        'field_value',
        'is_required',
        'field_options',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_required' => 'boolean',
        'field_options' => 'array',
        'order' => 'integer',
    ];

    /**
     * Get the asset that owns the custom field.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Scope a query to only include fields of a given type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('field_type', $type);
    }

    /**
     * Get the field options as an array.
     *
     * @return array
     */
    public function getOptionsAttribute()
    {
        return is_array($this->field_options)
            ? $this->field_options
            : json_decode($this->field_options, true) ?? [];
    }

    /**
     * Set the field options from an array.
     *
     * @param  array  $value
     * @return void
     */
    public function setOptionsAttribute($value)
    {
        $this->attributes['field_options'] = is_array($value)
            ? json_encode($value)
            : $value;
    }

    /**
     * Get the display value of the field.
     *
     * @return mixed
     */
    public function getDisplayValueAttribute()
    {
        switch ($this->field_type) {
            case 'select':
            case 'radio':
            case 'checkbox':
                $options = $this->options;
                $values = is_array($this->field_value)
                    ? $this->field_value
                    : json_decode($this->field_value, true) ?? [];

                if (empty($values)) {
                    return null;
                }

                return is_array($values)
                    ? implode(', ', array_map(function ($value) use ($options) {
                        return $options[$value] ?? $value;
                    }, $values))
                    : ($options[$values] ?? $values);

            case 'boolean':
                return (bool) $this->field_value ? 'Yes' : 'No';

            case 'date':
                return $this->field_value ? \Carbon\Carbon::parse($this->field_value)->format('Y-m-d') : null;

            case 'datetime':
                return $this->field_value ? \Carbon\Carbon::parse($this->field_value)->format('Y-m-d H:i:s') : null;

            default:
                return $this->field_value;
        }
    }
}
