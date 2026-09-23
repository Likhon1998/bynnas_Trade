function portalShop(cfg) {
    const cartKey = 'bt_portal_cart_' + cfg.shopId;
    const wishKey = 'bt_portal_wish_' + cfg.shopId;
    const loadJson = (k, fallback) => {
        try { return JSON.parse(localStorage.getItem(k) || 'null') ?? fallback; }
        catch (e) { return fallback; }
    };
    return {
        catalog: cfg.catalog || [],
        categories: cfg.categories || [],
        posters: cfg.posters || [],
        posterIndex: 0,
        filter: 'all',
        search: cfg.search || '',
        categoryId: cfg.categoryId ? String(cfg.categoryId) : '',
        cart: loadJson(cartKey, []),
        wishlist: loadJson(wishKey, []),
        cartOpen: false,
        wishOpen: false,
        notes: '',
        submitting: false,
        qtyDraft: {},
        creditLabel: cfg.creditLabel,
        storeUrl: cfg.storeUrl,
        csrf: cfg.csrf,
        posterTimer: null,
        init() {
            const draft = {};
            (this.catalog || []).forEach((p) => {
                const line = this.cart.find((c) => c.id === p.id);
                draft[p.id] = line ? line.qty : 1;
            });
            this.qtyDraft = draft;
            this.posterTimer = setInterval(() => {
                if (this.posters.length > 1) {
                    this.posterIndex = (this.posterIndex + 1) % this.posters.length;
                }
            }, 5200);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        setFilter(f) {
            this.filter = f || 'all';
            this.wishOpen = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        filteredCatalog() {
            const q = (this.search || '').trim().toLowerCase();
            const cat = this.categoryId ? String(this.categoryId) : '';
            return this.catalog.filter((p) => {
                if (this.filter === 'new' && !p.is_new) return false;
                if (this.filter === 'trending' && !p.is_trending) return false;
                if (this.filter === 'wishlist' && !this.isWished(p.id)) return false;
                if (cat && String(p.category_id) !== cat) return false;
                if (!q) return true;
                return String(p.name).toLowerCase().includes(q)
                    || String(p.sku).toLowerCase().includes(q)
                    || String(p.category).toLowerCase().includes(q);
            });
        },
        filteredCountLabel() {
            const n = this.filteredCatalog().length;
            return n + ' item' + (n === 1 ? '' : 's');
        },
        persistCart() { localStorage.setItem(cartKey, JSON.stringify(this.cart)); },
        persistWish() { localStorage.setItem(wishKey, JSON.stringify(this.wishlist)); },
        isWished(id) { return this.wishlist.includes(id); },
        toggleWish(item) {
            if (this.isWished(item.id)) this.wishlist = this.wishlist.filter((id) => id !== item.id);
            else this.wishlist.push(item.id);
            this.persistWish();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        wishlistItems() {
            return this.catalog.filter((p) => this.isWished(p.id));
        },
        inCart(id) { return this.cart.some((c) => c.id === id); },
        bumpQty(id, delta) {
            const p = this.catalog.find((x) => x.id === id);
            if (!p) return;
            let v = parseInt(this.qtyDraft[id] || 1, 10) + delta;
            if (isNaN(v) || v < 1) v = 1;
            if (v > p.stock) v = p.stock;
            this.qtyDraft[id] = v;
        },
        addToCart(item) {
            if (!item.in_stock) return;
            let qty = parseInt(this.qtyDraft[item.id] || 1, 10);
            if (isNaN(qty) || qty < 1) qty = 1;
            if (qty > item.stock) qty = item.stock;
            const idx = this.cart.findIndex((c) => c.id === item.id);
            const line = {
                id: item.id, name: item.name, sku: item.sku,
                price: item.price, price_label: item.price_label, stock: item.stock, qty,
            };
            if (idx >= 0) this.cart[idx] = line; else this.cart.push(line);
            this.persistCart();
            this.cartOpen = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },
        setLineQty(id, qty) {
            const line = this.cart.find((c) => c.id === id);
            if (!line) return;
            if (qty <= 0) { this.removeLine(id); return; }
            line.qty = Math.min(line.stock, qty);
            this.qtyDraft[id] = line.qty;
            this.persistCart();
        },
        removeLine(id) {
            this.cart = this.cart.filter((c) => c.id !== id);
            this.persistCart();
        },
        cartCount() { return this.cart.reduce((s, l) => s + (l.qty || 0), 0); },
        cartTotal() { return this.cart.reduce((s, l) => s + (l.price * l.qty), 0); },
        money(n) {
            return '৳ ' + Number(n || 0).toLocaleString('en-BD', { maximumFractionDigits: 0 });
        },
        checkout() {
            if (!this.cart.length || this.submitting) return;
            this.submitting = true;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = this.storeUrl;
            const token = document.createElement('input');
            token.type = 'hidden'; token.name = '_token'; token.value = this.csrf;
            form.appendChild(token);
            if (this.notes) {
                const notes = document.createElement('input');
                notes.type = 'hidden'; notes.name = 'notes'; notes.value = this.notes;
                form.appendChild(notes);
            }
            this.cart.forEach((line, i) => {
                const pid = document.createElement('input');
                pid.type = 'hidden'; pid.name = 'items[' + i + '][product_id]'; pid.value = line.id;
                form.appendChild(pid);
                const qty = document.createElement('input');
                qty.type = 'hidden'; qty.name = 'items[' + i + '][quantity]'; qty.value = line.qty;
                form.appendChild(qty);
            });
            document.body.appendChild(form);
            localStorage.removeItem(cartKey);
            form.submit();
        },
    };
}
