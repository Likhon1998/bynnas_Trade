@if ($errors->any())
    <div class="form-span" style="background:#fee2e2;color:#b91c1c;border-radius:10px;padding:10px 12px;font-size:13px">
        <ul style="margin:0;padding-left:18px">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div>
    <label class="label">Product name</label>
    <input class="input" style="width:100%" name="name" value="{{ old('name', $product?->name) }}" required>
</div>
<div>
    <label class="label">SKU</label>
    <input class="input" style="width:100%" name="sku" value="{{ old('sku', $product?->sku) }}" required>
</div>
<div>
    <label class="label">Barcode</label>
    <input class="input" style="width:100%" name="barcode" value="{{ old('barcode', $product?->barcode) }}">
</div>
<div>
    <label class="label">Category</label>
    <select class="select" style="width:100%" name="category_id">
        <option value="">Select</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $product?->category_id) == $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="label">Brand</label>
    <select class="select" style="width:100%" name="brand_id">
        <option value="">Select</option>
        @foreach ($brands as $brand)
            <option value="{{ $brand->id }}" @selected(old('brand_id', $product?->brand_id) == $brand->id)>{{ $brand->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="label">Model / variant</label>
    <input class="input" style="width:100%" name="model" value="{{ old('model', $product?->model) }}" placeholder="Model">
</div>
<div>
    <label class="label">Wholesale price (৳)</label>
    <input class="input" style="width:100%" type="number" step="0.01" name="wholesale_price" value="{{ old('wholesale_price', $product?->wholesale_price ?? 0) }}" required>
</div>
<div>
    <label class="label">Dealer price</label>
    <input class="input" style="width:100%" type="number" step="0.01" name="dealer_price" value="{{ old('dealer_price', $product?->dealer_price ?? 0) }}">
</div>
<div>
    <label class="label">Distributor price</label>
    <input class="input" style="width:100%" type="number" step="0.01" name="distributor_price" value="{{ old('distributor_price', $product?->distributor_price ?? 0) }}">
</div>
<div>
    <label class="label">Retail price</label>
    <input class="input" style="width:100%" type="number" step="0.01" name="retail_price" value="{{ old('retail_price', $product?->retail_price ?? 0) }}">
</div>
<div>
    <label class="label">Minimum selling price</label>
    <input class="input" style="width:100%" type="number" step="0.01" name="minimum_selling_price" value="{{ old('minimum_selling_price', $product?->minimum_selling_price ?? 0) }}">
</div>
<div>
    <label class="label">Cost price</label>
    <input class="input" style="width:100%" type="number" step="0.01" name="cost_price" value="{{ old('cost_price', $product?->cost_price ?? 0) }}">
</div>
<div>
    <label class="label">Landed cost</label>
    <input class="input" style="width:100%" type="number" step="0.01" name="landed_cost" value="{{ old('landed_cost', $product?->landed_cost ?? 0) }}">
</div>
<div>
    <label class="label">Stock on hand</label>
    <input class="input" style="width:100%" type="number" name="stock_on_hand" value="{{ old('stock_on_hand', $product?->stock_on_hand ?? 0) }}">
</div>
<div>
    <label class="label">Minimum stock</label>
    <input class="input" style="width:100%" type="number" name="minimum_stock" value="{{ old('minimum_stock', $product?->minimum_stock ?? 10) }}">
</div>
<div>
    <label class="label">Warranty</label>
    <input class="input" style="width:100%" name="warranty" value="{{ old('warranty', $product?->warranty) }}" placeholder="e.g. 12 months">
</div>
<div>
    <label class="label">Status</label>
    <select class="select" style="width:100%" name="status">
        @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'discontinued' => 'Discontinued'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $product?->status ?? 'active') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="form-span">
    <label class="label">Description</label>
    <textarea class="textarea" name="description" rows="3">{{ old('description', $product?->description) }}</textarea>
</div>
<div class="form-span">
    <label class="label">Product picture</label>
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        @if ($product?->imageUrl())
            <x-product-thumb :product="$product" :size="72" />
        @endif
        <input class="input" style="width:min(360px,100%)" type="file" name="image" accept="image/jpeg,image/png,image/webp">
    </div>
    <p class="muted" style="margin:8px 0 0">JPG, PNG or WebP · max 4 MB. Shown to shop partners and field salesmen.</p>
</div>
<div class="form-span">
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600">
        <input type="hidden" name="is_published" value="0">
        <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $product?->is_published ?? true))>
        Visible in B2B shop portal
    </label>
</div>

@can('products.manage_price')
    <div class="form-span">
        <div class="section-title" style="margin-bottom:8px">Price group overrides</div>
        <p class="muted" style="margin-top:0">Leave blank to use the standard wholesale price for that group.</p>
        <div class="form-grid">
            @foreach ($priceGroups as $group)
                @php
                    $existing = $product?->prices?->firstWhere('price_group_id', $group->id)?->wholesale_price;
                @endphp
                <div>
                    <label class="label">{{ $group->name }}</label>
                    <input class="input" style="width:100%" type="number" step="0.01" name="group_prices[{{ $group->id }}]"
                           value="{{ old('group_prices.'.$group->id, $existing) }}">
                </div>
            @endforeach
        </div>
    </div>
@endcan
