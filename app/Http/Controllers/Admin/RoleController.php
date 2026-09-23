<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\RbacService;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private RbacService $rbac) {}

    public function index()
    {
        $this->authorize('roles.view');

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', [
            'roles' => $roles,
            'presetDescriptions' => PermissionCatalog::rolePresetDescriptions(),
        ]);
    }

    public function create()
    {
        $this->authorize('roles.create');

        return view('admin.roles.create', [
            'modules' => PermissionCatalog::modules(),
            'presets' => PermissionCatalog::rolePresets(),
            'presetDescriptions' => PermissionCatalog::rolePresetDescriptions(),
        ]);
    }

    public function store(StoreRoleRequest $request)
    {
        $this->rbac->createRole(
            $request->validated('name'),
            $request->validated('permissions') ?? [],
            $request->user(),
        );

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $this->authorize('roles.edit');

        $role->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role,
            'modules' => PermissionCatalog::modules(),
            'selected' => $role->permissions->pluck('name')->all(),
            'presets' => PermissionCatalog::rolePresets(),
            'presetDescriptions' => PermissionCatalog::rolePresetDescriptions(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        $this->rbac->updateRole(
            $role,
            $request->validated('name'),
            $request->validated('permissions') ?? [],
            $request->user(),
        );

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, Role $role)
    {
        $this->authorize('roles.delete');

        $this->rbac->deleteRole($role, $request->user());

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
