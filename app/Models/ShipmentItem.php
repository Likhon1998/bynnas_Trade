<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItem extends Model
{
    protected $fillable = [
        'shipment_id', 'product_id', 'purchase_item_id', 'quantity',
        'unit_cost_bdt', 'line_goods_value', 'allocated_extra_cost',
        'line_landed_total', 'unit_landed_cost',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost_bdt' => 'decimal:2',
            'line_goods_value' => 'decimal:2',
            'allocated_extra_cost' => 'decimal:2',
            'line_landed_total' => 'decimal:2',
            'unit_landed_cost' => 'decimal:2',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }
}
