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
            <b>{{ $shop?->name }} is waiting for office approval.</b> Orders open once the office sets its prices and credit. Record how this visit went.
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
                <input type="search" x-model="q" placeholder="Search product or SKU" autocomplete="off" aria-label="Search products">
            </div>
            @if ($categories->count() > 1)
                <div class="f-filters">
                    <button type="button" class="f-filter" :class="cat === '' && 'active'" @click="cat = ''">All</button>
                    @foreach ($categories as $category)
                        <button type="button" class="f-filter" :class="cat === @js($category) && 'active'" @click="cat = @js($category)">{{ $category }}</button>
                    @endforeach
                    <button type="button" class="f-filter" :class="cat === '__picked' && 'active'" @click="cat = '__picked'" x-show="count() > 0" x-text="'In order (' + lines().length + ')'"></button>
                </div>
            @endif
            </div>

            <div class="f-card f-products" style="padding:4px 14px">
                <template x-for="p in filtered()" :key="p.id">
                    <div class="f-product" :class="qty[p.id] > 0 && 'picked'">
                        <template x-if="p.image"><img class="f-thumb" :src="p.image" alt="" loading="lazy"></template>
                        <template x-if="!p.image"><div class="f-thumb"><i data-lucide="package"></i></div></template>
                        <div class="f-product-info">
                            <div class="f-product-name" x-text="p.name"></div>
                            <div class="f-product-price" x-text="taka(p.price)"></div>
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
                            <span><span x-text="line.name"></span> <span class="f-muted" x-text="'× ' + line.qty"></span></span>
                            <span x-text="taka(line.qty * line.price)"></span>
                        </div>
                    </template>
                    <div class="f-muted f-small" x-show="count() === 0" style="padding:18px 0;text-align:center">Click <b>Add</b> on a product to start the order.</div>
                </div>
                <div class="f-total-row"><span>Total</span><strong x-text="taka(total())"></strong></div>
                <div class="f-warn" x-show="total() > credit">Over the shop's credit by <b x-text="taka(total() - credit)"></b>. The office may ask for an advance.</div>
                <button type="button" class="f-btn f-btn-primary f-btn-lg f-btn-block" style="margin-top:14px" :disabled="count() === 0" @click="reviewSheet = true">Review &amp; send order</button>
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
                            <span class="f-muted" x-text="' × ' + line.qty"></span>
                            <span class="f-stock low" x-show="line.qty > line.stock" x-text="' · only ' + Math.max(0, line.stock) + ' in stock'"></span>
                        </span>
                        <span x-text="taka(line.qty * line.price)"></span>
                        <input type="hidden" :name="'items[' + i + '][product_id]'" :value="line.id">
                        <input type="hidden" :name="'items[' + i + '][quantity]'" :value="line.qty">
                    </div>
                </template>
                <div class="f-total-row"><span>Total</span><strong x-text="taka(total())"></strong></div>
                <div class="f-warn" x-show="total() > credit">
                    This is <b x-text="taka(total() - credit)"></b> over the shop's credit limit. The office may ask for an advance payment before approving.
                </div>
                <label class="f-label" for="order-notes">Note for the office (optional)</label>
                <textarea id="order-notes" class="f-textarea" name="notes" rows="2" maxlength="1000" placeholder="e.g. deliver before Friday"></textarea>
                <button class="f-btn f-btn-success f-btn-lg f-btn-block" style="margin-top:14px" type="submit" :disabled="submitting || count() === 0">
                    <span x-text="submitting ? 'Sending…' : 'Send order for approval'"></span>
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
        q: '', cat: '', qty: {},
        reviewSheet: false, endSheet: false, submitting: false,
        taka(n) { return '৳ ' + Math.round(n || 0).toLocaleString('en-IN'); },
        filtered() {
            const q = this.q.trim().toLowerCase();
            return this.catalog.filter((p) => {
                if (this.cat === '__picked' && !(this.qty[p.id] > 0)) return false;
                if (this.cat && this.cat !== '__picked' && p.category !== this.cat) return false;
                return !q || p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q);
            });
        },
        inc(id) { this.qty[id] = (this.qty[id] || 0) + 1; },
        dec(id) { this.qty[id] = Math.max(0, (this.qty[id] || 0) - 1); },
        set(id, v) { this.qty[id] = Math.max(0, Math.min(100000, parseInt(v, 10) || 0)); },
        lines() {
            return this.catalog.filter((p) => this.qty[p.id] > 0).map((p) => ({ ...p, qty: this.qty[p.id] }));
        },
        count() { return Object.values(this.qty).reduce((a, b) => a + (b || 0), 0); },
        total() { return this.lines().reduce((sum, l) => sum + l.qty * l.price, 0); },
        init() {
            this.$watch('reviewSheet', (open) => { document.body.style.overflow = open ? 'hidden' : ''; });
            this.$watch('endSheet', (open) => { document.body.style.overflow = open ? 'hidden' : ''; });
            this.$nextTick(() => window.lucide && lucide.createIcons());
            this.$watch('q', () => this.$nextTick(() => window.lucide && lucide.createIcons()));
            this.$watch('cat', () => this.$nextTick(() => window.lucide && lucide.createIcons()));
        },
    };
}
</script>
@endpush
@endif
