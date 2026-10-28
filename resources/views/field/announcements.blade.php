@extends('field.layouts.app')
@section('title', 'Announcements')
@section('heading', 'News')
@section('subheading', 'Messages from the office')
@section('content')
    <div style="display:grid;gap:10px">
        @forelse ($announcements as $announcement)
            <article class="f-card">
                <div style="display:flex;gap:10px;align-items:flex-start">
                    <span class="f-ico tone-violet"><i data-lucide="megaphone"></i></span>
                    <div style="min-width:0;flex:1">
                        <div style="font-weight:800;font-size:14.5px;color:var(--f-ink);line-height:1.3">{{ $announcement->title }}</div>
                        <div class="f-muted f-small" style="margin:4px 0 8px">{{ $announcement->published_at?->format('d M Y · g:i A') }}</div>
                        <div style="white-space:pre-wrap;font-size:13.5px;line-height:1.5;color:var(--f-text)">{{ $announcement->body }}</div>
                    </div>
                </div>
            </article>
        @empty
            <div class="f-empty">
                <i data-lucide="megaphone"></i>
                <strong>No news yet</strong>
                When the office posts an announcement, it shows up here.
            </div>
        @endforelse
    </div>

    @if ($announcements->hasPages())
        <div style="display:flex;gap:10px;justify-content:space-between;align-items:center;margin-top:14px">
            @if ($announcements->onFirstPage())
                <span class="f-btn f-btn-ghost f-btn-sm" aria-disabled="true" style="opacity:.45">‹ Newer</span>
            @else
                <a class="f-btn f-btn-ghost f-btn-sm" href="{{ $announcements->previousPageUrl() }}">‹ Newer</a>
            @endif
            <span class="f-muted f-small">Page {{ $announcements->currentPage() }} of {{ $announcements->lastPage() }}</span>
            @if ($announcements->hasMorePages())
                <a class="f-btn f-btn-ghost f-btn-sm" href="{{ $announcements->nextPageUrl() }}">Older ›</a>
            @else
                <span class="f-btn f-btn-ghost f-btn-sm" aria-disabled="true" style="opacity:.45">Older ›</span>
            @endif
        </div>
    @endif
@endsection
