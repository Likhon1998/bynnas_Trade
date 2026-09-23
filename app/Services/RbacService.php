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

        foreach (PermissionCatalog::rolePresets() as $roleName => $permissions) {
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
