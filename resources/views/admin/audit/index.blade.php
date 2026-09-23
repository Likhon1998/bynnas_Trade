@extends('layouts.app')

@section('title', 'Audit logs')

@section('content')
    @php
        $tz = config('app.timezone');
        $hasFilters = request()->hasAny(['search', 'module', 'action', 'actor', 'from', 'to']);
    @endphp

    <x-page-header title="Audit logs" subtitle="{{ number_format($stats['total']) }} events recorded">
        <x-slot:description>Full trail of who did what, when, and from where — across users, orders, payments, stock and more.</x-slot:description>
    </x-page-header>

    <div class="audit-stats">
        <div class="audit-stat">
            <span class="audit-stat__label">Today</span>
            <strong>{{ number_format($stats['today']) }}</strong>
            <em>events</em>
        </div>
        <div class="audit-stat">
            <span class="audit-stat__label">This week</span>
            <strong>{{ number_format($stats['week']) }}</strong>
            <em>events</em>
        </div>
        <div class="audit-stat">
            <span class="audit-stat__label">Active actors</span>
            <strong>{{ number_format($stats['actors']) }}</strong>
            <em>people</em>
        </div>
        <div class="audit-stat">
            <span class="audit-stat__label">Modules</span>
            <strong>{{ $modules->count() }}</strong>
            <em>areas covered</em>
        </div>
    </div>

    <div class="card audit-filters">
        <form
            id="audit-filter-form"
            class="audit-filters__form"
            method="get"
            action="{{ route('audit.index') }}"
        >
            <div class="audit-field audit-field--search">
                <label class="label">Search</label>
                <input
                    class="input"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Description, user, module, IP…"
                    autocomplete="off"
                    data-audit-search
                >
            </div>
            <div class="audit-field">
                <label class="label">Module</label>
                <select class="select" name="module" data-audit-auto>
                    <option value="">All modules</option>
                    @foreach ($modules as $module => $cnt)
                        <option value="{{ $module }}" @selected(request('module') === $module)>
                            {{ ucwords(str_replace('_', ' ', $module)) }} ({{ $cnt }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="audit-field">
                <label class="label">Action</label>
                <select class="select" name="action" data-audit-auto>
                    <option value="">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>
                            {{ ucwords(str_replace('_', ' ', $action)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="audit-field">
                <label class="label">Actor</label>
                <select class="select" name="actor" data-audit-auto>
                    <option value="">Anyone</option>
                    @foreach ($actors as $actor)
                        <option value="{{ $actor->id }}" @selected((string) request('actor') === (string) $actor->id)>
                            {{ $actor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="audit-field audit-field--date">
                <label class="label">From</label>
                <input class="input" type="date" name="from" value="{{ request('from') }}" data-audit-auto>
            </div>
            <div class="audit-field audit-field--date">
                <label class="label">To</label>
                <input class="input" type="date" name="to" value="{{ request('to') }}" data-audit-auto>
            </div>
            <div class="audit-filters__actions">
                @if ($hasFilters)
                    <a class="btn btn-ghost" href="{{ route('audit.index') }}">Reset</a>
                @else
                    <span class="audit-live-hint">Updates live</span>
                @endif
            </div>
        </form>
    </div>

    <div class="card audit-table-card" x-data="{ open: null }">
        <div class="audit-table-wrap">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th class="audit-col-when">When</th>
                        <th class="audit-col-actor">Actor</th>
                        <th class="audit-col-event">Event</th>
                        <th class="audit-col-detail">What happened</th>
                        <th class="audit-col-meta">Source</th>
                        <th class="audit-col-more"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $when = $log->created_at?->timezone($tz);
                            $tone = $log->actionTone();
                            $rowId = 'audit-'.$log->id;
                            $changeCount = count($log->changeRows());
                        @endphp
                        <tr class="audit-row" :class="{ 'is-open': open === '{{ $rowId }}' }">
                            <td class="audit-col-when">
                                <div class="audit-stamp">
                                    <span class="audit-stamp__day">{{ $when?->format('D') }}</span>
                                    <span class="audit-stamp__date">{{ $when?->format('d M Y') }}</span>
                                    <span class="audit-stamp__time">{{ $when?->format('h:i A') }}</span>
                                </div>
                            </td>
                            <td class="audit-col-actor">
                                <div class="audit-actor">
                                    <span class="audit-actor__avatar" aria-hidden="true">
                                        {{ strtoupper(substr($log->user?->name ?? 'S', 0, 1)) }}
                                    </span>
                                    <div>
                                        <div class="audit-actor__name">{{ $log->user?->name ?? 'System' }}</div>
                                        @if ($log->user?->email)
                                            <div class="audit-actor__email">{{ $log->user->email }}</div>
                                        @else
                                            <div class="audit-actor__email">Automated / system</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="audit-col-event">
                                <div class="audit-event">
                                    <span class="audit-chip audit-chip--module">{{ $log->moduleLabel() }}</span>
                                    <span class="audit-chip audit-chip--{{ $tone }}">{{ $log->actionLabel() }}</span>
                                </div>
                            </td>
                            <td class="audit-col-detail">
                                <div class="audit-desc">{{ $log->description ?: '—' }}</div>
                                @if ($log->entityLabel())
                                    <div class="audit-entity">{{ $log->entityLabel() }}</div>
                                @endif
                                @if ($log->hasValueDiffs())
                                    <div class="audit-change-hint">{{ $changeCount }} field change{{ $changeCount === 1 ? '' : 's' }} recorded</div>
                                @endif
                            </td>
                            <td class="audit-col-meta">
                                <div class="audit-meta">
                                    <span class="audit-meta__ip">{{ $log->ip_address ?: '—' }}</span>
                                    @if ($log->browserHint())
                                        <span class="audit-meta__ua">{{ $log->browserHint() }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="audit-col-more">
                                @if ($log->hasValueDiffs() || $log->user_agent)
                                    <button
                                        type="button"
                                        class="btn btn-ghost audit-more-btn"
                                        @click="open = open === '{{ $rowId }}' ? null : '{{ $rowId }}'"
                                        :aria-expanded="open === '{{ $rowId }}'"
                                    >
                                        <span x-text="open === '{{ $rowId }}' ? 'Hide' : 'Details'"></span>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @if ($log->hasValueDiffs() || $log->user_agent)
                            <tr class="audit-detail-row" x-show="open === '{{ $rowId }}'" x-cloak>
                                <td colspan="6">
                                    <div class="audit-detail">
                                        @if ($log->hasValueDiffs())
                                            <div class="audit-detail__block">
                                                <div class="audit-detail__title">Recorded changes</div>
                                                <div class="audit-changes">
                                                    <div class="audit-changes__head">
                                                        <span>Field</span>
                                                        <span>Before</span>
                                                        <span>After</span>
                                                    </div>
                                                    @foreach ($log->changeRows() as $change)
                                                        <div class="audit-changes__row">
                                                            <span class="audit-changes__key">{{ str_replace('_', ' ', $change['key']) }}</span>
                                                            <span class="audit-changes__old">{{ \App\Models\AuditLog::formatValue($change['old']) }}</span>
                                                            <span class="audit-changes__new">{{ \App\Models\AuditLog::formatValue($change['new']) }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        @if ($log->user_agent)
                                            <div class="audit-detail__block">
                                                <div class="audit-detail__title">Client</div>
                                                <code class="audit-ua">{{ $log->user_agent }}</code>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="audit-empty">
                                <strong>No matching activity</strong>
                                <span>Try clearing filters or broadening the date range.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="audit-pagination">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('audit-filter-form');
    if (!form) return;

    let timer = null;

    function submitFilters(fromSearch) {
        clearTimeout(timer);
        const search = form.querySelector('[data-audit-search]');
        if (fromSearch && search && document.activeElement === search) {
            sessionStorage.setItem('auditSearchFocus', '1');
            sessionStorage.setItem('auditSearchPos', String(search.selectionStart ?? search.value.length));
        }

        const params = new URLSearchParams();
        [...form.elements].forEach((el) => {
            if (!el.name || el.disabled || el.type === 'submit') return;
            const value = String(el.value || '').trim();
            if (value !== '') params.set(el.name, value);
        });

        const base = form.getAttribute('action') || '{{ route('audit.index') }}';
        const qs = params.toString();
        window.location.assign(base + (qs ? '?' + qs : ''));
    }

    form.querySelectorAll('[data-audit-auto]').forEach((el) => {
        el.addEventListener('change', () => submitFilters(false));
    });

    const search = form.querySelector('[data-audit-search]');
    if (search) {
        search.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => submitFilters(true), 350);
        });
        search.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitFilters(true);
            }
        });

        if (sessionStorage.getItem('auditSearchFocus') === '1') {
            sessionStorage.removeItem('auditSearchFocus');
            const pos = parseInt(sessionStorage.getItem('auditSearchPos') || '0', 10);
            sessionStorage.removeItem('auditSearchPos');
            requestAnimationFrame(() => {
                search.focus();
                try {
                    const end = Math.min(pos, search.value.length);
                    search.setSelectionRange(end, end);
                } catch (_) {}
            });
        }
    }
})();
</script>
@endpush
