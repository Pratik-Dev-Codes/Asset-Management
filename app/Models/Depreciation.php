<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property-read \App\Models\Asset|null $asset
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation query()
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Depreciation withoutTrashed()
 * @mixin \Eloquent
 */
class Depreciation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_id',
        'purchase_date',
        'purchase_cost',
        'current_value',
        'depreciation',
        'months',
        'method',
        'calculated_at',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'current_value' => 'decimal:2',
        'depreciation' => 'decimal:2',
        'months' => 'integer',
        'calculated_at' => 'datetime',
    ];

    protected $dates = [
        'purchase_date',
        'calculated_at',
        'deleted_at',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function calculateDepreciation()
    {
        if ($this->method === 'straight_line') {
            return $this->calculateStraightLineDepreciation();
        } elseif ($this->method === 'double_declining') {
            return $this->calculateDoubleDecliningDepreciation();
        }
        
        return 0;
    }

    protected function calculateStraightLineDepreciation()
    {
        if ($this->months <= 0) {
            return 0;
        }
        
        $depreciationPerMonth = $this->purchase_cost / $this->months;
        $monthsElapsed = now()->diffInMonths($this->purchase_date);
        $totalDepreciation = min($depreciationPerMonth * $monthsElapsed, $this->purchase_cost);
        
        return max(0, $totalDepreciation);
    }

    protected function calculateDoubleDecliningDepreciation()
    {
        if ($this->months <= 0) {
            return 0;
        }
        
        $rate = 2 / $this->months;
        $bookValue = $this->purchase_cost;
        $totalDepreciation = 0;
        $monthsElapsed = now()->diffInMonths($this->purchase_date);
        
        for ($i = 0; $i < $monthsElapsed; $i++) {
            $depreciation = $bookValue * $rate;
            $totalDepreciation += $depreciation;
            $bookValue -= $depreciation;
            
            if ($bookValue <= 0) {
                $totalDepreciation = $this->purchase_cost;
                break;
            }
        }
        
        return min($totalDepreciation, $this->purchase_cost);
    }
}
