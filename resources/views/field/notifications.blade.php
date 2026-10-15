@extends('field.layouts.app')
@section('title', 'Notifications')
@section('heading', 'Notifications')
@section('subheading', 'Order, delivery, payment and target alerts')
@section('content')
    <div style="display:grid;gap:10px">
        @forelse ($notifications as $notification)
            @php $data = $notification->data; @endphp
            <div class="card" style="padding:14px;{{ $notification->read_at ? '' : 'border-left:3px solid #7c3aed' }}">
                <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
                    <div>
                        <div style="font-weight:700">{{ $data['title'] ?? 'Alert' }}</div>
                        <div class="muted" style="margin-top:4px">{{ $data['body'] ?? '' }}</div>
                        <div class="muted" style="font-size:11px;margin-top:6px">{{ $notification->created_at?->diffForHumans() }}</div>
                    </div>
                    @if (! $notification->read_at)
                        <form method="post" action="{{ route('field.notifications.read', $notification->id) }}">
                            @csrf
                            <button class="f-btn f-btn-ghost f-btn-sm" type="submit">Mark read</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="card" style="padding:16px">No notifications yet.</div>
        @endforelse
    </div>
    <div style="margin-top:12px">{{ $notifications->links() }}</div>
@endsection
