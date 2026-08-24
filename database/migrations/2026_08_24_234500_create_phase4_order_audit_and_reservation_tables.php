<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('audited_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('audited_at')->nullable()->after('audited_by');
            $table->timestamp('stock_reserved_at')->nullable()->after('audited_at');
            $table->text('audit_notes')->nullable()->after('stock_reserved_at');
            $table->text('rejection_reason')->nullable()->after('audit_notes');
            $table->decimal('credit_available_at_audit', 14, 2)->nullable()->after('rejection_reason');
            $table->boolean('credit_override')->default(false)->after('credit_available_at_audit');
            $table->boolean('stock_reserved')->default(false)->after('credit_override');
            $table->foreignId('cancelled_by')->nullable()->after('stock_reserved')->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('reserved_quantity')->default(0)->after('quantity');
            $table->unsignedInteger('available_at_audit')->nullable()->after('reserved_quantity');
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->string('event', 60);
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['reserved_quantity', 'available_at_audit']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('audited_by');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'audited_at', 'stock_reserved_at', 'audit_notes', 'rejection_reason',
                'credit_available_at_audit', 'credit_override', 'stock_reserved',
                'cancelled_at', 'cancellation_reason',
            ]);
        });
    }
};
