<?php

namespace App\Models;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\License;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'address2',
        'city',
        'state',
        'country',
        'zip',
        'phone',
        'fax',
        'email',
        'contact_person',
        'contact_phone',
        'contact_email',
        'website',
        'notes',
        'tax_id',
        'payment_terms',
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
        return $this->hasMany(Asset::class, 'supplier_id');
    }

    public function accessories()
    {
        return $this->hasMany(Accessory::class, 'supplier_id');
    }

    public function consumables()
    {
        return $this->hasMany(Consumable::class, 'supplier_id');
    }

    public function licenses()
    {
        return $this->hasMany(License::class, 'supplier_id');
    }

    public function purchases()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    public function getFullAddressAttribute()
    {
        $address = [
            $this->address,
            $this->address2,
            $this->city,
            $this->state,
            $this->zip,
            $this->country,
        ];

        return implode("\n", array_filter($address));
    }

    public function getContactInfoAttribute()
    {
        $info = [
            $this->contact_person,
            $this->contact_phone,
            $this->contact_email,
        ];

        return implode("\n", array_filter($info));
    }
}
