@props(['title', 'subtitle' => null, 'action' => null, 'actionLabel' => null, 'actionHref' => null])

@php
    $href = $actionHref ?: $action;
@endphp

<div class="toolbar">
    <div>
        <div class="page-kicker"><strong>{{ $title }}</strong> @if($subtitle)/ {{ $subtitle }}@endif</div>
        @isset($description)
            <p class="muted" style="margin:4px 0 0;font-size:12px">{{ $description }}</p>
        @endisset
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        {{ $tools ?? '' }}
        {{ $slot }}
        @if ($href)
            <a href="{{ $href }}" class="btn btn-primary">{{ $actionLabel }}</a>
        @endif
    </div>
</div>
