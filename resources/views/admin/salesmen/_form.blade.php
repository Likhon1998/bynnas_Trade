@php
    $salesman = $salesman ?? null;
    $profile = $salesman?->salesmanProfile;
    $selectedShops = old('shop_ids', $salesman?->assignedShops?->pluck('id')->all() ?? []);
@endphp

<div class="form-grid">
    <div class="field">
        <label class="label">Full name</label>
        <input class="input" name="name" value="{{ old('name', $salesman?->name) }}" required>
    </div>
    <div class="field">
        <label class="label">Employee code</label>
        <input class="input" name="employee_code" value="{{ old('employee_code', $profile?->employee_code) }}" placeholder="Auto if blank">
    </div>
    <div class="field">
        <label class="label">Email (field login)</label>
        <input class="input" type="email" name="email" value="{{ old('email', $salesman?->email) }}" required>
    </div>
    <div class="field">
        <label class="label">Phone</label>
        <input class="input" name="phone" value="{{ old('phone', $salesman?->phone) }}">
    </div>
    <div class="field">
        <label class="label">{{ $salesman ? 'New password (optional)' : 'Password' }}</label>
        <input class="input" type="password" name="password" {{ $salesman ? '' : 'required' }} minlength="8" placeholder="{{ $salesman ? 'Leave blank to keep' : 'Min 8 characters' }}">
    </div>
    <div class="field">
        <label class="label">Territory</label>
        <select class="select" name="territory_id">
            <option value="">All / unassigned</option>
            @foreach ($territories as $territory)
                <option value="{{ $territory->id }}" @selected((string) old('territory_id', $profile?->territory_id) === (string) $territory->id)>{{ $territory->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label class="label">Monthly target (৳)</label>
        <input class="input" type="number" step="0.01" min="0" name="monthly_target" value="{{ old('monthly_target', $profile?->monthly_target ?? 0) }}">
    </div>
    <div class="field">
        <label class="label">Joined on</label>
        <input class="input" type="date" name="joined_at" value="{{ old('joined_at', optional($profile?->joined_at)->format('Y-m-d')) }}">
    </div>
    <div class="field" style="grid-column:1/-1">
        <label class="label">Assigned shops</label>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;max-height:220px;overflow:auto;padding:8px;border:1px solid #e5e7eb;border-radius:8px">
            @foreach ($shops as $shop)
                <label style="display:flex;gap:8px;align-items:flex-start;font-size:13px">
                    <input type="checkbox" name="shop_ids[]" value="{{ $shop->id }}" @checked(in_array($shop->id, $selectedShops, true))>
                    <span>{{ $shop->name }} <span class="muted">({{ $shop->code }})</span></span>
                </label>
            @endforeach
        </div>
    </div>
    <div class="field" style="grid-column:1/-1">
        <label class="label">Notes</label>
        <textarea class="input" name="notes" rows="3">{{ old('notes', $profile?->notes) }}</textarea>
    </div>
    <div class="field">
        <label style="display:flex;gap:8px;align-items:center;font-size:13px">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $salesman?->is_active ?? true))>
            Active
        </label>
    </div>
</div>
