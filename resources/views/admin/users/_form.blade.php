@php
    $currentScope = $user?->accessScopes?->first();
    $rolePermissionMap = $roles->mapWithKeys(fn ($role) => [
        $role->name => $role->permissions->pluck('name')->values()->all(),
    ])->all();
    $initialRoles = collect(old('roles', $user?->roles?->pluck('name')->all() ?? []))->values()->all();
    $initialDirect = collect($selectedPermissions ?? [])->values()->all();
@endphp

<div
    class="rbac-page rbac-user"
    x-data="userRoleForm({
        selectedRoles: @js($initialRoles),
        roleMap: @js($rolePermissionMap),
        modules: @js($modules),
        direct: @js($initialDirect),
    })"
>
    @if ($errors->any())
        <div class="rbac-alert rbac-alert--error form-span">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rbac-user__layout">
        <section class="rbac-user__profile card">
            <div class="section-title" style="margin-bottom:14px">Account</div>
            <div class="form-grid">
                <div>
                    <label class="label">Full name</label>
                    <input class="input" style="width:100%" name="name" value="{{ old('name', $user?->name) }}" required>
                </div>
                <div>
                    <label class="label">Work email</label>
                    <input class="input" style="width:100%" type="email" name="email" value="{{ old('email', $user?->email) }}" required>
                </div>
                <div>
                    <label class="label">Phone</label>
                    <input class="input" style="width:100%" name="phone" value="{{ old('phone', $user?->phone) }}">
                </div>
                <div>
                    <label class="label">Portal</label>
                    <select class="select" style="width:100%" name="portal" required>
                        @foreach (['admin' => 'Admin', 'shop' => 'Shop', 'salesman' => 'Salesman'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('portal', $user?->portal ?? 'admin') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if (! $user)
                    <div>
                        <label class="label">Password</label>
                        <input class="input" style="width:100%" type="password" name="password" required>
                    </div>
                    <div>
                        <label class="label">Confirm password</label>
                        <input class="input" style="width:100%" type="password" name="password_confirmation" required>
                    </div>
                @else
                    <div>
                        <label class="label">New password (optional)</label>
                        <input class="input" style="width:100%" type="password" name="password">
                    </div>
                    <div>
                        <label class="label">Confirm new password</label>
                        <input class="input" style="width:100%" type="password" name="password_confirmation">
                    </div>
                @endif

                <div>
                    <label class="label">Access scope</label>
                    <select class="select" style="width:100%" name="scope_type" required>
                        @foreach ($scopeTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('scope_type', $currentScope?->scope_type ?? 'global') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Scope label</label>
                    <input class="input" style="width:100%" name="scope_label" value="{{ old('scope_label', $currentScope?->label) }}" placeholder="e.g. Dhaka North / Dhaka Central Warehouse">
                </div>

                <div class="form-span">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true))>
                        Account is active
                    </label>
                </div>
            </div>
        </section>

        <section class="rbac-user__roles card">
            <div class="section-title" style="margin-bottom:6px">Roles</div>
            <p class="muted" style="margin:0 0 14px">Select a role and its recommended permissions appear below automatically.</p>
            <div class="rbac-role-pick">
                @foreach ($roles as $role)
                    <label class="rbac-role-chip" :class="{ 'is-on': selectedRoles.includes(@js($role->name)) }">
                        <input
                            type="checkbox"
                            name="roles[]"
                            value="{{ $role->name }}"
                            :checked="selectedRoles.includes(@js($role->name))"
                            @change="toggleRole(@js($role->name), $event.target.checked)"
                        >
                        <strong>{{ $role->name }}</strong>
                        <span>{{ $role->permissions->count() }} permissions</span>
                    </label>
                @endforeach
            </div>
        </section>
    </div>

    <section class="card rbac-user__effective">
        <div class="rbac-toolbar" style="margin-bottom:0;padding:0;border:0;background:transparent">
            <div>
                <div class="section-title">Access from selected roles</div>
                <p class="muted" style="margin:4px 0 0" x-show="selectedRoles.length === 0">Pick at least one role to preview access.</p>
                <p class="muted" style="margin:4px 0 0" x-show="selectedRoles.length > 0" x-text="selectedRoles.join(', ') + ' · ' + effectivePermissions.length + ' permissions'"></p>
            </div>
        </div>

        <div class="rbac-modules" style="margin-top:16px" x-show="selectedRoles.length > 0">
            <template x-for="(actions, module) in modules" :key="module">
                <section
                    class="rbac-module"
                    x-show="moduleHasEffective(module, actions)"
                    :class="{ 'is-on': true }"
                >
                    <header class="rbac-module__head">
                        <div class="rbac-module__title">
                            <span x-text="String(module).replace(/_/g, ' ')" style="text-transform:capitalize"></span>
                        </div>
                        <span class="rbac-module__meta" x-text="effectiveInModule(module, actions) + '/' + Object.keys(actions).length"></span>
                    </header>
                    <div class="rbac-module__body">
                        <template x-for="(label, action) in actions" :key="action">
                            <span
                                class="rbac-perm is-checked rbac-perm--readonly"
                                x-show="isEffective(module + '.' + action)"
                                x-text="label"
                            ></span>
                        </template>
                    </div>
                </section>
            </template>
        </div>
    </section>

    <section class="card rbac-user__overrides">
        <div class="section-title" style="margin-bottom:6px">Direct permission overrides</div>
        <p class="muted" style="margin:0 0 14px">Optional extras on top of role access — for this user only.</p>
        <div class="rbac-modules">
            @foreach ($modules as $module => $actions)
                <section class="rbac-module">
                    <header class="rbac-module__head">
                        <div class="rbac-module__title">
                            <span>{{ str_replace('_', ' ', $module) }}</span>
                        </div>
                    </header>
                    <div class="rbac-module__body">
                        @foreach ($actions as $action => $label)
                            @php $perm = "{$module}.{$action}"; @endphp
                            <label class="rbac-perm" :class="{ 'is-checked': direct.includes(@js($perm)) }">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $perm }}"
                                    :checked="direct.includes(@js($perm))"
                                    @change="toggleDirect(@js($perm), $event.target.checked)"
                                >
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </section>
</div>

@once
    @push('scripts')
        <script>
            function userRoleForm(config) {
                return {
                    selectedRoles: Array.isArray(config.selectedRoles) ? [...config.selectedRoles] : [],
                    roleMap: config.roleMap || {},
                    modules: config.modules || {},
                    direct: Array.isArray(config.direct) ? [...config.direct] : [],

                    get effectivePermissions() {
                        const set = new Set();
                        this.selectedRoles.forEach((name) => {
                            (this.roleMap[name] || []).forEach((p) => set.add(p));
                        });
                        return [...set];
                    },

                    toggleRole(name, on) {
                        if (on && !this.selectedRoles.includes(name)) {
                            this.selectedRoles.push(name);
                        } else if (!on) {
                            this.selectedRoles = this.selectedRoles.filter((r) => r !== name);
                        }
                    },

                    isEffective(perm) {
                        return this.effectivePermissions.includes(perm);
                    },

                    moduleHasEffective(module, actions) {
                        return Object.keys(actions).some((action) => this.isEffective(module + '.' + action));
                    },

                    effectiveInModule(module, actions) {
                        return Object.keys(actions).filter((action) => this.isEffective(module + '.' + action)).length;
                    },

                    toggleDirect(perm, on) {
                        if (on && !this.direct.includes(perm)) {
                            this.direct.push(perm);
                        } else if (!on) {
                            this.direct = this.direct.filter((p) => p !== perm);
                        }
                    },
                };
            }
        </script>
    @endpush
@endonce
