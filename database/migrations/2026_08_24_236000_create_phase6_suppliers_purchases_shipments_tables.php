<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('country', 80)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('payment_terms', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('status', 40)->default('draft'); // draft, ordered, partial, received, cancelled
            $table->string('currency', 10)->default('CNY');
            $table->decimal('exchange_rate', 12, 4)->default(1); // to BDT
            $table->decimal('subtotal_foreign', 14, 2)->default(0);
            $table->decimal('subtotal_bdt', 14, 2)->default(0);
            $table->date('ordered_at')->nullable();
            $table->date('expected_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('received_quantity')->default(0);
            $table->decimal('unit_cost_foreign', 14, 2)->default(0);
            $table->decimal('unit_cost_bdt', 14, 2)->default(0);
            $table->decimal('line_total_bdt', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin', 120)->nullable(); // e.g. Shenzhen, China
            $table->string('carrier', 120)->nullable();
            $table->string('tracking_ref', 120)->nullable();
            $table->string('container_no', 80)->nullable();
            $table->string('status', 40)->default('draft');
            // draft, booked, in_transit, arrived, received, cancelled
            $table->date('shipped_at')->nullable();
            $table->date('eta_at')->nullable();
            $table->date('arrived_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->decimal('goods_value_bdt', 14, 2)->default(0);
            $table->decimal('freight_cost', 14, 2)->default(0);
            $table->decimal('customs_duty', 14, 2)->default(0);
            $table->decimal('insurance_cost', 14, 2)->default(0);
            $table->decimal('other_cost', 14, 2)->default(0);
            $table->decimal('total_landed_cost', 14, 2)->default(0);
            $table->boolean('costs_allocated')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'eta_at'], 'shipments_status_eta_idx');
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_item_id')->nullable()->constrained('purchase_items')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_cost_bdt', 14, 2)->default(0);
            $table->decimal('line_goods_value', 14, 2)->default(0);
            $table->decimal('allocated_extra_cost', 14, 2)->default(0);
            $table->decimal('line_landed_total', 14, 2)->default(0);
            $table->decimal('unit_landed_cost', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
    }
};
