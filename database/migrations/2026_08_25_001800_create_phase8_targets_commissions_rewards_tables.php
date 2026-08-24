<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('collection_rate_percent', 8, 4)->default(3.5000); // % of verified payment
            $table->decimal('target_bonus_percent', 8, 4)->default(1.0000); // extra % when target met
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salesman_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('territory_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->decimal('target_amount', 14, 2)->default(0);
            $table->decimal('achieved_amount', 14, 2)->default(0); // delivered order totals
            $table->decimal('collected_amount', 14, 2)->default(0); // verified payments attributed
            $table->string('status', 40)->default('open'); // open, closed
            $table->boolean('target_met')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['salesman_id', 'year', 'month']);
            $table->index(['year', 'month', 'status']);
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('salesman_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sales_target_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40)->default('collection'); // collection, target_bonus, adjustment
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('base_amount', 14, 2)->default(0);
            $table->decimal('rate_percent', 8, 4)->default(0);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->string('status', 40)->default('accrued'); // accrued, approved, paid, rejected
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['salesman_id', 'year', 'month', 'status']);
            $table->index(['type', 'status']);
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('salesman_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sales_target_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40)->default('target_hit'); // target_hit, top_performer, manual
            $table->string('title');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status', 40)->default('pending'); // pending, awarded, paid, cancelled
            $table->text('notes')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['salesman_id', 'year', 'month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('sales_targets');
        Schema::dropIfExists('commission_rules');
    }
};
