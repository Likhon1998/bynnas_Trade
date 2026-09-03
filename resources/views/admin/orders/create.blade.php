@extends('layouts.app')
@section('title', 'Create Order')
@section('content')
    @php
        $shopPayload = $shops->map(function ($shop) {
            return [
                'id' => $shop->id,
                'name' => $shop->name,
                'code' => $shop->code,
                'credit' => (float) $shop->availableCredit(),
                'credit_limit' => (float) $shop->credit_limit,
                'price_group' => $shop->priceGroup?->name ?: 'Standard wholesale',
                'salesman_id' => $shop->assigned_salesman_id,
            ];
        })->values();

        $productPayload = $products->map(function ($product) use ($shops) {
            $prices = ['default' => (float) $product->wholesale_price];
            foreach ($shops as $shop) {
                $prices[(string) $shop->id] = (float) $product->priceForGroup($shop->priceGroup);
            }

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'stock' => $product->availableStock(),
                'prices' => $prices,
            ];
        })->values();
    @endphp

    <x-page-header title="Create Order" subtitle="Place an order on behalf of any active shop">
        <a class="btn btn-ghost" href="{{ route('orders.index') }}">Back</a>
    </x-page-header>

    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">
            <ul style="margin:0;padding-left:18px">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px;background:#f5f3ff;color:#5b21b6;font-size:13px">
        Admin orders are created for the selected shop and still go to the <strong>Super Admin audit queue</strong> before stock is reserved.
        Pricing uses that shop’s wholesale price group.
    </div>

    <form class="card" style="padding:20px" method="post" action="{{ route('orders.store') }}"
          x-data="adminOrderForm(@js($shopPayload), @js($productPayload), {{ (int) old('shop_id', 0) }}, {{ (int) old('salesman_id', 0) }})">
        @csrf
        <div class="form-grid" style="margin-bottom:16px">
            <div class="field">
                <label class="label">Shop / client</label>
                <select class="select" name="shop_id" required x-model="shopId" @change="onShopChange()">
                    <option value="">Select shop</option>
                    @foreach ($shops as $shop)
                        <option value="{{ $shop->id }}" @selected(old('shop_id') == $shop->id)>{{ $shop->name }} ({{ $shop->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label">Salesman (optional)</label>
                <select class="select" name="salesman_id" x-model="salesmanId">
                    <option value="">Shop default / none</option>
                    @foreach ($salesmen as $salesman)
                        <option value="{{ $salesman->id }}" @selected(old('salesman_id') == $salesman->id)>{{ $salesman->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="grid-column:1/-1">
                <label class="label">Notes</label>
                <textarea class="textarea" name="notes" rows="2" placeholder="Optional note for audit">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="card" style="padding:14px;margin-bottom:16px;background:#f8fafc;border:1px solid var(--line)" x-show="selectedShop" x-cloak>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px">
                <div>
                    <div class="muted" style="font-size:12px">Selected shop</div>
                    <div style="font-weight:700" x-text="selectedShop?.name"></div>
                </div>
                <div>
                    <div class="muted" style="font-size:12px">Price group</div>
                    <div style="font-weight:700" x-text="selectedShop?.price_group"></div>
                </div>
                <div>
                    <div class="muted" style="font-size:12px">Available credit</div>
                    <div style="font-weight:700" x-text="formatMoney(selectedShop?.credit || 0)"></div>
                </div>
                <div>
                    <div class="muted" style="font-size:12px">Estimated total</div>
                    <div style="font-weight:800;color:var(--brand-dark)" x-text="formatMoney(estimatedTotal)"></div>
                </div>
            </div>
        </div>

        <div style="font-weight:700;margin-bottom:10px">Order lines</div>
        <div class="table-wrap" style="margin-bottom:16px">
            <table class="data">
                <thead><tr><th>Product</th><th>Available</th><th>Shop price</th><th>Qty</th><th>Line</th></tr></thead>
                <tbody>
                    @foreach ($products as $index => $product)
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <x-product-thumb :product="$product" :size="44" />
                                    <div>
                                        <div style="font-weight:600">{{ $product->name }}</div>
                                        <div class="muted" style="font-size:12px">{{ $product->sku }}</div>
                                    </div>
                                </div>
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $product->id }}">
                            </td>
                            <td>{{ number_format($product->availableStock()) }}</td>
                            <td style="font-weight:700" x-text="formatMoney(priceFor({{ $product->id }}))"></td>
                            <td>
                                <input class="input" style="width:90px" type="number" min="0"
                                       name="items[{{ $index }}][quantity]"
                                       x-model.number="qty[{{ $product->id }}]"
                                       value="{{ old('items.'.$index.'.quantity', 0) }}">
                            </td>
                            <td style="font-weight:700" x-text="formatMoney(lineTotal({{ $product->id }}))"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit" :disabled="!shopId || estimatedTotal <= 0">
                Submit for Super Admin audit
            </button>
            <span class="muted" style="font-size:13px" x-show="shopId && estimatedTotal <= 0">Add at least one quantity to continue.</span>
        </div>
    </form>
@endsection

@push('scripts')
<script>
function adminOrderForm(shops, products, initialShopId, initialSalesmanId) {
    return {
        shops,
        products,
        shopId: initialShopId ? String(initialShopId) : '',
        salesmanId: initialSalesmanId ? String(initialSalesmanId) : '',
        qty: Object.fromEntries(products.map(p => [p.id, 0])),
        get selectedShop() {
            return this.shops.find(s => String(s.id) === String(this.shopId)) || null;
        },
        get estimatedTotal() {
            return this.products.reduce((sum, product) => sum + this.lineTotal(product.id), 0);
        },
        onShopChange() {
            if (this.selectedShop?.salesman_id) {
                this.salesmanId = String(this.selectedShop.salesman_id);
            }
        },
        priceFor(productId) {
            const product = this.products.find(p => p.id === productId);
            if (!product) return 0;
            if (this.shopId && product.prices[String(this.shopId)] !== undefined) {
                return product.prices[String(this.shopId)];
            }
            return product.prices.default || 0;
        },
        lineTotal(productId) {
            const qty = Number(this.qty[productId] || 0);
            return Math.round(this.priceFor(productId) * qty * 100) / 100;
        },
        formatMoney(amount) {
            return '৳ ' + Number(amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        },
    }
}
</script>
@endpush
