<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contact_messages')) {
            Schema::create('contact_messages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('subject', 180)->nullable();
                $table->text('message');
                $table->string('status', 40)->default('new'); // new, read, closed
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }

        if (Schema::hasTable('partner_inquiries') && ! Schema::hasColumn('partner_inquiries', 'contact_name')) {
            Schema::table('partner_inquiries', function (Blueprint $table) {
                if (Schema::hasColumn('partner_inquiries', 'owner_name')) {
                    $table->renameColumn('owner_name', 'contact_name');
                }
                if (Schema::hasColumn('partner_inquiries', 'shop_type')) {
                    $table->renameColumn('shop_type', 'business_type');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');

        if (Schema::hasTable('partner_inquiries') && Schema::hasColumn('partner_inquiries', 'contact_name')) {
            Schema::table('partner_inquiries', function (Blueprint $table) {
                $table->renameColumn('contact_name', 'owner_name');
                if (Schema::hasColumn('partner_inquiries', 'business_type')) {
                    $table->renameColumn('business_type', 'shop_type');
                }
            });
        }
    }
};
