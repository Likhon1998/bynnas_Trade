<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAccessScope;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function syncPermissionCatalog(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionCatalog::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }

    public function ensureSystemRoles(): void
    {
        $this->syncPermissionCatalog();

        $definitions = [
            'Super Admin' => PermissionCatalog::all(),
            'Admin' => array_values(array_filter(
                PermissionCatalog::all(),
                fn (string $p) => ! str_starts_with($p, 'roles.delete') && ! str_starts_with($p, 'permissions.')
            )),
            'Sales Manager' => [
                'shops.view', 'shops.create', 'shops.edit', 'shops.approve', 'shops.manage_credentials',
                'salesmen.view', 'salesmen.create', 'salesmen.edit',
                'visits.view', 'visits.create', 'visits.edit',
                'orders.view', 'orders.create', 'orders.edit', 'orders.approve', 'orders.reject', 'orders.cancel', 'orders.export',
                'products.view', 'products.create', 'products.edit', 'categories.view', 'categories.create',
                'price_groups.view', 'reports.view', 'analytics.view', 'targets.view', 'targets.manage', 'commissions.view', 'rewards.view',
            ],
            'Salesman' => [
                'shops.view', 'visits.view', 'visits.create', 'visits.edit',
                'orders.view', 'orders.create', 'products.view',
                'targets.view', 'commissions.view', 'rewards.view', 'deliveries.view',
            ],
            'Warehouse Manager' => [
                'warehouses.view', 'warehouses.create', 'warehouses.edit',
                'inventory.view', 'inventory.receive', 'inventory.adjust', 'inventory.transfer',
                'fulfilment.view', 'fulfilment.pick', 'fulfilment.pack', 'fulfilment.dispatch',
                'shipments.view', 'shipments.create', 'shipments.edit', 'shipments.receive',
                'suppliers.view', 'purchases.view', 'purchases.create',
                'orders.view', 'products.view', 'products.manage_stock',
                'deliveries.view', 'deliveries.assign', 'deliveries.update_status',
            ],
            'Warehouse Staff' => [
                'warehouses.view', 'inventory.view', 'inventory.receive',
                'fulfilment.view', 'fulfilment.pick', 'fulfilment.pack',
                'orders.view', 'products.view', 'shipments.view', 'shipments.receive', 'deliveries.view',
            ],
            'Finance Manager' => [
                'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.export',
                'payments.view', 'payments.create', 'payments.verify',
                'orders.view', 'shops.view', 'returns.view', 'returns.approve',
                'reports.view', 'analytics.view', 'commissions.view', 'commissions.approve', 'commissions.manage',
                'targets.view', 'targets.manage', 'rewards.view', 'rewards.manage', 'rewards.approve',
            ],
            'Delivery Manager' => [
                'deliveries.view', 'deliveries.assign', 'deliveries.update_status',
                'fulfilment.view', 'fulfilment.dispatch',
                'orders.view', 'shops.view',
            ],
            'Delivery Staff' => [
                'deliveries.view', 'deliveries.update_status', 'orders.view', 'fulfilment.view',
            ],
            'Shop Owner' => [
                'orders.view', 'orders.create', 'products.view',
                'invoices.view', 'payments.view', 'returns.view', 'returns.create',
            ],
            'Accountant' => [
                'invoices.view', 'invoices.export', 'payments.view', 'payments.verify',
                'reports.view', 'analytics.view', 'shops.view', 'orders.view',
            ],
        ];

        foreach ($definitions as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }
    }

    public function createUser(array $data, array $roleNames = [], ?array $scope = null, ?User $actor = null): User
    {
        return DB::transaction(function () use ($data, $roleNames, $scope, $actor) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_active' => $data['is_active'] ?? true,
                'portal' => $data['portal'] ?? User::PORTAL_ADMIN,
                'created_by' => $actor?->id,
            ]);

            if ($roleNames !== []) {
                $user->syncRoles($roleNames);
            }

            if ($scope) {
                $this->syncUserScope($user, $scope);
            }

            $this->auditLogger->log(
                'users',
                'created',
                "Created user {$user->email}",
                $user,
                null,
                $user->only(['name', 'email', 'phone', 'portal', 'is_active']),
                $actor,
            );

            return $user;
        });
    }

    public function updateUser(User $user, array $data, ?array $roleNames = null, ?array $scope = null, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user, $data, $roleNames, $scope, $actor) {
            $old = $user->only(['name', 'email', 'phone', 'portal', 'is_active']);

            $payload = collect($data)->only(['name', 'email', 'phone', 'portal', 'is_active'])->all();

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $user->update($payload);

            if ($roleNames !== null) {
                $user->syncRoles($roleNames);
            }

            if ($scope !== null) {
                $this->syncUserScope($user, $scope);
            }

            $this->auditLogger->log(
                'users',
                'updated',
                "Updated user {$user->email}",
                $user,
                $old,
                $user->fresh()->only(['name', 'email', 'phone', 'portal', 'is_active']),
                $actor,
            );

            return $user->fresh();
        });
    }

    public function resetPassword(User $user, string $password, ?User $actor = null): void
    {
        $user->update(['password' => $password]);

        $this->auditLogger->log(
            'users',
            'password_reset',
            "Reset credentials for {$user->email}",
            $user,
            null,
            null,
            $actor,
        );
    }

    public function syncUserScope(User $user, array $scope): void
    {
        $user->accessScopes()->delete();

        $type = $scope['scope_type'] ?? UserAccessScope::TYPE_GLOBAL;

        $user->accessScopes()->create([
            'scope_type' => $type,
            'scope_id' => $type === UserAccessScope::TYPE_GLOBAL ? null : ($scope['scope_id'] ?? null),
            'label' => $scope['label'] ?? null,
        ]);
    }

    public function createRole(string $name, array $permissions = [], ?User $actor = null): Role
    {
        return DB::transaction(function () use ($name, $permissions, $actor) {
            $role = Role::create(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);

            $this->auditLogger->log(
                'roles',
                'created',
                "Created role {$name}",
                null,
                null,
                ['name' => $name, 'permissions' => $permissions],
                $actor,
            );

            return $role;
        });
    }

    public function updateRole(Role $role, string $name, array $permissions, ?User $actor = null): Role
    {
        return DB::transaction(function () use ($role, $name, $permissions, $actor) {
            $old = [
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->all(),
            ];

            if ($role->name === 'Super Admin') {
                $name = 'Super Admin';
                $permissions = PermissionCatalog::all();
            }

            $role->update(['name' => $name]);
            $role->syncPermissions($permissions);

            $this->auditLogger->log(
                'roles',
                'updated',
                "Updated role {$name}",
                null,
                $old,
                ['name' => $name, 'permissions' => $permissions],
                $actor,
            );

            return $role->fresh('permissions');
        });
    }

    public function deleteRole(Role $role, ?User $actor = null): void
    {
        if ($role->name === 'Super Admin') {
            abort(403, 'The Super Admin role cannot be deleted.');
        }

        DB::transaction(function () use ($role, $actor) {
            $this->auditLogger->log(
                'roles',
                'deleted',
                "Deleted role {$role->name}",
                null,
                ['name' => $role->name],
                null,
                $actor,
            );

            $role->delete();
        });
    }
}
