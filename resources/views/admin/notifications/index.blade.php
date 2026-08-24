@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
    <x-page-header title="Notifications" subtitle="Operational alerts for admin users">
        <form method="post" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-ghost" type="submit">Mark all read</button></form>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card">
        @forelse ($notifications as $n)
            @php $data = $n->data; @endphp
            <div style="padding:14px 16px;border-bottom:1px solid #f1f3f8;display:flex;gap:12px;align-items:flex-start;background:{{ $n->read_at ? 'transparent' : '#fffbeb' }}">
                <div style="flex:1">
                    <div style="font-weight:700;font-size:14px">{{ $data['title'] ?? 'Notification' }}</div>
                    <div class="muted" style="font-size:13px;margin-top:4px">{{ $data['body'] ?? '' }}</div>
                    <div class="muted" style="font-size:12px;margin-top:6px">{{ $n->created_at?->diffForHumans() }} · {{ $data['category'] ?? 'system' }}</div>
                </div>
                <form method="post" action="{{ route('notifications.read', $n->id) }}">
                    @csrf
                    <button class="btn btn-ghost" type="submit" style="font-size:12px">{{ $n->read_at ? 'Open' : 'Read' }}</button>
                </form>
            </div>
        @empty
            <div class="muted" style="padding:24px;text-align:center">No notifications yet.</div>
        @endforelse
        @if ($notifications->hasPages())
            <div style="padding:12px 16px">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
