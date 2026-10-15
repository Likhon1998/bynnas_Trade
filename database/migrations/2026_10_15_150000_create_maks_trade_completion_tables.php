<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('grade', 20)->nullable()->after('notes');
            $table->decimal('rating', 2, 1)->nullable()->after('grade');
        });

        Schema::table('salesman_profiles', function (Blueprint $table) {
            $table->string('grade', 20)->nullable()->after('notes');
            $table->decimal('rating', 2, 1)->nullable()->after('grade');
        });

        Schema::create('product_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->decimal('offer_price', 14, 2);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('salesman_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('monthly_salary', 14, 2);
            $table->date('effective_from');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'is_active', 'effective_from']);
        });

        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('amount', 14, 2);
            $table->decimal('bonus', 14, 2)->default(0);
            $table->string('method', 40)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'year', 'month']);
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('body');
            $table->boolean('send_to_all')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('announcement_salesman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
        });

        Schema::table('commission_rules', function (Blueprint $table) {
            $table->string('basis', 20)->default('collection')->after('name');
            $table->foreignId('product_id')->nullable()->after('basis')->constrained()->nullOnDelete();
            $table->decimal('sales_rate_percent', 8, 4)->nullable()->after('collection_rate_percent');
            $table->decimal('product_rate_percent', 8, 4)->nullable()->after('sales_rate_percent');
        });
    }

    public function down(): void
    {
        Schema::table('commission_rules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn(['basis', 'sales_rate_percent', 'product_rate_percent']);
        });

        Schema::dropIfExists('announcement_salesman');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('salesman_salaries');
        Schema::dropIfExists('product_offers');

        Schema::table('salesman_profiles', function (Blueprint $table) {
            $table->dropColumn(['grade', 'rating']);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['grade', 'rating']);
        });
    }
};
