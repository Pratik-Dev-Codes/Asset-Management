<?php

namespace App\Models;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderReceipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'purchase_order_item_id',
        'quantity',
        'notes',
        'received_by',
        'received_at',
    ];

    protected $dates = [
        'received_at',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function item()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getFormattedReceivedAtAttribute()
    {
        return $this->received_at ? $this->received_at->format('M j, Y g:i A') : 'N/A';
    }

    public function getReceiverNameAttribute()
    {
        return $this->receiver ? $this->receiver->present()->fullName() : 'System';
    }

    public function getItemNameAttribute()
    {
        return $this->item ? $this->item->item_name : 'Deleted Item';
    }
}
