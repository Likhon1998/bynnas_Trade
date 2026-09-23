<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('advance_required')->default(false)->after('credit_override');
            $table->decimal('advance_amount', 14, 2)->nullable()->after('advance_required');
            $table->foreignId('advance_invoice_id')->nullable()->after('advance_amount')
                ->constrained('invoices')->nullOnDelete();
            $table->timestamp('advance_requested_at')->nullable()->after('advance_invoice_id');
            $table->timestamp('advance_paid_at')->nullable()->after('advance_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('advance_invoice_id');
            $table->dropColumn([
                'advance_required',
                'advance_amount',
                'advance_requested_at',
                'advance_paid_at',
            ]);
        });
    }
};
