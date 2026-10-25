<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'is_primary', 'sort_order']);
        });

        Product::query()
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->orderBy('id')
            ->each(function (Product $product) {
                $product->images()->create([
                    'path' => $product->image_path,
                    'sort_order' => 0,
                    'is_primary' => true,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
