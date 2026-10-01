<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_inquiries', function (Blueprint $table) {
            $table->string('source', 20)->default('website')->after('status');
            $table->foreignId('submitted_by')->nullable()->after('source')->constrained('users')->nullOnDelete();
            $table->index(['submitted_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('partner_inquiries', function (Blueprint $table) {
            $table->dropIndex(['submitted_by', 'created_at']);
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn('source');
        });
    }
};
