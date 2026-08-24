<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clean partial failed run (index name too long on first attempt).
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('order_fulfilments');
        Schema::dropIfExists('inventory_ledgers');
        Schema::dropIfExists('inventory_ledger_entries');
        Schema::dropIfExists('warehouse_stocks');

        if (Schema::hasColumn('orders', 'fulfilment_warehouse_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('fulfilment_warehouse_id');
            });
        }

        if (Schema::hasColumn('orders', 'warehouse_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('warehouse_id');
            });
        }

        Schema::dropIfExists('warehouses');

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('qty_on_hand')->default(0);
            $table->unsignedInteger('qty_reserved')->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
        });

        Schema::create('inventory_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->integer('qty_delta');
            $table->unsignedInteger('qty_on_hand_after');
            $table->unsignedInteger('qty_reserved_after');
            $table->string('reference', 80)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_id', 'product_id', 'created_at'], 'inv_ledger_wh_prod_created_idx');
            $table->index(['type', 'created_at'], 'inv_ledger_type_created_idx');
        });

        Schema::create('order_fulfilments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('status', 40)->default('awaiting_pick');
            $table->foreignId('picker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('packer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispatcher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('picking_started_at')->nullable();
            $table->timestamp('picked_at')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('warehouse_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fulfilment_id')->constrained('order_fulfilments')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('out_for_delivery');
            $table->string('tracking_ref', 80)->nullable();
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'dispatched_at'], 'deliveries_status_dispatched_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('shop_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });

        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('order_fulfilments');
        Schema::dropIfExists('inventory_ledgers');
        Schema::dropIfExists('warehouse_stocks');
        Schema::dropIfExists('warehouses');
    }
};
