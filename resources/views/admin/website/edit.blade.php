@extends('layouts.app')
@section('title', 'Website')
@section('content')
    @php
        $section = $schema[$tab];
        $site = app(\App\Support\SiteContent::class);
    @endphp

    <x-page-header title="Website" subtitle="Edit the public website — changes go live as soon as you save.">
        <a class="btn btn-ghost" href="{{ $previewUrl }}" target="_blank" rel="noopener">
            <i data-lucide="external-link" style="width:16px;height:16px"></i> View page
        </a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">
            Please fix the highlighted fields: {{ $errors->first() }}
        </div>
    @endif
    @unless ($canManage)
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fff7ed;color:#9a3412">
            You can view the website content but not change it. Ask a Super Admin for the “Edit public website content” permission.
        </div>
    @endunless

    <nav class="web-tabs" aria-label="Website sections">
        @foreach ($schema as $key => $item)
            <a href="{{ route('website.edit', ['tab' => $key]) }}" class="{{ $key === $tab ? 'is-active' : '' }}">{{ $item['label'] }}</a>
        @endforeach
    </nav>

    <p class="muted web-intro">
        {{ $section['description'] }}
        @if ($lastUpdated)
            · Last saved {{ $lastUpdated->updated_at?->diffForHumans() }}{{ $lastUpdated->editor ? ' by '.$lastUpdated->editor->name : '' }}
        @else
            · Showing the default wording.
        @endif
    </p>

    <form method="post" action="{{ route('website.update') }}" enctype="multipart/form-data" id="website-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="section" value="{{ $tab }}">

        <fieldset class="web-fieldset" @disabled(! $canManage)>
            @foreach ($section['groups'] as $groupLabel => $fields)
                <section class="card web-group">
                    <div class="section-title">{{ $groupLabel }}</div>
                    <div class="web-fields">
                        @foreach ($fields as $key => $field)
                            @include('admin.website.field', ['key' => $key, 'field' => $field, 'value' => $values[$key] ?? null])
                        @endforeach
                    </div>
                </section>
            @endforeach
        </fieldset>

        @if ($canManage)
            <div class="web-savebar">
                <button type="submit" class="btn btn-primary">Save {{ strtolower($section['label']) }}</button>
                <a class="btn btn-ghost" href="{{ $previewUrl }}" target="_blank" rel="noopener">View page</a>
                @if ($lastUpdated)
                    <button type="submit" form="website-reset" class="btn btn-ghost web-reset">Restore default wording</button>
                @endif
            </div>
        @endif
    </form>

    @if ($canManage && $lastUpdated)
        <form method="post" action="{{ route('website.reset') }}" id="website-reset" onsubmit="return confirm('Restore the default wording for this section? Your changes here will be lost.')">
            @csrf
            <input type="hidden" name="section" value="{{ $tab }}">
        </form>
    @endif
@endsection
