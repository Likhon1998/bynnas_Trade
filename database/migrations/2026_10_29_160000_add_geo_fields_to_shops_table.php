<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shops')) {
            return;
        }

        Schema::table('shops', function (Blueprint $table) {
            if (! Schema::hasColumn('shops', 'district')) {
                $table->string('district', 80)->nullable()->after('city');
            }
            if (! Schema::hasColumn('shops', 'thana')) {
                $table->string('thana', 80)->nullable();
            }
            if (! Schema::hasColumn('shops', 'post_office')) {
                $table->string('post_office', 120)->nullable();
            }
            if (! Schema::hasColumn('shops', 'location')) {
                $table->string('location', 160)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shops')) {
            return;
        }

        $drop = array_values(array_filter(
            ['district', 'thana', 'post_office', 'location'],
            fn ($column) => Schema::hasColumn('shops', $column)
        ));

        if ($drop === []) {
            return;
        }

        Schema::table('shops', function (Blueprint $table) use ($drop) {
            $table->dropColumn($drop);
        });
    }
};
