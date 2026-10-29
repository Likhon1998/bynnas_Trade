@php
    $salesman = $salesman ?? null;
    $profile = $salesman?->salesmanProfile;
    $selectedShops = array_map('intval', old('shop_ids', $salesman?->assignedShops?->pluck('id')->all() ?? []));
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
        <label class="label">Grade</label>
        <select class="select" name="grade">
            <option value="">No grade</option>
            @foreach (['A+', 'A', 'B', 'C', 'D'] as $grade)
                <option value="{{ $grade }}" @selected(old('grade', $profile?->grade) === $grade)>{{ $grade }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label class="label">Rating (0–5)</label>
        <input class="input" type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $profile?->rating) }}">
    </div>
    <div class="field">
        <label class="label">Joined on</label>
        <input class="input" type="date" name="joined_at" value="{{ old('joined_at', optional($profile?->joined_at)->format('Y-m-d')) }}">
    </div>
    <div class="field" style="grid-column:1/-1">
        <label class="label" id="assign-shops">Assign dokan (shops)</label>
        <p class="muted" style="margin:0 0 8px;font-size:12px">Tick the shops this salesman will look after. Unassigned shops stay visible to every salesman until you assign them.</p>
        @php
            $salesmanId = (int) ($salesman?->id ?? 0);
            $shopsByArea = $shops->groupBy(fn ($s) => $s->city ?: 'No area');
        @endphp
        <div class="sa-assign" x-data="{ q: '' }">
            <input class="input" type="search" x-model="q" placeholder="Search shop or area" autocomplete="off" aria-label="Search shops to assign">
            <div class="sa-assign-list">
                @forelse ($shopsByArea as $area => $areaShops)
                    <div class="sa-area" x-show="[...$el.querySelectorAll('[data-search]')].some(n => !q || n.dataset.search.includes(q.toLowerCase()))">
                        <div class="sa-area-head">{{ $area }} <span>{{ $areaShops->count() }}</span></div>
                        @foreach ($areaShops as $shop)
                            @php
                                $other = $shop->assigned_salesman_id && (int) $shop->assigned_salesman_id !== $salesmanId
                                    ? $shop->assignedSalesman?->name
                                    : null;
                                $meta = $shop->code;
                                if ($shop->status !== \App\Models\Shop::STATUS_ACTIVE) {
                                    $meta .= ' · '.$shop->statusLabel();
                                }
                                if ($other) {
                                    $meta .= ' · now '.$other;
                                } elseif (! $shop->assigned_salesman_id) {
                                    $meta .= ' · unassigned';
                                }
                            @endphp
                            <label class="sa-shop" data-search="{{ strtolower($shop->name.' '.$shop->code.' '.$shop->city) }}" x-show="!q || ($el.dataset.search || '').includes(q.toLowerCase())">
                                <input type="checkbox" name="shop_ids[]" value="{{ $shop->id }}" @checked(in_array($shop->id, $selectedShops, true))>
                                <span>
                                    <b>{{ $shop->name }}</b>
                                    <small>{{ $meta }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @empty
                    <p class="muted" style="padding:12px">No shops to assign yet.</p>
                @endforelse
            </div>
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
