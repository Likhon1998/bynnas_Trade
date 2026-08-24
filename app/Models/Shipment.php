<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_BOOKED = 'booked';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_ARRIVED = 'arrived';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'number', 'supplier_id', 'purchase_id', 'warehouse_id', 'origin', 'carrier',
        'tracking_ref', 'container_no', 'status', 'shipped_at', 'eta_at', 'arrived_at',
        'received_at', 'goods_value_bdt', 'freight_cost', 'customs_duty', 'insurance_cost',
        'other_cost', 'total_landed_cost', 'costs_allocated', 'notes', 'created_by', 'received_by',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'date',
            'eta_at' => 'date',
            'arrived_at' => 'date',
            'received_at' => 'datetime',
            'goods_value_bdt' => 'decimal:2',
            'freight_cost' => 'decimal:2',
            'customs_duty' => 'decimal:2',
            'insurance_cost' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'total_landed_cost' => 'decimal:2',
            'costs_allocated' => 'boolean',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function extraCostsTotal(): float
    {
        return (float) $this->freight_cost
            + (float) $this->customs_duty
            + (float) $this->insurance_cost
            + (float) $this->other_cost;
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_BOOKED => 'Booked',
            self::STATUS_IN_TRANSIT => 'In transit',
            self::STATUS_ARRIVED => 'Arrived',
            self::STATUS_RECEIVED => 'Received at warehouse',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function canReceive(): bool
    {
        return in_array($this->status, [self::STATUS_IN_TRANSIT, self::STATUS_ARRIVED, self::STATUS_BOOKED], true);
    }
}
