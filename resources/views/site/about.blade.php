@extends('site.layouts.app')
@section('title', 'About')
@php($c = $site->section('about'))
@section('content')
    <section class="page-hero">
        <div class="site-wrap">
            @if ($c['hero_eyebrow'])<span class="eyebrow">{{ $c['hero_eyebrow'] }}</span>@endif
            <h1>{{ $c['hero_title'] }}</h1>
            @if ($c['hero_lead'])<p class="lead">{{ $c['hero_lead'] }}</p>@endif
        </div>
    </section>

    @if ($c['what_title'] || $c['info_items'])
        <section class="section">
            <div class="site-wrap split">
                <div>
                    @if ($c['what_eyebrow'])<span class="eyebrow">{{ $c['what_eyebrow'] }}</span>@endif
                    @if ($c['what_title'])<h2>{{ $c['what_title'] }}</h2>@endif
                    @if ($c['what_lead'])<p class="lead">{{ $c['what_lead'] }}</p>@endif
                </div>
                <div class="info-list">
                    @foreach ($c['info_items'] as $item)
                        <div class="info-item">
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($c['commitments'])
        <section class="section section-soft">
            <div class="site-wrap">
                <div class="section-head">
                    @if ($c['commit_eyebrow'])<span class="eyebrow">{{ $c['commit_eyebrow'] }}</span>@endif
                    @if ($c['commit_title'])<h2>{{ $c['commit_title'] }}</h2>@endif
                </div>
                <div class="features features-3">
                    @foreach ($c['commitments'] as $item)
                        <div class="feature">
                            <h3>{{ $item['title'] }}</h3>
                            <p>{{ $item['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($c['cta_title'])
        <section class="section">
            <div class="site-wrap">
                <div class="cta-band">
                    <div>
                        <h2>{{ $c['cta_title'] }}</h2>
                        @if ($c['cta_text'])<p>{{ $c['cta_text'] }}</p>@endif
                    </div>
                    <div class="cta-band-actions">
                        @if ($site->enabled('general.show_partner_button'))
                            <a class="btn btn-white btn-lg" href="{{ route('site.partner') }}">Become a partner</a>
                        @else
                            <a class="btn btn-white btn-lg" href="{{ route('site.contact') }}">Contact us</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
