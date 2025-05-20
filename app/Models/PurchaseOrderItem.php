<?php

namespace App\Models;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReceipt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'item_type',
        'item_id',
        'quantity',
        'received_quantity',
        'unit_price',
        'total',
        'notes',
    ];

    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'quantity' => 'integer',
        'received_quantity' => 'integer',
    ];

    public static function getItemTypes()
    {
        return [
            'asset' => 'Asset',
            'accessory' => 'Accessory',
            'component' => 'Component',
            'consumable' => 'Consumable',
            'license' => 'License',
            'other' => 'Other',
        ];
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function item()
    {
        return $this->morphTo();
    }

    public function getItemNameAttribute()
    {
        if ($this->item) {
            return $this->item->name ?? $this->item->title ?? 'N/A';
        }
        
        return 'Deleted Item';
    }

    public function getItemModelNumberAttribute()
    {
        if ($this->item && method_exists($this->item, 'model_number')) {
            return $this->item->model_number;
        }
        
        return 'N/A';
    }

    public function getItemCategoryAttribute()
    {
        if ($this->item && method_exists($this->item, 'category')) {
            return $this->item->category->name ?? 'N/A';
        }
        
        return 'N/A';
    }

    public function getItemManufacturerAttribute()
    {
        if ($this->item && method_exists($this->item, 'manufacturer')) {
            return $this->item->manufacturer->name ?? 'N/A';
        }
        
        return 'N/A';
    }

    public function getRemainingQuantityAttribute()
    {
        return max(0, $this->quantity - $this->received_quantity);
    }

    public function isFullyReceived()
    {
        return $this->received_quantity >= $this->quantity;
    }

    public function receive($quantity = null, $notes = null)
    {
        if ($quantity === null) {
            $quantity = $this->remaining_quantity;
        } else {
            $quantity = min($quantity, $this->remaining_quantity);
        }

        if ($quantity <= 0) {
            return false;
        }

        $this->increment('received_quantity', $quantity);
        
        // Create a receipt record
        PurchaseOrderReceipt::create([
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_order_item_id' => $this->id,
            'quantity' => $quantity,
            'notes' => $notes,
            'received_by' => auth()->id(),
        ]);

        // Update the purchase order status if needed
        $this->purchaseOrder->updateStatus();

        return true;
    }
}
