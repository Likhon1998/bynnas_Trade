@extends('field.layouts.app')
@php
    $shop = $visit->shop;
    $phone = preg_replace('/[^0-9+]/', '', (string) $shop?->phone);
    $credit = $shop?->availableCredit() ?? 0;
    $taking = $visit->isOpen() && ! $visit->order && $canOrder;
@endphp
@section('title', 'Visit · '.$shop?->name)
@section('heading', $shop?->name)
@section('subheading', ($visit->isOpen() ? 'Checked in '.$visit->checked_in_at?->format('g:i A') : 'Visit closed').' · '.$visit->outcomeLabel())
@section('back', route('field.shops'))
@if ($taking)
    @section('has-bar', '1')
@endif
@section('content')
    <div class="f-card" style="padding:14px">
        <div style="display:flex;gap:12px;align-items:center">
            <div class="f-shop-ico"><i data-lucide="store"></i></div>
            <div style="flex:1;min-width:0">
                <div class="f-shop-name">{{ $shop?->owner_name ?: $shop?->name }}</div>
                <div class="f-shop-meta">{{ $shop?->address ?: $shop?->city }}</div>
            </div>
            @if ($phone)
                <a class="f-btn f-btn-soft f-btn-icon" href="tel:{{ $phone }}" aria-label="Call shop"><i data-lucide="phone"></i></a>
            @endif
        </div>
        <div class="f-chips" style="margin-top:12px">
            @if ($canOrder)
                <span class="f-chip {{ $credit <= 0 ? 'red' : 'green' }}">Credit left {{ \App\Support\DemoData::taka($credit) }}</span>
            @else
                <span class="f-chip amber">Awaiting approval</span>
            @endif
            @if ($shop?->priceGroup)<span class="f-chip">{{ $shop->priceGroup->name }} prices</span>@endif
            @if ($shop?->payment_terms_days)<span class="f-chip">{{ $shop->payment_terms_days }}-day terms</span>@endif
        </div>
    </div>

    @if ($visit->order)
        @php $s = $visit->order->simpleStatus(); @endphp
        <div class="f-card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
                <div>
                    <div class="f-muted f-small">Order taken on this visit</div>
                    <div style="font-weight:800;font-size:18px">{{ \App\Support\DemoData::taka($visit->order->total) }}</div>
                </div>
                <span class="f-status {{ $s['tone'] }}">{{ $s['label'] }}</span>
            </div>
            <a class="f-btn f-btn-ghost f-btn-block" style="margin-top:12px" href="{{ route('field.orders.show', $visit->order) }}">View order {{ $visit->order->number }}</a>
        </div>
        @if ($visit->isOpen())
            <form method="post" action="{{ route('field.visit.checkout', $visit) }}">
                @csrf
                <input type="hidden" name="outcome" value="order_taken">
                <button class="f-btn f-btn-primary f-btn-lg f-btn-block" type="submit"><i data-lucide="log-out"></i> Finish visit</button>
            </form>
        @endif
    @elseif (! $visit->isOpen())
        <div class="f-empty">
            <i data-lucide="calendar-check"></i>
            <strong>Visit closed · {{ $visit->outcomeLabel() }}</strong>
            {{ $visit->checked_out_at?->format('d M, g:i A') }}{{ $visit->notes ? ' — '.$visit->notes : '' }}
        </div>
        <a class="f-btn f-btn-primary f-btn-block f-btn-lg" style="margin-top:12px" href="{{ route('field.shops') }}">Back to my shops</a>
    @elseif (! $canOrder)
        <div class="f-warn" style="margin:0 0 12px">
            @if ($shop?->status === \App\Models\Shop::STATUS_PENDING)
                <b>{{ $shop->name }} is not approved yet.</b> Approve it to take orders on this visit.
                <form method="post" action="{{ route('field.shops.approve', $shop) }}" style="margin-top:10px">
                    @csrf
                    <button class="f-btn f-btn-primary f-btn-lg f-btn-block" type="submit"><i data-lucide="check"></i> Approve shop &amp; take orders</button>
                </form>
            @else
                <b>{{ $shop?->name }} cannot take orders</b> ({{ strtolower($shop?->statusLabel() ?? 'unavailable') }}). Ask the office if it was deactivated.
            @endif
        </div>
        <form class="f-card" method="post" action="{{ route('field.visit.checkout', $visit) }}">
            @csrf
            <h3 style="margin:0 0 10px;font-size:14px">End visit</h3>
            <div class="f-choices">
                <label class="f-choice"><input type="radio" name="outcome" value="follow_up" checked><span><i data-lucide="calendar-clock"></i>Follow up later</span></label>
                <label class="f-choice"><input type="radio" name="outcome" value="no_order"><span><i data-lucide="x-circle"></i>No order today</span></label>
                <label class="f-choice"><input type="radio" name="outcome" value="closed"><span><i data-lucide="lock"></i>Shop closed</span></label>
            </div>
            <label class="f-label" for="visit-notes">Note (optional)</label>
            <textarea id="visit-notes" class="f-textarea" name="notes" rows="2" maxlength="1000" placeholder="e.g. owner interested in phone accessories"></textarea>
            <button class="f-btn f-btn-primary f-btn-lg f-btn-block" style="margin-top:14px" type="submit"><i data-lucide="log-out"></i> End visit</button>
        </form>
    @else
        <div x-data="fieldOrder(@js($catalog), {{ $credit }})" x-cloak>
          <div class="f-visit">
          <div class="f-visit-main">
            <div class="f-toolbar">
            <div class="f-search" style="margin-top:4px">
                <i data-lucide="search"></i>
                <input type="search" x-model="q" @input="onSearch()" placeholder="Search product or SKU" autocomplete="off" aria-label="Search products">
            </div>
            <div class="f-filters" x-show="view === 'products'">
                <button type="button" class="f-filter" @click="backToCategories()"><i data-lucide="layout-grid" style="width:14px;height:14px"></i> Categories</button>
                <button type="button" class="f-filter" :class="cat === '' && !q && 'active'" @click="showAllProducts()">All</button>
                <template x-for="c in categoryCards()" :key="c.name">
                    <button type="button" class="f-filter" :class="cat === c.name && 'active'" @click="openCategory(c.name)" x-text="c.name"></button>
                </template>
                <button type="button" class="f-filter" :class="cat === '__picked' && 'active'" @click="showPicked()" x-show="count() > 0" x-text="'In order (' + lines().length + ')'"></button>
            </div>
            </div>

            {{-- Category picker (default) --}}
            <div class="f-cat-grid" x-show="view === 'categories' && !q" x-cloak>
                <button type="button" class="f-cat-card tone-violet" @click="showAllProducts()">
                    <span class="f-cat-ico"><i data-lucide="package"></i></span>
                    <span class="f-cat-name">All products</span>
                    <span class="f-cat-count" x-text="catalog.length + ' items'"></span>
                </button>
                <template x-for="(c, i) in categoryCards()" :key="c.name">
                    <button type="button" class="f-cat-card" :class="'tone-' + ['blue','green','amber','rose','sky','teal','violet'][i % 7]" @click="openCategory(c.name)">
                        <span class="f-cat-ico"><i data-lucide="tag"></i></span>
                        <span class="f-cat-name" x-text="c.name"></span>
                        <span class="f-cat-count" x-text="c.count + (c.count === 1 ? ' product' : ' products')"></span>
                    </button>
                </template>
                <button type="button" class="f-cat-card tone-amber" @click="showPicked()" x-show="count() > 0">
                    <span class="f-cat-ico"><i data-lucide="shopping-cart"></i></span>
                    <span class="f-cat-name">In this order</span>
                    <span class="f-cat-count" x-text="lines().length + ' · ' + taka(total())"></span>
                </button>
            </div>

            <div class="f-card f-products" style="padding:4px 14px" x-show="view === 'products' || q" x-cloak>
                <div class="f-muted f-small" style="padding:8px 0 4px" x-show="!q && cat && cat !== '__picked'" x-text="'Showing ' + cat"></div>
                <div class="f-muted f-small" style="padding:8px 0 4px" x-show="cat === '__picked'">Products in this order — tap price to change</div>
                <template x-for="p in filtered()" :key="p.id">
                    <div class="f-product" :class="qty[p.id] > 0 && 'picked'">
                        <template x-if="p.image"><img class="f-thumb" :src="p.image" alt="" loading="lazy"></template>
                        <template x-if="!p.image"><div class="f-thumb"><i data-lucide="package"></i></div></template>
                        <div class="f-product-info">
                            <div class="f-product-name" x-text="p.name"></div>
                            <div class="f-muted f-small" x-show="!cat || cat === ''" x-text="p.category"></div>
                            <div class="f-product-price" x-show="!qty[p.id]" x-text="taka(p.price)"></div>
                            <label class="f-price-edit" x-show="qty[p.id] > 0" x-cloak>
                                <span>৳</span>
                                <input type="number" inputmode="decimal" step="0.01" min="0" :value="sellPrice(p)" @change="setPrice(p.id, $event.target.value)" :aria-label="'Price of ' + p.name">
                            </label>
                            <div class="f-muted f-small" x-show="qty[p.id] > 0 && changed(p) && !belowMin(p)" x-text="'List ' + taka(p.price)"></div>
                            <div class="f-stock low" x-show="qty[p.id] > 0 && belowMin(p)" x-text="'Cannot sell below ' + taka(p.min_price)"></div>
                            <div class="f-stock" :class="p.stock <= 0 ? 'out' : (p.stock <= 10 ? 'low' : 'ok')"
                                 x-text="p.stock <= 0 ? 'Out of stock' : (p.stock <= 10 ? 'Only ' + p.stock + ' left' : p.stock + ' in stock')"></div>
                        </div>
                        <template x-if="!qty[p.id]">
                            <button type="button" class="f-add" @click="inc(p.id)" :aria-label="'Add ' + p.name">Add</button>
                        </template>
                        <template x-if="qty[p.id] > 0">
                            <div class="f-stepper">
                                <button type="button" @click="dec(p.id)" :aria-label="'Less ' + p.name"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 12h14"/></svg></button>
                                <input type="number" inputmode="numeric" min="0" :value="qty[p.id]" @change="set(p.id, $event.target.value)" @focus="$event.target.select()" :aria-label="'Quantity of ' + p.name">
                                <button type="button" @click="inc(p.id)" :aria-label="'More ' + p.name"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></button>
                            </div>
                        </template>
                    </div>
                </template>
                <div x-show="filtered().length === 0" class="f-muted" style="padding:24px 0;text-align:center">No products match your search.</div>
            </div>

            <button type="button" class="f-btn f-btn-ghost f-btn-block f-mobile-only" @click="endSheet = true" style="margin-top:4px">
                <i data-lucide="door-open"></i> End visit without order
            </button>
          </div>

          <aside class="f-visit-side">
            <div class="f-card f-summary">
                <div class="f-summary-head">
                    <h3>Order summary</h3>
                    <span class="f-chip" x-text="count() ? count() + ' pcs' : 'Empty'"></span>
                </div>
                <div class="f-summary-lines">
                    <template x-for="line in lines()" :key="line.id">
                        <div class="f-line-item">
                            <span><span x-text="line.name"></span> <span class="f-muted" x-text="'× ' + line.qty + ' · ' + taka(line.sell)"></span></span>
                            <span x-text="taka(line.qty * line.sell)"></span>
                        </div>
                    </template>
                    <div class="f-muted f-small" x-show="count() === 0" style="padding:18px 0;text-align:center">Click <b>Add</b> on a product to start the order.</div>
                </div>
                <div class="f-total-row"><span>Total</span><strong x-text="taka(total())"></strong></div>
                <div class="f-warn" x-show="hasBelowMin()">A selling price is below the product minimum. Raise it before sending.</div>
                <div class="f-warn" x-show="total() > credit">Over the shop's credit by <b x-text="taka(total() - credit)"></b>. The office may ask for an advance.</div>
                <button type="button" class="f-btn f-btn-primary f-btn-lg f-btn-block" style="margin-top:14px" :disabled="count() === 0 || hasBelowMin()" @click="reviewSheet = true">Review &amp; send order</button>
                <button type="button" class="f-btn f-btn-ghost f-btn-block" style="margin-top:8px" @click="endSheet = true"><i data-lucide="door-open"></i> End visit without order</button>
            </div>
          </aside>
          </div>

            {{-- Sticky running total --}}
            <div class="f-cartbar" role="region" aria-label="Order total">
                <div class="f-cartbar-text">
                    <small x-text="count() ? lines().length + ' products · ' + count() + ' pcs' : 'No products added yet'"></small>
                    <strong x-text="taka(total())"></strong>
                </div>
                <button type="button" class="f-btn f-btn-primary" :disabled="count() === 0" @click="reviewSheet = true">Review order</button>
            </div>

            {{-- Review & submit --}}
            <div class="f-sheet-backdrop" x-show="reviewSheet" x-transition.opacity @click="reviewSheet = false"></div>
            <form class="f-sheet" x-show="reviewSheet" x-transition method="post" action="{{ route('field.visit.order', $visit) }}" @submit="submitting = true" @keydown.escape.window="reviewSheet = false">
                @csrf
                <div class="f-sheet-grip"></div>
                <h3>Review order</h3>
                <div class="f-muted f-small" style="margin-bottom:6px">{{ $shop?->name }}</div>
                <template x-for="(line, i) in lines()" :key="line.id">
                    <div class="f-line-item">
                        <span>
                            <span x-text="line.name"></span>
                            <span class="f-muted" x-text="' × ' + line.qty + ' · ' + taka(line.sell)"></span>
                            <span class="f-muted" x-show="line.changed" x-text="' · list ' + taka(line.price)"></span>
                            <span class="f-stock low" x-show="line.qty > line.stock" x-text="' · only ' + Math.max(0, line.stock) + ' in stock'"></span>
                        </span>
                        <span x-text="taka(line.qty * line.sell)"></span>
                        <input type="hidden" :name="'items[' + i + '][product_id]'" :value="line.id">
                        <input type="hidden" :name="'items[' + i + '][quantity]'" :value="line.qty">
                        <input type="hidden" :name="'items[' + i + '][unit_price]'" :value="line.sell">
                    </div>
                </template>
                <div class="f-total-row"><span>Total</span><strong x-text="taka(total())"></strong></div>
                <div class="f-warn" x-show="hasBelowMin()">Raise every selling price to at least the product minimum before sending.</div>
                <div class="f-warn" x-show="total() > credit">
                    This is <b x-text="taka(total() - credit)"></b> over the shop's credit limit. Collect some cash now if the owner can pay.
                </div>

                <div class="f-baki" style="margin-top:12px">
                    <div class="f-label">Payment at shop</div>
                    <p class="f-muted f-small" style="margin:0 0 8px">Owner can pay some now; rest stays as <b>baki</b> (credit).</p>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px">
                        <button type="button" class="f-filter" :class="Number(paidNow) === 0 && 'active'" @click="paidNow = 0">Full baki</button>
                        <button type="button" class="f-filter" @click="paidNow = Math.round(total() / 2)">Half now</button>
                        <button type="button" class="f-filter" :class="Number(paidNow) >= total() && total() > 0 && 'active'" @click="paidNow = Math.round(total())">Pay all</button>
                    </div>
                    <label class="f-label" for="paid-now">Collected now (৳)</label>
                    <input id="paid-now" class="f-input" type="number" inputmode="decimal" min="0" step="1" name="paid_now" x-model.number="paidNow" :max="Math.round(total())">
                    <div class="f-kv" style="margin-top:8px">
                        <span>Baki (later)</span>
                        <strong x-text="taka(baki())"></strong>
                    </div>
                    <template x-if="Number(paidNow) > 0">
                        <div style="margin-top:8px">
                            <label class="f-label" for="pay-method">How did they pay?</label>
                            <select id="pay-method" class="f-input" name="payment_method" x-model="payMethod">
                                <option value="cash">Cash</option>
                                <option value="mobile_banking">bKash / Nagad / Rocket</option>
                                <option value="bank_transfer">Bank transfer</option>
                            </select>
                            <label class="f-label" for="pay-ref" style="margin-top:8px">Reference (optional)</label>
                            <input id="pay-ref" class="f-input" type="text" name="payment_reference" maxlength="120" placeholder="Txn id or note">
                        </div>
                    </template>
                </div>

                <label class="f-label" for="order-notes" style="margin-top:12px">Note for the office (optional)</label>
                <textarea id="order-notes" class="f-textarea" name="notes" rows="2" maxlength="1000" placeholder="e.g. deliver before Friday"></textarea>
                <button class="f-btn f-btn-success f-btn-lg f-btn-block" style="margin-top:14px" type="submit" :disabled="submitting || count() === 0 || hasBelowMin() || paidNow > total() + 0.01">
                    <span x-text="submitting ? 'Sending…' : (Number(paidNow) > 0 ? 'Send order · collect ' + taka(paidNow) : 'Send order · full baki')"></span>
                </button>
                <button class="f-btn f-btn-ghost f-btn-block" style="margin-top:8px" type="button" @click="reviewSheet = false">Keep adding products</button>
            </form>

            {{-- End visit without order --}}
            <div class="f-sheet-backdrop" x-show="endSheet" x-transition.opacity @click="endSheet = false"></div>
            <form class="f-sheet" x-show="endSheet" x-transition method="post" action="{{ route('field.visit.checkout', $visit) }}" @keydown.escape.window="endSheet = false">
                @csrf
                <div class="f-sheet-grip"></div>
                <h3>End visit</h3>
                <div class="f-muted f-small" style="margin-bottom:12px">What happened at {{ $shop?->name }}?</div>
                <div class="f-choices">
                    <label class="f-choice"><input type="radio" name="outcome" value="no_order" checked><span><i data-lucide="x-circle"></i>No order today</span></label>
                    <label class="f-choice"><input type="radio" name="outcome" value="closed"><span><i data-lucide="lock"></i>Shop closed</span></label>
                    <label class="f-choice"><input type="radio" name="outcome" value="follow_up"><span><i data-lucide="calendar-clock"></i>Follow up later</span></label>
                </div>
                <label class="f-label" for="visit-notes">Note (optional)</label>
                <textarea id="visit-notes" class="f-textarea" name="notes" rows="2" maxlength="1000" placeholder="e.g. owner asked to come back next week"></textarea>
                <button class="f-btn f-btn-primary f-btn-lg f-btn-block" style="margin-top:14px" type="submit">End visit</button>
                <button class="f-btn f-btn-ghost f-btn-block" style="margin-top:8px" type="button" @click="endSheet = false">Back to order</button>
            </form>
        </div>
    @endif
@endsection

@if ($taking)
@push('scripts')
<script>
function fieldOrder(catalog, credit) {
    return {
        catalog, credit,
        q: '', cat: '', view: 'categories',
        qty: {}, prices: {},
        paidNow: 0, payMethod: 'cash',
        reviewSheet: false, endSheet: false, submitting: false,
        taka(n) { return '৳ ' + Math.round(n || 0).toLocaleString('en-IN'); },
        sellPrice(p) {
            const raw = this.prices[p.id];
            if (raw === undefined || raw === null || raw === '') return Number(p.price) || 0;
            return Number(raw);
        },
        setPrice(id, v) {
            const n = parseFloat(v);
            this.prices[id] = isNaN(n) || n < 0 ? 0 : Math.round(n * 100) / 100;
        },
        changed(p) {
            return Math.abs(this.sellPrice(p) - Number(p.price || 0)) >= 0.005;
        },
        belowMin(p) {
            const min = Number(p.min_price || 0);
            return min > 0 && this.sellPrice(p) + 0.001 < min;
        },
        hasBelowMin() {
            return this.lines().some((line) => this.belowMin(line));
        },
        categoryCards() {
            const map = {};
            this.catalog.forEach((p) => {
                const name = p.category || 'Other';
                map[name] = (map[name] || 0) + 1;
            });
            return Object.keys(map).sort().map((name) => ({ name, count: map[name] }));
        },
        openCategory(name) {
            this.cat = name;
            this.q = '';
            this.view = 'products';
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
        showAllProducts() {
            this.cat = '';
            this.q = '';
            this.view = 'products';
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
        showPicked() {
            this.cat = '__picked';
            this.q = '';
            this.view = 'products';
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
        backToCategories() {
            this.cat = '';
            this.q = '';
            this.view = 'categories';
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
        onSearch() {
            if (this.q.trim()) this.view = 'products';
        },
        filtered() {
            const q = this.q.trim().toLowerCase();
            return this.catalog.filter((p) => {
                if (this.cat === '__picked' && !(this.qty[p.id] > 0)) return false;
                if (!q && this.cat && this.cat !== '__picked' && p.category !== this.cat) return false;
                return !q || p.name.toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q);
            });
        },
        inc(id) { this.qty[id] = (this.qty[id] || 0) + 1; },
        dec(id) { this.qty[id] = Math.max(0, (this.qty[id] || 0) - 1); },
        set(id, v) { this.qty[id] = Math.max(0, Math.min(100000, parseInt(v, 10) || 0)); },
        lines() {
            return this.catalog.filter((p) => this.qty[p.id] > 0).map((p) => {
                const sell = this.sellPrice(p);
                return { ...p, qty: this.qty[p.id], sell, changed: Math.abs(sell - Number(p.price || 0)) >= 0.005 };
            });
        },
        count() { return Object.values(this.qty).reduce((a, b) => a + (b || 0), 0); },
        total() { return this.lines().reduce((sum, l) => sum + l.qty * l.sell, 0); },
        baki() { return Math.max(0, this.total() - (Number(this.paidNow) || 0)); },
        init() {
            this.$watch('reviewSheet', (open) => { document.body.style.overflow = open ? 'hidden' : ''; });
            this.$watch('endSheet', (open) => { document.body.style.overflow = open ? 'hidden' : ''; });
            this.$nextTick(() => window.lucide && lucide.createIcons());
            this.$watch('view', () => this.$nextTick(() => window.lucide && lucide.createIcons()));
            this.$watch('cat', () => this.$nextTick(() => window.lucide && lucide.createIcons()));
        },
    };
}
</script>
@endpush
@endif
