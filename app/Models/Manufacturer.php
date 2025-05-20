<?php

namespace App\Models;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manufacturer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'url',
        'support_url',
        'support_phone',
        'support_email',
        'image',
        'notes',
        'user_id',
    ];

    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'manufacturer_id');
    }

    public function accessories()
    {
        return $this->hasMany(Accessory::class, 'manufacturer_id');
    }

    public function consumables()
    {
        return $this->hasMany(Consumable::class, 'manufacturer_id');
    }

    public function licenses()
    {
        return $this->hasMany(License::class, 'manufacturer_id');
    }

    public function models()
    {
        return $this->hasMany(AssetModel::class, 'manufacturer_id');
    }

    public function getSupportInfoAttribute()
    {
        $info = [];
        
        if ($this->support_phone) {
            $info[] = 'Phone: ' . $this->support_phone;
        }
        
        if ($this->support_email) {
            $info[] = 'Email: ' . $this->support_email;
        }
        
        if ($this->url) {
            $info[] = 'Website: ' . $this->url;
        }
        
        if ($this->support_url) {
            $info[] = 'Support: ' . $this->support_url;
        }
        
        return implode("\n", $info);
    }
}
