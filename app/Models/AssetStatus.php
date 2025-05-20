<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetStatus extends Model
{
    // Status constants
    public const DEPLOYED = 1;

    public const READY_TO_DEPLOY = 2;

    public const PENDING = 3;

    public const ARCHIVED = 4;

    public const OUT_FOR_REPAIR = 5;

    public const BROKEN = 6;

    public const LOST = 7;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'color',
        'notes',
        'show_in_nav',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'show_in_nav' => 'boolean',
    ];

    /**
     * Get the assets associated with this status.
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Scope a query to only include statuses that should be shown in navigation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShownInNav($query)
    {
        return $query->where('show_in_nav', true);
    }

    /**
     * Get the default statuses for the application.
     */
    public static function getDefaultStatuses(): array
    {
        return [
            [
                'id' => self::DEPLOYED,
                'name' => 'Deployed',
                'type' => 'deployed',
                'color' => '#2ecc71',
                'show_in_nav' => true,
            ],
            [
                'id' => self::READY_TO_DEPLOY,
                'name' => 'Ready to Deploy',
                'type' => 'pending',
                'color' => '#3498db',
                'show_in_nav' => true,
            ],
            [
                'id' => self::PENDING,
                'name' => 'Pending',
                'type' => 'pending',
                'color' => '#f39c12',
                'show_in_nav' => true,
            ],
            [
                'id' => self::ARCHIVED,
                'name' => 'Archived',
                'type' => 'archived',
                'color' => '#95a5a6',
                'show_in_nav' => false,
            ],
            [
                'id' => self::OUT_FOR_REPAIR,
                'name' => 'Out for Repair',
                'type' => 'maintenance',
                'color' => '#e74c3c',
                'show_in_nav' => true,
            ],
            [
                'id' => self::BROKEN,
                'name' => 'Broken',
                'type' => 'maintenance',
                'color' => '#8e44ad',
                'show_in_nav' => true,
            ],
            [
                'id' => self::LOST,
                'name' => 'Lost/Stolen',
                'type' => 'archived',
                'color' => '#2c3e50',
                'show_in_nav' => false,
            ],
        ];
    }
}
