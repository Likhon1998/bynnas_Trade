@if ($errors->any())
    <div style="background:#fee2e2;color:#b91c1c;border-radius:10px;padding:10px 12px;font-size:13px;margin-bottom:14px">
        <ul style="margin:0;padding-left:18px">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="margin-bottom:16px">
    <label class="label">Role name</label>
    <input class="input" style="width:100%;max-width:420px" name="name" value="{{ old('name', $role?->name) }}" @disabled($role?->name === 'Super Admin') required>
    @if ($role?->name === 'Super Admin')
        <input type="hidden" name="name" value="Super Admin">
        <p class="muted">Super Admin always retains full system access.</p>
    @endif
</div>

@foreach ($modules as $module => $actions)
    <div style="margin-bottom:14px;padding:12px;border:1px solid var(--line);border-radius:12px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <div style="font-weight:700;font-size:13px;text-transform:capitalize">{{ str_replace('_', ' ', $module) }}</div>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px">
            @foreach ($actions as $action => $label)
                @php $perm = "{$module}.{$action}"; @endphp
                <label style="display:flex;align-items:center;gap:6px;font-size:12px;background:#f8f9fd;padding:6px 10px;border-radius:8px">
                    <input type="checkbox" name="permissions[]" value="{{ $perm }}"
                        @checked(collect($selected)->contains($perm))
                        @disabled($role?->name === 'Super Admin')>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
@endforeach
