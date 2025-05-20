<?php

namespace App\Models;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string|null $icon
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Asset> $assets
 * @property-read int|null $assets_count
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|AssetCategory withoutTrashed()
 * @mixin \Eloquent
 */
class AssetCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'icon',
        'eol',
        'require_acceptance',
        'checkin_email',
        'image',
        'user_id',
    ];

    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'category_id');
    }

    public function accessories()
    {
        return $this->hasMany(Accessory::class, 'category_id');
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'category_id');
    }

    public function consumables()
    {
        return $this->hasMany(Consumable::class, 'category_id');
    }

    public function licenses()
    {
        return $this->hasMany(License::class, 'category_id');
    }

    public function models()
    {
        return $this->hasMany(AssetModel::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/categories/' . $this->image);
        }
        
        return asset('img/default-category.png');
    }

    public function getItemCountAttribute()
    {
        return $this->assets()->count() + 
               $this->accessories()->count() + 
               $this->components()->count() + 
               $this->consumables()->count() + 
               $this->licenses()->count() + 
               $this->models()->count();
    }
}