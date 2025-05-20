<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatusLabel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'color',
        'show_in_nav',
        'notes',
        'user_id',
    ];

    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    // Status types
    const TYPE_DEPLOYABLE = 'deployable';
    const TYPE_PENDING = 'pending';
    const TYPE_ARCHIVED = 'archived';
    const TYPE_UNDER_MAINTENANCE = 'under_maintenance';
    const TYPE_RETIRED = 'retired';
    const TYPE_LOST = 'lost';
    const TYPE_DAMAGED = 'damaged';
    const TYPE_READY_TO_DEPLOY = 'ready_to_deploy';

    public static function getTypes()
    {
        return [
            self::TYPE_DEPLOYABLE => 'Deployable',
            self::TYPE_PENDING => 'Pending',
            self::TYPE_ARCHIVED => 'Archived',
            self::TYPE_UNDER_MAINTENANCE => 'Under Maintenance',
            self::TYPE_RETIRED => 'Retired',
            self::TYPE_LOST => 'Lost',
            self::TYPE_DAMAGED => 'Damaged',
            self::TYPE_READY_TO_DEPLOY => 'Ready to Deploy',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'status_id');
    }

    public function accessories()
    {
        return $this->hasMany(Accessory::class, 'status_id');
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'status_id');
    }

    public function consumables()
    {
        return $this->hasMany(Consumable::class, 'status_id');
    }

    public function licenses()
    {
        return $this->hasMany(License::class, 'status_id');
    }

    public function getTypeNameAttribute()
    {
        $types = self::getTypes();
        return $types[$this->type] ?? 'Unknown';
    }

    public function getStatusBadgeAttribute()
    {
        $colors = [
            self::TYPE_DEPLOYABLE => 'success',
            self::TYPE_PENDING => 'info',
            self::TYPE_ARCHIVED => 'secondary',
            self::TYPE_UNDER_MAINTENANCE => 'warning',
            self::TYPE_RETIRED => 'dark',
            self::TYPE_LOST => 'danger',
            self::TYPE_DAMAGED => 'danger',
            self::TYPE_READY_TO_DEPLOY => 'primary',
        ];

        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            $colors[$this->type] ?? 'secondary',
            e($this->name)
        );
    }
}
