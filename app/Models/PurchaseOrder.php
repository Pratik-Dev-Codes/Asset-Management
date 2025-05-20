<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    const STATUS_DRAFT = 'draft';

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_APPROVED = 'approved';

    const STATUS_RECEIVED = 'received';

    const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_number',
        'supplier_id',
        'user_id',
        'order_date',
        'expected_delivery_date',
        'delivery_date',
        'status',
        'notes',
        'terms',
        'shipping_terms',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',
        'shipping_phone',
        'tracking_number',
        'tracking_url',
        'subtotal',
        'tax',
        'shipping',
        'total',
        'currency',
        'exchange_rate',
        'approved_by',
        'approved_at',
        'received_by',
        'received_at',
    ];

    protected $dates = [
        'order_date',
        'expected_delivery_date',
        'delivery_date',
        'approved_at',
        'received_at',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    public static function getStatuses()
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_PARTIALLY_RECEIVED => 'Partially Received',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'purchase_order_id');
    }

    public function accessories()
    {
        return $this->hasMany(Accessory::class, 'purchase_order_id');
    }

    public function components()
    {
        return $this->hasMany(Component::class, 'purchase_order_id');
    }

    public function consumables()
    {
        return $this->hasMany(Consumable::class, 'purchase_order_id');
    }

    public function licenses()
    {
        return $this->hasMany(License::class, 'purchase_order_id');
    }

    public function getStatusBadgeAttribute()
    {
        $statuses = [
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_PENDING_APPROVAL => 'info',
            self::STATUS_APPROVED => 'primary',
            self::STATUS_RECEIVED => 'success',
            self::STATUS_PARTIALLY_RECEIVED => 'warning',
            self::STATUS_CANCELLED => 'danger',
            self::STATUS_REJECTED => 'danger',
        ];

        $statusLabels = self::getStatuses();

        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            $statuses[$this->status] ?? 'secondary',
            e($statusLabels[$this->status] ?? ucfirst($this->status))
        );
    }

    public function calculateTotals()
    {
        $subtotal = $this->items->sum('total');
        $tax = $subtotal * ($this->tax_rate / 100);
        $total = $subtotal + $tax + $this->shipping;

        $this->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);
    }

    public function getReceivedItemsCount()
    {
        return $this->items->sum('received_quantity');
    }

    public function getOrderedItemsCount()
    {
        return $this->items->sum('quantity');
    }

    public function isFullyReceived()
    {
        return $this->status === self::STATUS_RECEIVED ||
               ($this->status === self::STATUS_PARTIALLY_RECEIVED &&
                $this->getReceivedItemsCount() >= $this->getOrderedItemsCount());
    }

    /**
     * Update the status of the purchase order based on received items.
     *
     * @return void
     */
    public function updateStatus()
    {
        $ordered = $this->getOrderedItemsCount();
        $received = $this->getReceivedItemsCount();

        if ($ordered === 0) {
            $status = self::STATUS_DRAFT;
        } elseif ($received === 0) {
            $status = self::STATUS_APPROVED;
        } elseif ($received >= $ordered) {
            $status = self::STATUS_RECEIVED;
            $this->received_at = now();
            $this->received_by = auth()->id();
        } else {
            $status = self::STATUS_PARTIALLY_RECEIVED;
        }

        $this->status = $status;
        $this->save();
    }
}
