<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $credit = Permission::findOrCreate('shops.manage_credit', 'web');
        $edit = Permission::findOrCreate('shops.edit', 'web');

        Role::query()->where('guard_name', 'web')->whereIn('name', ['Super Admin', 'Admin'])->get()
            ->each(fn (Role $role) => $role->givePermissionTo($credit));
        Role::query()->where('guard_name', 'web')->where('name', 'Finance Manager')->get()
            ->each(fn (Role $role) => $role->givePermissionTo([$credit, $edit]));
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()->where('name', 'shops.manage_credit')->where('guard_name', 'web')->delete();
    }
};
