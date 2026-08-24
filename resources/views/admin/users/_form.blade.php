@php
    $currentScope = $user?->accessScopes?->first();
@endphp

@if ($errors->any())
    <div class="form-span" style="background:#fee2e2;color:#b91c1c;border-radius:10px;padding:10px 12px;font-size:13px">
        <ul style="margin:0;padding-left:18px">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

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
    <label class="label">Roles</label>
    <div style="display:flex;flex-wrap:wrap;gap:10px">
        @foreach ($roles as $role)
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;background:#f8f9fd;padding:8px 12px;border-radius:10px">
                <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                    @checked(collect(old('roles', $user?->roles?->pluck('name')->all() ?? []))->contains($role->name))>
                {{ $role->name }}
            </label>
        @endforeach
    </div>
</div>

<div class="form-span">
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true))>
        Account is active
    </label>
</div>

<div class="form-span">
    <div class="section-title" style="margin-bottom:8px">Direct permission overrides</div>
    <p class="muted" style="margin-top:0">Optional. These are added on top of role permissions for this user only.</p>
    @foreach ($modules as $module => $actions)
        <div style="margin-bottom:14px;padding:12px;border:1px solid var(--line);border-radius:12px">
            <div style="font-weight:700;font-size:13px;margin-bottom:8px;text-transform:capitalize">{{ str_replace('_', ' ', $module) }}</div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                @foreach ($actions as $action => $label)
                    @php $perm = "{$module}.{$action}"; @endphp
                    <label style="display:flex;align-items:center;gap:6px;font-size:12px;background:#f8f9fd;padding:6px 10px;border-radius:8px">
                        <input type="checkbox" name="permissions[]" value="{{ $perm }}" @checked(collect($selectedPermissions)->contains($perm))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
