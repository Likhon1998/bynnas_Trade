<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = [
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
        ];

        foreach ($names as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::query()->where('guard_name', 'web')->whereIn('name', [
            'Super Admin', 'Admin', 'Sales Manager',
        ])->get()->each(fn (Role $role) => $role->givePermissionTo($names));
    }

    public function down(): void
    {
        // Keep permissions; they are part of the live catalog.
    }
};
