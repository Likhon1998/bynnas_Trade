<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_inquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('partner_inquiries', 'shop_id')) {
                $table->foreignId('shop_id')->nullable()->after('reviewed_by')->constrained('shops')->nullOnDelete();
            }
            if (! Schema::hasColumn('partner_inquiries', 'portal_email')) {
                $table->string('portal_email')->nullable()->after('shop_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('partner_inquiries', function (Blueprint $table) {
            if (Schema::hasColumn('partner_inquiries', 'shop_id')) {
                $table->dropConstrainedForeignId('shop_id');
            }
            if (Schema::hasColumn('partner_inquiries', 'portal_email')) {
                $table->dropColumn('portal_email');
            }
        });
    }
};
