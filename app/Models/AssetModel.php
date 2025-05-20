<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetModel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'model_number',
        'manufacturer_id',
        'category_id',
        'eol',
        'notes',
        'requestable',
        'depreciation_id',
        'image',
        'user_id',
    ];

    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function depreciation()
    {
        return $this->belongsTo(Depreciation::class, 'depreciation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'model_id');
    }

    public function getFullNameAttribute()
    {
        $name = [];
        
        if ($this->manufacturer) {
            $name[] = $this->manufacturer->name;
        }
        
        $name[] = $this->name;
        
        if ($this->model_number) {
            $name[] = $this->model_number;
        }
        
        return implode(' ', $name);
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/models/' . $this->image);
        }
        
        if ($this->category && $this->category->image) {
            return asset('storage/categories/' . $this->category->image);
        }
        
        return asset('img/default-model.png');
    }

    public function getEolMonthsAttribute()
    {
        if ($this->eol) {
            return $this->eol;
        }
        
        if ($this->category && $this->category->eol) {
            return $this->category->eol;
        }
        
        return config('defaults.eol_months', 36);
    }
}
