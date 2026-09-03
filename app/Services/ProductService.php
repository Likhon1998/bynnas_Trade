<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function create(array $data, ?User $actor = null, array $groupPrices = [], ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($data, $actor, $groupPrices, $image) {
            if ($image) {
                $data['image_path'] = $image->store('products', 'public');
            }

            $product = Product::query()->create([
                ...collect($data)->only([
                    'name', 'sku', 'barcode', 'category_id', 'brand_id', 'model', 'variant',
                    'description', 'image_path', 'cost_price', 'landed_cost', 'wholesale_price',
                    'dealer_price', 'distributor_price', 'retail_price', 'minimum_selling_price',
                    'warranty', 'minimum_stock', 'stock_on_hand', 'status', 'is_published',
                ])->all(),
                'created_by' => $actor?->id,
            ]);

            $this->syncGroupPrices($product, $groupPrices);

            $this->auditLogger->log(
                'products',
                'created',
                "Created product {$product->sku}",
                $product,
                null,
                $product->only(['sku', 'name', 'wholesale_price', 'stock_on_hand', 'image_path']),
                $actor,
            );

            return $product->fresh(['category', 'brand', 'prices']);
        });
    }

    public function update(Product $product, array $data, ?User $actor = null, ?array $groupPrices = null, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($product, $data, $actor, $groupPrices, $image) {
            $old = $product->only(['name', 'wholesale_price', 'status', 'stock_on_hand', 'image_path']);

            if ($image) {
                if ($product->image_path && ! str_starts_with($product->image_path, 'http')) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $data['image_path'] = $image->store('products', 'public');
            }

            $product->update(collect($data)->only([
                'name', 'sku', 'barcode', 'category_id', 'brand_id', 'model', 'variant',
                'description', 'image_path', 'cost_price', 'landed_cost', 'wholesale_price',
                'dealer_price', 'distributor_price', 'retail_price', 'minimum_selling_price',
                'warranty', 'minimum_stock', 'stock_on_hand', 'status', 'is_published',
            ])->all());

            if ($groupPrices !== null) {
                $this->syncGroupPrices($product, $groupPrices);
            }

            $this->auditLogger->log(
                'products',
                'updated',
                "Updated product {$product->sku}",
                $product,
                $old,
                $product->fresh()->only(['name', 'wholesale_price', 'status', 'stock_on_hand', 'image_path']),
                $actor,
            );

            return $product->fresh(['category', 'brand', 'prices']);
        });
    }

    public function syncGroupPrices(Product $product, array $groupPrices): void
    {
        foreach ($groupPrices as $priceGroupId => $wholesale) {
            if ($wholesale === null || $wholesale === '') {
                ProductPrice::query()
                    ->where('product_id', $product->id)
                    ->where('price_group_id', $priceGroupId)
                    ->delete();

                continue;
            }

            ProductPrice::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'price_group_id' => $priceGroupId,
                ],
                [
                    'wholesale_price' => $wholesale,
                    'minimum_selling_price' => $product->minimum_selling_price,
                ],
            );
        }
    }
}
