<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'product_id', 'quantity', 'received_quantity',
        'unit_cost_foreign', 'unit_cost_bdt', 'line_total_bdt',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost_foreign' => 'decimal:2',
            'unit_cost_bdt' => 'decimal:2',
            'line_total_bdt' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function remainingQuantity(): int
    {
        return max(0, (int) $this->quantity - (int) $this->received_quantity);
    }
}
