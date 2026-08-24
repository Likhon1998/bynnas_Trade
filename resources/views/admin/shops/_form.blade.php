@if ($errors->any())
    <div class="form-span" style="background:#fee2e2;color:#b91c1c;border-radius:10px;padding:10px 12px;font-size:13px">
        <ul style="margin:0;padding-left:18px">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div>
    <label class="label">Shop name</label>
    <input class="input" style="width:100%" name="name" value="{{ old('name', $shop?->name) }}" required>
</div>
<div>
    <label class="label">Owner name</label>
    <input class="input" style="width:100%" name="owner_name" value="{{ old('owner_name', $shop?->owner_name) }}" required>
</div>
<div>
    <label class="label">Email</label>
    <input class="input" style="width:100%" type="email" name="email" value="{{ old('email', $shop?->email) }}">
</div>
<div>
    <label class="label">Phone</label>
    <input class="input" style="width:100%" name="phone" value="{{ old('phone', $shop?->phone) }}">
</div>
<div>
    <label class="label">City</label>
    <input class="input" style="width:100%" name="city" value="{{ old('city', $shop?->city) }}">
</div>
<div>
    <label class="label">Territory</label>
    <select class="select" style="width:100%" name="territory_id">
        <option value="">Select territory</option>
        @foreach ($territories as $territory)
            <option value="{{ $territory->id }}" @selected(old('territory_id', $shop?->territory_id) == $territory->id)>{{ $territory->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="label">Price group</label>
    <select class="select" style="width:100%" name="price_group_id">
        <option value="">Select price group</option>
        @foreach ($priceGroups as $group)
            <option value="{{ $group->id }}" @selected(old('price_group_id', $shop?->price_group_id) == $group->id)>{{ $group->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="label">Assigned salesman</label>
    <select class="select" style="width:100%" name="assigned_salesman_id">
        <option value="">Unassigned</option>
        @foreach ($salesmen as $salesman)
            <option value="{{ $salesman->id }}" @selected(old('assigned_salesman_id', $shop?->assigned_salesman_id) == $salesman->id)>{{ $salesman->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="label">Credit limit (৳)</label>
    <input class="input" style="width:100%" type="number" step="0.01" name="credit_limit" value="{{ old('credit_limit', $shop?->credit_limit ?? 150000) }}">
</div>
<div>
    <label class="label">Payment terms (days)</label>
    <input class="input" style="width:100%" type="number" name="payment_terms_days" value="{{ old('payment_terms_days', $shop?->payment_terms_days ?? 21) }}">
</div>
<div>
    <label class="label">Status</label>
    <select class="select" style="width:100%" name="status" required>
        @foreach (['pending' => 'Pending Approval', 'active' => 'Active', 'on_hold' => 'On Hold', 'rejected' => 'Rejected'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $shop?->status ?? 'pending') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="form-span">
    <label class="label">Business address</label>
    <textarea class="textarea" name="address" rows="3">{{ old('address', $shop?->address) }}</textarea>
</div>
<div class="form-span">
    <label class="label">Notes</label>
    <textarea class="textarea" name="notes" rows="2">{{ old('notes', $shop?->notes) }}</textarea>
</div>
