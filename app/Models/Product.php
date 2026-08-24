<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_DISCONTINUED = 'discontinued';

    protected $fillable = [
        'name', 'sku', 'barcode', 'category_id', 'brand_id', 'model', 'variant',
        'description', 'image_path', 'cost_price', 'landed_cost', 'wholesale_price',
        'dealer_price', 'distributor_price', 'retail_price', 'minimum_selling_price',
        'warranty', 'minimum_stock', 'stock_on_hand', 'reserved_stock',
        'status', 'is_published', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'landed_cost' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'dealer_price' => 'decimal:2',
            'distributor_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'minimum_selling_price' => 'decimal:2',
            'is_published' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function availableStock(): int
    {
        return max(0, (int) $this->stock_on_hand - (int) $this->reserved_stock);
    }

    public function priceForGroup(?PriceGroup $group = null): float
    {
        if ($group) {
            $override = $this->prices->firstWhere('price_group_id', $group->id)
                ?? $this->prices()->where('price_group_id', $group->id)->first();

            if ($override) {
                if ($override->promotional_price
                    && (! $override->promo_starts_at || $override->promo_starts_at->isPast())
                    && (! $override->promo_ends_at || $override->promo_ends_at->isFuture())
                ) {
                    return (float) $override->promotional_price;
                }

                return (float) $override->wholesale_price;
            }
        }

        return (float) $this->wholesale_price;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_DISCONTINUED => 'Discontinued',
            default => $this->availableStock() <= 0
                ? 'Out of Stock'
                : ($this->availableStock() <= $this->minimum_stock ? 'Low Stock' : 'In Stock'),
        };
    }
}
