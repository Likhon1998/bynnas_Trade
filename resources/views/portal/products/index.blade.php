@extends('portal.layouts.app')
@section('title', 'Shop')
@section('content')
<div
    class="ecom"
    x-data="portalShop({
        catalog: @js($catalog),
        categories: @js($categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()),
        posters: @js($posters),
        shopId: {{ (int) $shop->id }},
        shopName: @js($shop->name),
        creditLabel: @js(\App\Support\DemoData::taka($credit)),
        storeUrl: @js($storeUrl),
        csrf: @js(csrf_token()),
        search: @js($initialSearch),
        categoryId: @js($initialCategory),
    })"
>
    @if ($errors->any())
        <div class="ecom-flash is-error">{{ $errors->first() }}</div>
    @endif
    @if (session('success'))
        <div class="ecom-flash is-ok">{{ session('success') }}</div>
    @endif

    {{-- Poster hero --}}
    <section class="ecom-hero">
        <template x-for="(poster, i) in posters" :key="i">
            <button
                type="button"
                class="ecom-poster"
                :class="['tone-' + poster.tone, posterIndex === i && 'is-active']"
                x-show="posterIndex === i"
                x-cloak
                @click="setFilter(poster.filter)"
            >
                <div class="ecom-poster-copy">
                    <span class="ecom-poster-eye" x-text="poster.eyebrow"></span>
                    <h1 x-text="poster.title"></h1>
                    <p x-text="poster.text"></p>
                    <span class="ecom-poster-cta" x-text="poster.cta"></span>
                </div>
            </button>
        </template>
        <div class="ecom-poster-dots">
            <template x-for="(poster, i) in posters" :key="'d'+i">
                <button type="button" class="ecom-dot" :class="posterIndex === i && 'is-active'" @click="posterIndex = i"></button>
            </template>
        </div>
    </section>

    {{-- Sticky shop bar --}}
    <div class="ecom-bar">
        <div class="ecom-tabs">
            <button type="button" class="ecom-tab" :class="filter === 'all' && 'is-active'" @click="setFilter('all')">All</button>
            <button type="button" class="ecom-tab" :class="filter === 'new' && 'is-active'" @click="setFilter('new')">New</button>
            <button type="button" class="ecom-tab" :class="filter === 'trending' && 'is-active'" @click="setFilter('trending')">Trending</button>
            <button type="button" class="ecom-tab" :class="filter === 'wishlist' && 'is-active'" @click="setFilter('wishlist')">
                Wishlist <b x-text="wishlist.length" x-show="wishlist.length"></b>
            </button>
        </div>
        <div class="ecom-bar-tools">
            <input class="input ecom-search" type="search" placeholder="Search SKU or product" x-model="search" autocomplete="off">
            <select class="select ecom-cat" x-model="categoryId">
                <option value="">Category</option>
                <template x-for="cat in categories" :key="cat.id">
                    <option :value="String(cat.id)" x-text="cat.name"></option>
                </template>
            </select>
            <button type="button" class="ecom-icon-btn" @click="wishOpen = true" title="Wishlist">
                <i data-lucide="heart" style="width:16px;height:16px"></i>
                <span x-show="wishlist.length" x-text="wishlist.length"></span>
            </button>
            <button type="button" class="ecom-icon-btn is-cart" @click="cartOpen = true" title="Cart">
                <i data-lucide="shopping-bag" style="width:16px;height:16px"></i>
                <span x-show="cartCount()" x-text="cartCount()"></span>
            </button>
        </div>
    </div>

    <div class="ecom-meta">
        <span x-text="filteredCountLabel()"></span>
        <span class="muted">Credit {{ \App\Support\DemoData::taka($credit) }}</span>
    </div>

    {{-- Product grid --}}
    <div class="ecom-grid">
        <template x-for="item in filteredCatalog()" :key="item.id">
            <article class="ecom-card" :class="!item.in_stock && 'is-oos'">
                <div class="ecom-card-media">
                    <template x-if="item.image">
                        <img :src="item.image" :alt="item.name" loading="lazy">
                    </template>
                    <template x-if="!item.image">
                        <div class="ecom-fallback" x-text="(item.name || '?').charAt(0)"></div>
                    </template>
                    <div class="ecom-badges">
                        <span class="ecom-badge is-new" x-show="item.is_new">New</span>
                        <span class="ecom-badge is-hot" x-show="item.is_trending && !item.is_new">Trending</span>
                        <span class="ecom-badge is-low" x-show="item.low_stock">Low stock</span>
                    </div>
                    <button
                        type="button"
                        class="ecom-wish"
                        :class="isWished(item.id) && 'is-on'"
                        @click="toggleWish(item)"
                        :title="isWished(item.id) ? 'Remove from wishlist' : 'Save to wishlist'"
                    >
                        <i data-lucide="heart" style="width:14px;height:14px"></i>
                    </button>
                </div>
                <div class="ecom-card-body">
                    <div class="ecom-sku" x-text="item.sku"></div>
                    <h3 x-text="item.name"></h3>
                    <div class="ecom-cat" x-text="item.category"></div>
                    <div class="ecom-price-row">
                        <strong x-text="item.price_label"></strong>
                        <span :class="item.in_stock ? 'ok' : 'no'" x-text="item.in_stock ? (item.stock + ' left') : 'Sold out'"></span>
                    </div>
                    <div class="ecom-card-foot">
                        <div class="ecom-stepper">
                            <button type="button" @click="bumpQty(item.id, -1)" :disabled="!item.in_stock">−</button>
                            <input type="number" min="1" :max="item.stock" x-model.number="qtyDraft[item.id]" :disabled="!item.in_stock">
                            <button type="button" @click="bumpQty(item.id, 1)" :disabled="!item.in_stock">+</button>
                        </div>
                        <button type="button" class="ecom-add" @click="addToCart(item)" :disabled="!item.in_stock">
                            <span x-text="inCart(item.id) ? 'Update' : 'Add'"></span>
                        </button>
                    </div>
                </div>
            </article>
        </template>
    </div>
    <div class="ecom-empty" x-show="filteredCatalog().length === 0" x-cloak>
        <template x-if="filter === 'wishlist'">
            <p>Wishlist is empty. Tap ♥ on products to save them.</p>
        </template>
        <template x-if="filter !== 'wishlist'">
            <p>No products match this view.</p>
        </template>
    </div>

    {{-- Compact floating cart pill --}}
    <button type="button" class="ecom-fab" x-show="cartCount() > 0" x-cloak @click="cartOpen = true">
        <i data-lucide="shopping-bag" style="width:16px;height:16px"></i>
        <span x-text="cartCount() + ' · ' + money(cartTotal())"></span>
    </button>

    {{-- Cart sheet (compact) --}}
    <div class="ecom-scrim" x-show="cartOpen || wishOpen" x-cloak @click="cartOpen = false; wishOpen = false"></div>
    <aside class="ecom-sheet" :class="cartOpen && 'is-open'" x-cloak>
        <div class="ecom-sheet-head">
            <strong>Cart</strong>
            <button type="button" @click="cartOpen = false">×</button>
        </div>
        <div class="ecom-sheet-body">
            <template x-if="cart.length === 0">
                <p class="ecom-empty">Cart is empty.</p>
            </template>
            <template x-for="line in cart" :key="line.id">
                <div class="ecom-line">
                    <div class="ecom-line-info">
                        <strong x-text="line.name"></strong>
                        <span x-text="line.price_label + ' · ' + line.sku"></span>
                    </div>
                    <div class="ecom-stepper ecom-stepper-xs">
                        <button type="button" @click="setLineQty(line.id, line.qty - 1)">−</button>
                        <span x-text="line.qty"></span>
                        <button type="button" @click="setLineQty(line.id, line.qty + 1)">+</button>
                    </div>
                    <strong class="ecom-line-sum" x-text="money(line.price * line.qty)"></strong>
                    <button type="button" class="ecom-x" @click="removeLine(line.id)">×</button>
                </div>
            </template>
        </div>
        <div class="ecom-sheet-foot" x-show="cart.length">
            <div class="ecom-total"><span>Total</span><strong x-text="money(cartTotal())"></strong></div>
            <textarea class="textarea" rows="2" x-model="notes" placeholder="Optional note"></textarea>
            <button type="button" class="btn btn-primary ecom-checkout" :disabled="submitting" @click="checkout()" x-text="submitting ? 'Sending…' : 'Place order'"></button>
        </div>
    </aside>

    {{-- Wishlist sheet --}}
    <aside class="ecom-sheet" :class="wishOpen && 'is-open'" x-cloak>
        <div class="ecom-sheet-head">
            <strong>Wishlist</strong>
            <button type="button" @click="wishOpen = false">×</button>
        </div>
        <div class="ecom-sheet-body">
            <template x-if="wishlistItems().length === 0">
                <p class="ecom-empty">No saved items yet.</p>
            </template>
            <template x-for="item in wishlistItems()" :key="'w'+item.id">
                <div class="ecom-line">
                    <div class="ecom-line-info">
                        <strong x-text="item.name"></strong>
                        <span x-text="item.price_label"></span>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" @click="addToCart(item); wishOpen=false; cartOpen=true" :disabled="!item.in_stock">Add</button>
                    <button type="button" class="ecom-x" @click="toggleWish(item)">×</button>
                </div>
            </template>
        </div>
    </aside>
</div>
@endsection
