<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderFulfilment extends Model
{
    public const STATUS_AWAITING_PICK = 'awaiting_pick';

    public const STATUS_PICKING = 'picking';

    public const STATUS_PICKED = 'picked';

    public const STATUS_PACKED = 'packed';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'order_id', 'warehouse_id', 'status', 'picker_id', 'packer_id', 'dispatcher_id',
        'picking_started_at', 'picked_at', 'packed_at', 'dispatched_at', 'delivered_at',
        'warehouse_notes',
    ];

    protected function casts(): array
    {
        return [
            'picking_started_at' => 'datetime',
            'picked_at' => 'datetime',
            'packed_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function picker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picker_id');
    }

    public function packer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packer_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class, 'fulfilment_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_AWAITING_PICK => 'Awaiting pick',
            self::STATUS_PICKING => 'Picking',
            self::STATUS_PICKED => 'Picked',
            self::STATUS_PACKED => 'Packed',
            self::STATUS_DISPATCHED => 'Dispatched',
            self::STATUS_DELIVERED => 'Delivered',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
