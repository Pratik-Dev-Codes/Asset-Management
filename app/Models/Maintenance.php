<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\Asset|null $asset
 * @property-read \App\Models\User|null $performedBy
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Maintenance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Maintenance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Maintenance onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Maintenance query()
 * @method static \Illuminate\Database\Eloquent\Builder|Maintenance withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Maintenance withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Maintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'asset_id',
        'start_date',
        'completion_date',
        'cost',
        'notes',
        'status',
        'performed_by',
        'approved_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'completion_date' => 'date',
        'cost' => 'decimal:2',
    ];

    protected $dates = [
        'start_date',
        'completion_date',
        'deleted_at',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
