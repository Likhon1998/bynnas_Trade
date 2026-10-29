<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shop_visits') && ! Schema::hasColumn('shop_visits', 'photo_path')) {
            Schema::table('shop_visits', function (Blueprint $table) {
                $table->string('photo_path', 255)->nullable()->after('notes');
            });
        }

        if (Schema::hasTable('shops') && ! Schema::hasColumn('shops', 'photo_path')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->string('photo_path', 255)->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shop_visits') && Schema::hasColumn('shop_visits', 'photo_path')) {
            Schema::table('shop_visits', function (Blueprint $table) {
                $table->dropColumn('photo_path');
            });
        }

        if (Schema::hasTable('shops') && Schema::hasColumn('shops', 'photo_path')) {
            Schema::table('shops', function (Blueprint $table) {
                $table->dropColumn('photo_path');
            });
        }
    }
};
