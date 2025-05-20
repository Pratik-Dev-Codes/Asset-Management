<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'quantity',
        'unit_price',
        'total_value',
        'category_id',
        'location_id',
        'supplier_id',
        'reorder_level',
        'status',
        'notes',
        'purchase_date',
        'expiry_date',
        'serial_number',
        'barcode',
        'image_path',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
        'reorder_level' => 'integer',
        'purchase_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_low_stock',
        'formatted_unit_price',
        'formatted_total_value',
    ];

    /**
     * Get the category that owns the inventory.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    /**
     * Get the location that owns the inventory.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Get the supplier that owns the inventory.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the user who created the inventory.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the inventory.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the transactions for the inventory.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Check if the inventory is low on stock.
     *
     * @return bool
     */
    public function getIsLowStockAttribute(): bool
    {
        if (is_null($this->reorder_level)) {
            return false;
        }

        return $this->quantity <= $this->reorder_level;
    }

    /**
     * Get the formatted unit price.
     *
     * @return string
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2);
    }

    /**
     * Get the formatted total value.
     *
     * @return string
     */
    public function getFormattedTotalValueAttribute(): string
    {
        return number_format($this->total_value, 2);
    }

    /**
     * Scope a query to only include low stock items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level')
                    ->whereNotNull('reorder_level');
    }

    /**
     * Scope a query to only include items with the given status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include items in the given category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $categoryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to only include items at the given location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $locationId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAtLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Update the inventory quantity and total value.
     *
     * @param  int  $quantity
     * @param  float|null  $unitPrice
     * @param  string  $action
     * @return void
     */
    public function updateQuantity($quantity, $unitPrice = null, $action = 'add')
    {
        if (!is_null($unitPrice)) {
            $this->unit_price = $unitPrice;
        }

        if ($action === 'add') {
            $this->quantity += $quantity;
        } else {
            $this->quantity -= $quantity;
        }

        $this->total_value = $this->quantity * $this->unit_price;
        $this->save();
    }
}
