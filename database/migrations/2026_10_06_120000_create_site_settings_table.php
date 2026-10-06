<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('section', 40)->unique();
            $table->json('content');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $view = Permission::findOrCreate('website.view', 'web');
        $manage = Permission::findOrCreate('website.manage', 'web');

        Role::query()->where('guard_name', 'web')->whereIn('name', ['Super Admin', 'Admin'])->get()
            ->each(fn (Role $role) => $role->givePermissionTo([$view, $manage]));
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::query()->whereIn('name', ['website.view', 'website.manage'])->where('guard_name', 'web')->delete();
    }
};
