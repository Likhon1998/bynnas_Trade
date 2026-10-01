@extends('layouts.app')
@section('title', 'Shops')
@section('content')
    <div x-data="shopBoard(@js($rows), @js($initialFilters))">
        <x-page-header title="Shops" subtitle="Wholesale partners and portal access" action="{{ route('shops.create') }}" action-label="Add Shop">
            <x-slot:description>Filters update instantly. Approved shops receive portal credentials.</x-slot:description>
        </x-page-header>

        @if (session('success'))
            <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
        @endif

        <div class="card" style="padding:14px;margin-bottom:14px">
            <div class="filters">
                <input
                    class="input"
                    type="search"
                    x-model="search"
                    @input="syncUrl()"
                    placeholder="Search shop, owner, city or salesman"
                    autocomplete="off"
                >
                <select class="select" x-model="status" @change="syncUrl()">
                    <option value="">All statuses</option>
                    <option value="pending">Pending Approval</option>
                    <option value="active">Active</option>
                    <option value="on_hold">On Hold</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select class="select" x-model="source" @change="syncUrl()">
                    <option value="">All sources</option>
                    <option value="field">Added by salesmen</option>
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
                            <th>Shop ID</th>
                            <th>Shop</th>
                            <th>Owner</th>
                            <th>City</th>
                            <th>Price group</th>
                            <th>Salesman</th>
                            <th>Credit</th>
                            <th>Outstanding</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in filteredRows()" :key="row.id">
                            <tr>
                                <td><a class="link" :href="row.url" x-text="row.code"></a></td>
                                <td>
                                    <div style="font-weight:600" x-text="row.name"></div>
                                    <div x-show="row.added_by" class="fa-added">
                                        Added by <b x-text="row.added_by"></b> · <span x-text="row.added_on"></span>
                                    </div>
                                </td>
                                <td x-text="row.owner || '—'"></td>
                                <td x-text="row.city || '—'"></td>
                                <td x-text="row.price_group"></td>
                                <td x-text="row.salesman"></td>
                                <td x-text="row.credit"></td>
                                <td x-text="row.outstanding"></td>
                                <td><span class="badge" :class="row.status_badge" x-text="row.status_label"></span></td>
                            </tr>
                        </template>
                        <tr x-show="filteredRows().length === 0">
                            <td colspan="9" class="muted" style="padding:24px;text-align:center">No shops match these filters.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function shopBoard(rows, initialFilters) {
    return {
        rows: rows || [],
        search: initialFilters?.search || '',
        status: initialFilters?.status || '',
        source: initialFilters?.source || '',
        filteredRows() {
            const q = (this.search || '').trim().toLowerCase();
            return this.rows.filter((row) => {
                if (this.status && row.status !== this.status) return false;
                if (this.source && row.source !== this.source) return false;
                if (!q) return true;
                return (
                    String(row.code).toLowerCase().includes(q) ||
                    String(row.name).toLowerCase().includes(q) ||
                    String(row.owner).toLowerCase().includes(q) ||
                    String(row.city).toLowerCase().includes(q) ||
                    String(row.salesman).toLowerCase().includes(q) ||
                    String(row.added_by).toLowerCase().includes(q)
                );
            });
        },
        filteredCountLabel() {
            const n = this.filteredRows().length;
            const total = this.rows.length;
            return n === total ? `${total} shop${total === 1 ? '' : 's'}` : `Showing ${n} of ${total}`;
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
            history.replaceState({}, '', u.pathname + u.search);
        },
    };
}
</script>
@endpush
