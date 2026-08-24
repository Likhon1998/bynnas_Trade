<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 30)->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('price_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 30)->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('owner_name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->foreignId('territory_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('price_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_salesman_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->decimal('outstanding_balance', 14, 2)->default(0);
            $table->unsignedSmallInteger('payment_terms_days')->default(21);
            $table->string('status', 30)->default('pending'); // pending, active, on_hold, rejected
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'city']);
        });

        Schema::create('shop_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();

            $table->unique(['shop_id', 'user_id']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku', 80)->unique();
            $table->string('barcode', 80)->nullable()->unique();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model')->nullable();
            $table->string('variant')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('cost_price', 14, 2)->default(0);
            $table->decimal('landed_cost', 14, 2)->default(0);
            $table->decimal('wholesale_price', 14, 2)->default(0);
            $table->decimal('dealer_price', 14, 2)->default(0);
            $table->decimal('distributor_price', 14, 2)->default(0);
            $table->decimal('retail_price', 14, 2)->default(0);
            $table->decimal('minimum_selling_price', 14, 2)->default(0);
            $table->string('warranty')->nullable();
            $table->unsignedInteger('minimum_stock')->default(0);
            $table->unsignedInteger('stock_on_hand')->default(0);
            $table->unsignedInteger('reserved_stock')->default(0);
            $table->string('status', 30)->default('active'); // active, inactive, discontinued
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category_id']);
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('price_group_id')->constrained()->cascadeOnDelete();
            $table->decimal('wholesale_price', 14, 2);
            $table->decimal('minimum_selling_price', 14, 2)->nullable();
            $table->decimal('promotional_price', 14, 2)->nullable();
            $table->timestamp('promo_starts_at')->nullable();
            $table->timestamp('promo_ends_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'price_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('products');
        Schema::dropIfExists('shop_user');
        Schema::dropIfExists('shops');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('price_groups');
        Schema::dropIfExists('territories');
    }
};
