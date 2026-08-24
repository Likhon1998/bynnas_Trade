<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
            $table->string('portal', 30)->default('admin')->after('is_active'); // admin|shop|salesman
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->foreignId('created_by')->nullable()->after('last_login_at')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['phone', 'is_active', 'portal', 'last_login_at']);
            $table->dropSoftDeletes();
        });
    }
};
