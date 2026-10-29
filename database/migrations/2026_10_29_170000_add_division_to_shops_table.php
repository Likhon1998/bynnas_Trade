<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shops') || Schema::hasColumn('shops', 'division')) {
            return;
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->string('division', 80)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shops') || ! Schema::hasColumn('shops', 'division')) {
            return;
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('division');
        });
    }
};
