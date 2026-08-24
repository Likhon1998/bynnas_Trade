<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Models\UserAccessScope;
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
            'roles' => Role::query()->orderBy('name')->get(),
            'modules' => PermissionCatalog::modules(),
            'scopeTypes' => $this->scopeTypes(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $user = $this->rbac->createUser(
            $data,
            $data['roles'] ?? [],
            [
                'scope_type' => $data['scope_type'],
                'label' => $data['scope_label'] ?? null,
            ],
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
            'roles' => Role::query()->orderBy('name')->get(),
            'modules' => PermissionCatalog::modules(),
            'scopeTypes' => $this->scopeTypes(),
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

        $this->rbac->updateUser(
            $user,
            $data,
            $data['roles'] ?? [],
            [
                'scope_type' => $data['scope_type'],
                'label' => $data['scope_label'] ?? null,
            ],
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

        $data = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $this->rbac->resetPassword($user, $data['password'], $request->user());

        return back()->with('success', 'Password reset successfully.');
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
