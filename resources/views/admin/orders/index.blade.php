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
                    <option value="awaiting_advance">Awaiting Advance</option>
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
                        <div id="order-modal-title" class="order-modal-title" x-text="order?.number || 'Loading…'"></div>
                        <div class="order-modal-sub" x-show="order">
                            <span x-text="order?.shop"></span>
                            <span class="dot">·</span>
                            <span x-text="order?.shop_code"></span>
                            <span class="dot">·</span>
                            <span x-text="order?.source"></span>
                            <template x-if="order?.salesman">
                                <span> · <span x-text="order.salesman"></span></span>
                            </template>
                            <template x-if="order?.submitted_at">
                                <span> · <span x-text="order.submitted_at"></span></span>
                            </template>
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

                <div class="order-modal-error" x-show="error" x-text="error"></div>
                <div class="order-modal-loading" x-show="!order && !error">Loading order…</div>

                <div class="order-modal-main" :class="order ? 'is-open' : ''">
                    {{-- PRODUCTS FIRST — always the primary visible pane --}}
                    <section class="order-products-pane">
                        <div class="order-products-head">
                            <strong>Order items</strong>
                            <span x-text="(order?.item_count || 0) + ' line(s) · ' + (order?.total || '')"></span>
                        </div>
                        <div class="order-products-body">
                            <table class="data order-lines-table" :class="{ 'is-empty': !(order?.items?.length) }">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="num">Qty</th>
                                        <th class="num">Stock</th>
                                        <th class="num">Unit</th>
                                        <th class="num">Line</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="item in (order?.items || [])" :key="item.sku + '-' + item.name">
                                        <tr>
                                            <td>
                                                <div class="order-item-name" x-text="item.name"></div>
                                                <div class="muted" style="font-size:11px" x-text="item.sku"></div>
                                            </td>
                                            <td class="num" style="font-weight:800" x-text="item.qty"></td>
                                            <td class="num">
                                                <span :class="item.ok ? 'stock-ok' : 'stock-bad'" x-text="item.available"></span>
                                            </td>
                                            <td class="num" x-text="item.unit"></td>
                                            <td class="num" style="font-weight:700" x-text="item.line"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <div class="order-products-empty" x-show="order && !order.items?.length">No line items on this order.</div>
                        </div>
                        <p class="order-notes" x-show="order?.notes">
                            <strong>Shop notes</strong>
                            <span x-text="order.notes"></span>
                        </p>
                    </section>

                    {{-- Side panel: signals + decide --}}
                    <aside class="order-side-pane">
                        <div class="order-side-signals">
                            <div class="order-side-signal" :class="order?.snapshot?.credit_ok ? 'ok' : 'bad'">
                                <span>Credit</span>
                                <strong x-text="order?.snapshot?.credit_available || '—'"></strong>
                                <em x-text="order?.snapshot?.credit_ok ? 'Within limit' : 'Over limit'"></em>
                            </div>
                            <div class="order-side-signal" :class="order?.snapshot?.stock_ok ? 'ok' : 'bad'">
                                <span>Stock</span>
                                <strong x-text="order?.snapshot?.stock_ok ? 'Ready' : 'Short'"></strong>
                                <em x-text="order?.snapshot?.stock_ok ? 'Can reserve' : 'Cannot approve'"></em>
                            </div>
                        </div>

                        <div class="order-reject-banner" x-show="order?.rejection_reason">
                            Rejected: <span x-text="order.rejection_reason"></span>
                        </div>

                        <div class="order-status-banner" x-show="order && !order.pending_audit" :class="'tone-' + order.status_tone">
                            Status: <strong x-text="order.status_label"></strong>
                        </div>

                        <div class="order-advance-box" x-show="order?.awaiting_advance">
                            <strong x-show="!order?.advance_paid">Advance due</strong>
                            <strong x-show="order?.advance_paid" style="color:#15803d">Advance paid</strong>
                            <div class="order-advance-amount" x-text="order?.advance_amount"></div>
                            <p class="muted" style="margin:4px 0 0;font-size:12px" x-show="!order?.advance_paid">
                                Balance <span x-text="order?.advance_balance"></span>
                                · Invoice <span x-text="order?.advance_invoice_number"></span>
                            </p>
                            <p class="muted" style="margin:4px 0 0;font-size:12px" x-show="order?.advance_paid">
                                System updated automatically. You can approve &amp; reserve now.
                            </p>

                            <form
                                method="post"
                                :action="order?.collect_advance_url"
                                class="order-decide-panel"
                                style="margin-top:10px;gap:8px"
                                x-show="!order?.advance_paid && order?.collect_advance_url"
                            >
                                @csrf
                                <input type="hidden" name="return_url" :value="returnUrl">
                                <label class="order-field">
                                    <span class="order-field-label">Amount received</span>
                                    <input class="input" type="number" name="amount" step="0.01" min="0.01" :value="order?.advance_balance_raw" required>
                                </label>
                                <label class="order-field">
                                    <span class="order-field-label">Method</span>
                                    <select class="select" name="method" required>
                                        <option value="cash">Cash</option>
                                        <option value="bank_transfer">Bank transfer</option>
                                        <option value="mobile_banking">Mobile banking</option>
                                        <option value="cheque">Cheque</option>
                                    </select>
                                </label>
                                <label class="order-field">
                                    <span class="order-field-label">Reference <em>(optional)</em></span>
                                    <input class="input" type="text" name="reference" placeholder="Txn / cheque no.">
                                </label>
                                <button class="btn btn-primary btn-block" type="submit">Mark advance received</button>
                            </form>
                        </div>

                        <div class="order-side-actions" x-show="order?.pending_audit && (order?.can_approve || order?.can_request_advance || order?.can_reject || order?.can_delete)">
                            <div class="order-decide-tabs" x-show="(order?.can_approve || order?.can_request_advance) && order?.can_reject && !order?.awaiting_advance">
                                <button type="button" class="order-decide-tab" :class="auditTab === 'approve' && 'is-active'" @click="auditTab = 'approve'" x-show="order?.can_approve || order?.can_request_advance">Decide</button>
                                <button type="button" class="order-decide-tab is-danger" :class="auditTab === 'reject' && 'is-active'" @click="auditTab = 'reject'">Reject</button>
                            </div>

                            {{-- Pending audit: approve now OR require advance --}}
                            <div x-show="!order?.awaiting_advance && auditTab === 'approve' && (order?.can_approve || order?.can_request_advance)" class="order-decide-panel">
                                <label class="order-field">
                                    <span class="order-field-label">Note <em>(optional)</em></span>
                                    <input class="input" type="text" x-model="auditNotes" placeholder="Warehouse / credit note">
                                </label>

                                <label class="order-override" style="border-color:#c7d2fe;background:#eef2ff;cursor:pointer">
                                    <input type="checkbox" x-model="requireAdvance">
                                    <span>
                                        <strong style="color:#3730a3">Require advance first</strong>
                                        <small style="color:#4338ca">Shop must pay before this order can be approved</small>
                                    </span>
                                </label>

                                <template x-if="requireAdvance && order?.can_request_advance">
                                    <form method="post" :action="order?.request_advance_url" class="order-decide-panel" style="gap:8px">
                                        @csrf
                                        <input type="hidden" name="return_url" :value="returnUrl">
                                        <input type="hidden" name="audit_notes" :value="auditNotes">
                                        <label class="order-field">
                                            <span class="order-field-label">Advance amount (৳)</span>
                                            <input class="input" type="number" name="advance_amount" step="0.01" min="1" :max="order?.total_raw" x-model="advanceAmount" required>
                                        </label>
                                        <button class="btn btn-primary btn-block" type="submit">Request advance</button>
                                    </form>
                                </template>

                                <template x-if="!requireAdvance && order?.can_approve">
                                    <form method="post" :action="order?.approve_url" class="order-decide-panel" style="gap:8px">
                                        @csrf
                                        <input type="hidden" name="return_url" :value="returnUrl">
                                        <input type="hidden" name="audit_notes" :value="auditNotes">
                                        <label class="order-override" x-show="order && !order.snapshot?.credit_ok">
                                            <input type="checkbox" name="credit_override" value="1">
                                            <span>
                                                <strong>Override credit</strong>
                                                <small>Allow over-limit order</small>
                                            </span>
                                        </label>
                                        <p class="order-decide-hint" x-show="order && !order.snapshot?.stock_ok">Stock shortfall — cannot approve.</p>
                                        <button class="btn btn-approve btn-block" type="submit" :disabled="order && !order.snapshot?.stock_ok">
                                            Approve &amp; reserve
                                        </button>
                                    </form>
                                </template>
                            </div>

                            {{-- Advance paid: approve --}}
                            <form method="post" :action="order?.approve_url" x-show="order?.awaiting_advance && order?.can_approve" class="order-decide-panel">
                                @csrf
                                <input type="hidden" name="return_url" :value="returnUrl">
                                <label class="order-field">
                                    <span class="order-field-label">Note <em>(optional)</em></span>
                                    <input class="input" type="text" name="audit_notes" placeholder="Warehouse note">
                                </label>
                                <label class="order-override" x-show="order && !order.snapshot?.credit_ok">
                                    <input type="checkbox" name="credit_override" value="1">
                                    <span>
                                        <strong>Override credit</strong>
                                        <small>Allow over-limit order</small>
                                    </span>
                                </label>
                                <p class="order-decide-hint" x-show="order && !order.snapshot?.stock_ok">Stock shortfall — cannot approve.</p>
                                <button class="btn btn-approve btn-block" type="submit" :disabled="order && !order.snapshot?.stock_ok">
                                    Approve &amp; reserve
                                </button>
                            </form>

                            <form method="post" :action="order?.reject_url" x-show="order?.can_reject && (auditTab === 'reject' || order?.awaiting_advance)" class="order-decide-panel">
                                @csrf
                                <input type="hidden" name="return_url" :value="returnUrl">
                                <label class="order-field">
                                    <span class="order-field-label">Rejection reason</span>
                                    <input class="input" type="text" name="rejection_reason" required placeholder="Why reject?">
                                </label>
                                <button class="btn btn-reject btn-block" type="submit">Reject order</button>
                            </form>

                            <form
                                method="post"
                                :action="order?.delete_url"
                                x-show="order?.can_delete"
                                class="order-delete-bar"
                                @submit="if (!confirm('Delete this order permanently?')) $event.preventDefault()"
                            >
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="return_url" :value="returnUrl">
                                <button class="btn-link-danger" type="submit">Delete this order</button>
                            </form>
                        </div>
                    </aside>
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
        auditTab: 'approve',
        requireAdvance: false,
        advanceAmount: '',
        auditNotes: '',
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
            this.auditTab = 'approve';
            this.requireAdvance = false;
            this.auditNotes = '';
            document.body.style.overflow = 'hidden';

            const applyOrder = (data) => {
                this.order = data;
                const suggested = data?.total_raw ? Math.round(data.total_raw * 0.3 * 100) / 100 : '';
                this.advanceAmount = data?.advance_amount_raw || suggested || '';
                if (data?.awaiting_advance && data?.can_approve) this.auditTab = 'approve';
                else if (data && !data.can_approve && !data.can_request_advance && data.can_reject) this.auditTab = 'reject';
                else this.auditTab = 'approve';
            };

            const cached = this.previews[id] || this.previews[String(id)];
            if (cached) {
                applyOrder(cached);
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
                const data = await res.json();
                this.previews[id] = data;
                applyOrder(data);
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
            this.auditTab = 'approve';
            this.requireAdvance = false;
            this.auditNotes = '';
            this.advanceAmount = '';
            document.body.style.overflow = '';
        },
    };
}
</script>
@endpush
