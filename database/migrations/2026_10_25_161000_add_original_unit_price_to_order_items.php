<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('original_unit_price', 14, 2)->nullable()->after('quantity');
        });

        DB::table('order_items')->whereNull('original_unit_price')->update([
            'original_unit_price' => DB::raw('unit_price'),
        ]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('original_unit_price');
        });
    }
};
