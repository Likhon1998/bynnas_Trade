@extends('field.layouts.app')
@section('title', 'Alerts')
@section('heading', 'Alerts')
@section('subheading', 'Order, delivery and payment updates')
@section('content')
    <div style="display:grid;gap:10px">
        @forelse ($notifications as $notification)
            @php $data = $notification->data; @endphp
            <div class="f-card" style="{{ $notification->read_at ? '' : 'border-color:#C4B5FD;box-shadow:inset 3px 0 0 #7C3AED' }}">
                <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
                    <div style="min-width:0;flex:1">
                        <div style="font-weight:800;color:var(--f-ink)">{{ $data['title'] ?? 'Alert' }}</div>
                        <div class="f-muted" style="margin-top:4px;line-height:1.45">{{ $data['body'] ?? '' }}</div>
                        <div class="f-muted f-small" style="margin-top:6px">{{ $notification->created_at?->diffForHumans() }}</div>
                    </div>
                    @if (! $notification->read_at)
                        <form method="post" action="{{ route('field.notifications.read', $notification->id) }}">
                            @csrf
                            <button class="f-btn f-btn-ghost f-btn-sm" type="submit">Read</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="f-empty">
                <i data-lucide="bell"></i>
                <strong>No alerts yet</strong>
                Order and delivery updates will appear here.
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div style="display:flex;gap:10px;justify-content:space-between;align-items:center;margin-top:14px">
            @if ($notifications->onFirstPage())
                <span class="f-btn f-btn-ghost f-btn-sm" aria-disabled="true" style="opacity:.45">‹ Newer</span>
            @else
                <a class="f-btn f-btn-ghost f-btn-sm" href="{{ $notifications->previousPageUrl() }}">‹ Newer</a>
            @endif
            <span class="f-muted f-small">Page {{ $notifications->currentPage() }} of {{ $notifications->lastPage() }}</span>
            @if ($notifications->hasMorePages())
                <a class="f-btn f-btn-ghost f-btn-sm" href="{{ $notifications->nextPageUrl() }}">Older ›</a>
            @else
                <span class="f-btn f-btn-ghost f-btn-sm" aria-disabled="true" style="opacity:.45">Older ›</span>
            @endif
        </div>
    @endif
@endsection
