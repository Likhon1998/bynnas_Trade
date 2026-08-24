<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id', 'price_group_id', 'wholesale_price', 'minimum_selling_price',
        'promotional_price', 'promo_starts_at', 'promo_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'wholesale_price' => 'decimal:2',
            'minimum_selling_price' => 'decimal:2',
            'promotional_price' => 'decimal:2',
            'promo_starts_at' => 'datetime',
            'promo_ends_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceGroup(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class);
    }
}
