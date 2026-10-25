<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'product_sku',
        'quantity', 'reserved_quantity', 'available_at_audit',
        'original_unit_price', 'unit_price', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'original_unit_price' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function originalUnitPrice(): float
    {
        return (float) ($this->original_unit_price ?? $this->unit_price);
    }

    public function wasPriceChanged(): bool
    {
        return abs($this->originalUnitPrice() - (float) $this->unit_price) >= 0.005;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
