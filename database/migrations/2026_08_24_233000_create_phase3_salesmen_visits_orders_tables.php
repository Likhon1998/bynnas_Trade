<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salesman_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_code', 40)->unique();
            $table->foreignId('territory_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('monthly_target', 14, 2)->default(0);
            $table->date('joined_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shop_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salesman_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();
            $table->string('purpose', 80)->nullable();
            $table->string('outcome', 40)->default('in_progress'); // in_progress, order_taken, no_order, closed, follow_up
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('order_id')->nullable(); // filled after order created (FK added below)
            $table->timestamps();

            $table->index(['salesman_id', 'checked_in_at']);
            $table->index(['shop_id', 'checked_in_at']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salesman_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('shop_visits')->nullOnDelete();
            $table->string('source', 30)->default('salesman'); // salesman, shop_portal, admin
            $table->string('status', 40)->default('pending_audit');
            // draft, pending_audit, approved, rejected, cancelled, fulfilled (later phases)
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->unsignedInteger('item_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'submitted_at']);
            $table->index(['shop_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('product_sku', 80);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 14, 2);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
        });

        Schema::table('shop_visits', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shop_visits', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('shop_visits');
        Schema::dropIfExists('salesman_profiles');
    }
};
