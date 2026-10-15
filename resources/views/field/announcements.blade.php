@extends('field.layouts.app')
@section('title', 'Announcements')
@section('heading', 'Announcements')
@section('subheading', 'Messages from the office')
@section('content')
    <div style="display:grid;gap:12px">
        @forelse ($announcements as $announcement)
            <div class="card" style="padding:16px">
                <div style="font-weight:700;margin-bottom:6px">{{ $announcement->title }}</div>
                <div class="muted" style="font-size:12px;margin-bottom:8px">{{ $announcement->published_at?->format('d M Y H:i') }}</div>
                <div style="white-space:pre-wrap">{{ $announcement->body }}</div>
            </div>
        @empty
            <div class="card" style="padding:16px" class="muted">No announcements.</div>
        @endforelse
    </div>
    <div style="margin-top:12px">{{ $announcements->links() }}</div>
@endsection
