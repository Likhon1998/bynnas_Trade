<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Shop;
use App\Models\Territory;
use App\Models\User;
use App\Models\UserAccessScope;
use App\Models\Warehouse;
use App\Services\RbacService;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private RbacService $rbac) {}

    public function index(Request $request)
    {
        $this->authorize('users.view');

        $users = User::query()
            ->with(['roles', 'accessScopes'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->portal, fn ($q, $portal) => $q->where('portal', $portal))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('users.create');

        return view('admin.users.create', [
            'roles' => Role::query()->with('permissions')->orderBy('name')->get(),
            'modules' => PermissionCatalog::modules(),
            'scopeTypes' => $this->scopeTypes(),
            ...$this->scopeOptions(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $this->guardPrivileges($request, null, $data['roles'] ?? [], $data['permissions'] ?? []);

        $user = $this->rbac->createUser(
            $data,
            $data['roles'] ?? [],
            $this->scopeFrom($data),
            $request->user(),
        );

        if (! empty($data['permissions'])) {
            $user->syncPermissions($data['permissions']);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $this->authorize('users.view');

        $user->load(['roles.permissions', 'permissions', 'accessScopes']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorize('users.edit');

        $user->load(['roles', 'permissions', 'accessScopes']);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => Role::query()->with('permissions')->orderBy('name')->get(),
            'modules' => PermissionCatalog::modules(),
            'scopeTypes' => $this->scopeTypes(),
            ...$this->scopeOptions(),
            'selectedPermissions' => $user->getDirectPermissions()->pluck('name')->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        if ($user->isSuperAdmin() && $request->user()->id !== $user->id && ! $request->user()->isSuperAdmin()) {
            abort(403, 'Only a Super Admin can modify another Super Admin.');
        }

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $this->guardPrivileges($request, $user, $data['roles'] ?? [], $data['permissions'] ?? []);

        $this->rbac->updateUser(
            $user,
            $data,
            $data['roles'] ?? [],
            $this->scopeFrom($data),
            $request->user(),
        );

        $user->syncPermissions($data['permissions'] ?? []);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function toggleActive(Request $request, User $user)
    {
        $this->authorize('users.activate');

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super Admin accounts cannot be deactivated.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        app(\App\Services\AuditLogger::class)->log(
            'users',
            $user->is_active ? 'activated' : 'deactivated',
            ($user->is_active ? 'Activated' : 'Deactivated')." user {$user->email}",
            $user,
            null,
            ['is_active' => $user->is_active],
            $request->user(),
        );

        return back()->with('success', 'User status updated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->authorize('users.reset_password');
        abort_if($user->isSuperAdmin() && ! $request->user()->isSuperAdmin(), 403, 'Only a Super Admin can reset a Super Admin password.');

        $data = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $this->rbac->resetPassword($user, $data['password'], $request->user());

        return back()->with('success', 'Password reset successfully.');
    }

    /**
     * Stop privilege escalation: only a Super Admin may hand out the Super Admin role, and nobody
     * else may grant a role or permission they don't hold themselves.
     */
    private function guardPrivileges(Request $request, ?User $target, array $roles, array $permissions): void
    {
        $actor = $request->user();
        if ($actor->isSuperAdmin()) {
            return;
        }

        $currentRoles = $target ? $target->getRoleNames()->all() : [];
        $currentPermissions = $target ? $target->getDirectPermissions()->pluck('name')->all() : [];
        $addedRoles = array_values(array_diff($roles, $currentRoles));
        $addedPermissions = array_values(array_diff($permissions, $currentPermissions));
        $rolesChanged = $addedRoles !== [] || array_diff($currentRoles, $roles) !== [];
        $permissionsChanged = $addedPermissions !== [] || array_diff($currentPermissions, $permissions) !== [];

        abort_if(in_array('Super Admin', $addedRoles, true), 403, 'Only a Super Admin can grant the Super Admin role.');
        abort_if($rolesChanged && ! $actor->can('roles.assign'), 403, 'You are not allowed to change roles.');
        abort_if($permissionsChanged && ! $actor->can('permissions.manage'), 403, 'You are not allowed to change direct permissions.');

        $granted = Role::query()->whereIn('name', $addedRoles)->with('permissions')->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->merge($addedPermissions)
            ->unique();
        $missing = $granted->reject(fn (string $permission) => $actor->can($permission));
        abort_if($missing->isNotEmpty(), 403, 'You cannot grant permissions you do not have: '.$missing->take(5)->implode(', '));
    }

    /** @return array{scope_type: string, scope_id: ?int, label: ?string} */
    private function scopeFrom(array $data): array
    {
        $type = $data['scope_type'];
        $id = $type === UserAccessScope::TYPE_GLOBAL ? null : (int) $data['scope_id'];
        $name = match ($type) {
            UserAccessScope::TYPE_TERRITORY => Territory::query()->whereKey($id)->value('name'),
            UserAccessScope::TYPE_WAREHOUSE => Warehouse::query()->whereKey($id)->value('name'),
            UserAccessScope::TYPE_SHOP => Shop::query()->whereKey($id)->value('name'),
            default => null,
        };

        return ['scope_type' => $type, 'scope_id' => $id, 'label' => ($data['scope_label'] ?? null) ?: $name];
    }

    /** @return array<string, \Illuminate\Support\Collection> */
    private function scopeOptions(): array
    {
        return [
            'scopeOptions' => [
                UserAccessScope::TYPE_TERRITORY => Territory::query()->orderBy('name')->pluck('name', 'id'),
                UserAccessScope::TYPE_WAREHOUSE => Warehouse::query()->orderBy('name')->pluck('name', 'id'),
                UserAccessScope::TYPE_SHOP => Shop::query()->where('status', '!=', Shop::STATUS_REJECTED)->orderBy('name')->pluck('name', 'id'),
            ],
        ];
    }

    private function scopeTypes(): array
    {
        return [
            UserAccessScope::TYPE_GLOBAL => 'All data (global)',
            UserAccessScope::TYPE_TERRITORY => 'Assigned territory',
            UserAccessScope::TYPE_WAREHOUSE => 'Assigned warehouse',
            UserAccessScope::TYPE_SHOP => 'Assigned shop',
        ];
    }
}
