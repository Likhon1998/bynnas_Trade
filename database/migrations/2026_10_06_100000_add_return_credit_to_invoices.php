<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('credited_amount', 14, 2)->default(0)->after('paid_amount');
        });

        Schema::table('returns', function (Blueprint $table) {
            $table->decimal('applied_to_invoice', 14, 2)->default(0)->after('credit_issued');
        });
    }

    public function down(): void
    {
        Schema::table('returns', fn (Blueprint $table) => $table->dropColumn('applied_to_invoice'));
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('credited_amount'));
    }
};
