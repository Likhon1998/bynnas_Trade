@extends('layouts.app')
@section('title', 'Orders')
@section('content')
    @php
        $statusFlash = session('status_flash');
    @endphp
    <div
        x-data="orderBoard(@js($rows), @js($previews), @js(url('/admin/orders')), @js($initialFilters), @js($statusFlash))"
        @keydown.escape.window="close()"
    >
        <x-page-header title="Orders" subtitle="Super Admin audit, approval and stock reservation" action="{{ route('orders.create') }}" action-label="Create Order">
            <x-slot:description>Filters update instantly. Click View to approve or reject without leaving this page.</x-slot:description>
            @if ($pendingCount > 0)
                <button type="button" class="btn btn-primary" @click="setAuditQueue()">Audit queue ({{ $pendingCount }})</button>
            @endif
        </x-page-header>

        @if (session('success'))
            <div class="status-flash status-flash--{{ $statusFlash['result'] ?? 'updated' }}" role="status">
                <div class="status-flash-main">{{ session('success') }}</div>
                @if (!empty($statusFlash['status']))
                    <div class="status-flash-meta">
                        New status
                        <span class="badge badge-{{ ($statusFlash['result'] ?? '') === 'rejected' ? 'out' : (($statusFlash['result'] ?? '') === 'approved' ? 'approved' : 'hold') }}">
                            {{ $statusFlash['status'] }}
                        </span>
                    </div>
                @endif
            </div>
        @endif
        @if ($errors->any())
            <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">
                <ul style="margin:0;padding-left:18px">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card" style="padding:14px;margin-bottom:14px">
            <div class="filters">
                <input
                    class="input"
                    type="search"
                    x-model="search"
                    @input="syncUrl()"
                    placeholder="Order # or shop"
                    autocomplete="off"
                    style="min-width:200px;flex:1"
                >
                <select class="select" x-model="status" @change="syncUrl()">
                    <option value="">All statuses</option>
                    <option value="pending_audit">Pending Super Admin Audit</option>
                    <option value="approved">Approved · Reserved</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select class="select" x-model="source" @change="syncUrl()">
                    <option value="">All sources</option>
                    <option value="salesman">Salesman</option>
                    <option value="shop_portal">Shop Portal</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="button" class="btn btn-ghost" x-show="search || status || source" x-cloak @click="clearFilters()">Clear</button>
                <span class="muted" style="font-size:12px;margin-left:auto" x-text="filteredCountLabel()"></span>
            </div>
        </div>

        <div class="card">
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Shop</th>
                            <th>Source</th>
                            <th>Salesman</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th style="width:1%;white-space:nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in filteredRows()" :key="row.id">
                            <tr
                                :class="{
                                    'order-row-flash': flashId === row.id,
                                    'order-row-active': order?.id === row.id && open
                                }"
                            >
                                <td style="font-weight:700" x-text="row.number"></td>
                                <td x-text="row.shop"></td>
                                <td class="muted" x-text="row.source_label"></td>
                                <td x-text="row.salesman"></td>
                                <td x-text="row.item_count"></td>
                                <td style="font-weight:700" x-text="row.total"></td>
                                <td class="muted" x-text="row.submitted_at"></td>
                                <td>
                                    <span class="badge" :class="row.status_badge" x-text="row.status_label"></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-view" @click="view(row.id)">View</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredRows().length === 0">
                            <td colspan="9" class="muted" style="padding:24px;text-align:center">No orders match these filters.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            x-show="open"
            x-cloak
            class="order-modal-backdrop"
            x-transition:enter="om-fade"
            x-transition:enter-start="om-fade-start"
            x-transition:enter-end="om-fade-end"
            x-transition:leave="om-fade"
            x-transition:leave-start="om-fade-end"
            x-transition:leave-end="om-fade-start"
            @click.self="close()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="order-modal-title"
        >
            <div
                class="order-modal"
                @click.stop
                x-show="open"
                x-transition:enter="om-pop"
                x-transition:enter-start="om-pop-start"
                x-transition:enter-end="om-pop-end"
            >
                <div class="order-modal-head">
                    <div class="order-modal-title-block">
                        <div class="order-modal-kicker">Order review</div>
                        <div id="order-modal-title" class="order-modal-title" x-text="order?.number || '…'"></div>
                        <div class="order-modal-sub" x-show="order">
                            <span x-text="order?.shop"></span>
                            <span class="dot">·</span>
                            <span x-text="order?.shop_code"></span>
                        </div>
                    </div>
                    <div class="order-modal-head-right">
                        <span
                            class="status-pill"
                            :class="'status-pill--' + (order?.status_tone || 'muted')"
                            x-text="order?.status_label || '…'"
                        ></span>
                        <button type="button" class="icon-btn" @click="close()" aria-label="Close">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                </div>

                <div class="order-modal-body">
                    <template x-if="error">
                        <div class="order-modal-error" x-text="error"></div>
                    </template>

                    <template x-if="order">
                        <div>
                            <div class="order-check-row">
                                <div class="order-check" :class="order.snapshot.credit_ok ? 'is-ok' : 'is-bad'">
                                    <div class="order-check-label">Shop credit</div>
                                    <div class="order-check-value" x-text="order.snapshot.credit_available"></div>
                                    <div class="order-check-flag" x-text="order.snapshot.credit_ok ? 'Within limit' : 'Over limit'"></div>
                                </div>
                                <div class="order-check" :class="order.snapshot.stock_ok ? 'is-ok' : 'is-bad'">
                                    <div class="order-check-label">Stock</div>
                                    <div class="order-check-value" x-text="order.snapshot.stock_ok ? 'Ready' : 'Shortfall'"></div>
                                    <div class="order-check-flag" x-text="order.snapshot.stock_ok ? 'Can reserve' : 'Cannot approve'"></div>
                                </div>
                                <div class="order-check">
                                    <div class="order-check-label">Total</div>
                                    <div class="order-check-value" x-text="order.total"></div>
                                    <div class="order-check-flag"><span x-text="order.item_count"></span> line(s)</div>
                                </div>
                                <div class="order-check">
                                    <div class="order-check-label">Source</div>
                                    <div class="order-check-value" style="font-size:15px" x-text="order.source"></div>
                                    <div class="order-check-flag" x-text="order.salesman ? ('Salesman: ' + order.salesman) : 'No salesman'"></div>
                                </div>
                            </div>

                            <div class="order-lines-card">
                                <div class="order-lines-head">
                                    <strong>Line items</strong>
                                    <span class="muted" style="font-size:12px" x-text="order.submitted_at ? ('Submitted ' + order.submitted_at) : ''"></span>
                                </div>
                                <div class="table-wrap">
                                    <table class="data order-lines-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Qty</th>
                                                <th>Available</th>
                                                <th>Unit</th>
                                                <th>Line</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="item in order.items" :key="item.sku + '-' + item.name">
                                                <tr>
                                                    <td>
                                                        <div style="font-weight:600" x-text="item.name"></div>
                                                        <div class="muted" style="font-size:11px" x-text="item.sku"></div>
                                                    </td>
                                                    <td style="font-weight:700" x-text="item.qty"></td>
                                                    <td>
                                                        <span :class="item.ok ? 'stock-ok' : 'stock-bad'" x-text="item.available"></span>
                                                    </td>
                                                    <td x-text="item.unit"></td>
                                                    <td style="font-weight:700" x-text="item.line"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <p class="order-notes" x-show="order.notes">
                                <strong>Notes</strong>
                                <span x-text="order.notes"></span>
                            </p>

                            <div class="order-reject-banner" x-show="order.rejection_reason">
                                Rejected: <span x-text="order.rejection_reason"></span>
                            </div>

                            <div class="order-status-banner" x-show="!order.pending_audit" :class="'tone-' + order.status_tone">
                                Current status: <strong x-text="order.status_label"></strong>
                                <span x-show="order.status_tone === 'success'"> — stock reserved / in fulfilment</span>
                                <span x-show="order.status_tone === 'danger'"> — this order was rejected</span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="order-modal-foot" x-show="order && order.pending_audit && (order.can_approve || order.can_reject || order.can_delete)">
                    <div class="order-modal-foot-grid" :class="order?.can_delete && (order?.can_approve || order?.can_reject) ? 'has-delete' : ''">
                        <form method="post" :action="order?.approve_url" x-show="order?.can_approve" class="order-action-form">
                            @csrf
                            <input type="hidden" name="return_url" :value="returnUrl">
                            <label class="label">Audit notes (optional)</label>
                            <textarea class="input" name="audit_notes" rows="2" placeholder="Any credit / stock notes"></textarea>
                            <label class="credit-override" x-show="order && !order.snapshot.credit_ok">
                                <input type="checkbox" name="credit_override" value="1">
                                Override credit limit
                            </label>
                            <button class="btn btn-approve" type="submit" :disabled="order && !order.snapshot.stock_ok">
                                Approve & reserve
                            </button>
                            <div class="muted" style="font-size:11px" x-show="order && !order.snapshot.stock_ok">Fix stock before approving.</div>
                        </form>

                        <form method="post" :action="order?.reject_url" x-show="order?.can_reject" class="order-action-form is-reject">
                            @csrf
                            <input type="hidden" name="return_url" :value="returnUrl">
                            <label class="label">Rejection reason</label>
                            <textarea class="input" name="rejection_reason" rows="2" required placeholder="Why reject this order?"></textarea>
                            <button class="btn btn-reject" type="submit">Reject order</button>
                        </form>
                    </div>

                    <form
                        method="post"
                        :action="order?.delete_url"
                        x-show="order?.can_delete"
                        class="order-delete-bar"
                        @submit="if (!confirm('Delete this order before approval? This cannot be undone.')) $event.preventDefault()"
                    >
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="return_url" :value="returnUrl">
                        <div class="muted" style="font-size:12px">Still waiting for audit — you can remove this order entirely.</div>
                        <button class="btn btn-delete" type="submit">Delete order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function orderBoard(rows, previews, ordersBaseUrl, initialFilters, statusFlash) {
    return {
        rows: rows || [],
        previews: previews || {},
        search: initialFilters?.search || '',
        status: initialFilters?.status || '',
        source: initialFilters?.source || '',
        open: false,
        error: null,
        order: null,
        flashId: statusFlash?.order_id ? parseInt(statusFlash.order_id, 10) : null,
        returnUrl: (() => {
            const u = new URL(window.location.href);
            u.searchParams.delete('view');
            return u.pathname + u.search;
        })(),
        init() {
            const viewId = new URLSearchParams(window.location.search).get('view');
            if (viewId) this.view(parseInt(viewId, 10));
            if (this.flashId) {
                this.$nextTick(() => {
                    document.querySelector('.order-row-flash')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            }
        },
        filteredRows() {
            const q = (this.search || '').trim().toLowerCase();
            return this.rows.filter((row) => {
                if (this.status && row.status !== this.status) return false;
                if (this.source && row.source !== this.source) return false;
                if (!q) return true;
                return (
                    String(row.number).toLowerCase().includes(q) ||
                    String(row.shop).toLowerCase().includes(q) ||
                    String(row.shop_code).toLowerCase().includes(q) ||
                    String(row.salesman).toLowerCase().includes(q)
                );
            });
        },
        filteredCountLabel() {
            const n = this.filteredRows().length;
            const total = this.rows.length;
            return n === total ? `${total} order${total === 1 ? '' : 's'}` : `Showing ${n} of ${total}`;
        },
        setAuditQueue() {
            this.search = '';
            this.source = '';
            this.status = 'pending_audit';
            this.syncUrl();
        },
        clearFilters() {
            this.search = '';
            this.status = '';
            this.source = '';
            this.syncUrl();
        },
        syncUrl() {
            const u = new URL(window.location.href);
            const setOrDel = (key, val) => {
                if (val) u.searchParams.set(key, val);
                else u.searchParams.delete(key);
            };
            setOrDel('search', (this.search || '').trim());
            setOrDel('status', this.status);
            setOrDel('source', this.source);
            if (this.status === 'pending_audit') u.searchParams.set('audit_queue', '1');
            else u.searchParams.delete('audit_queue');
            u.searchParams.delete('view');
            history.replaceState({}, '', u.pathname + u.search);
            this.returnUrl = u.pathname + u.search;
        },
        async view(id) {
            this.open = true;
            this.error = null;
            document.body.style.overflow = 'hidden';

            const cached = this.previews[id] || this.previews[String(id)];
            if (cached) {
                this.order = cached;
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                return;
            }

            this.order = null;
            try {
                const url = `${ordersBaseUrl.replace(/\/$/, '')}/${id}/preview`;
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Could not load this order.');
                this.order = await res.json();
                this.previews[id] = this.order;
            } catch (e) {
                this.error = e.message || 'Failed to load order.';
            } finally {
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
            }
        },
        close() {
            this.open = false;
            this.order = null;
            this.error = null;
            document.body.style.overflow = '';
        },
    };
}
</script>
@endpush
