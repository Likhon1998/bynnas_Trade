@extends('field.layouts.app')
@section('title', 'Partners')
@section('heading', 'Partner applications')
@section('subheading', 'Bring shops onto the partner portal — they order online and you get the credit.')
@section('actions')
    <a class="f-btn f-btn-primary f-btn-sm" href="{{ route('field.partners.create') }}"><i data-lucide="handshake"></i> New application</a>
@endsection
@section('content')
    @include('field.partials.login-todo')

    <div class="f-kpis">
        <div class="f-kpi tone-teal">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="send"></i></span><span class="f-kpi-label">Applications sent</span></div>
            <div class="f-kpi-value">{{ $counts['all'] }}</div>
            <div class="f-kpi-meta">By you</div>
        </div>
        <div class="f-kpi tone-amber">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="hourglass"></i></span><span class="f-kpi-label">Waiting for review</span></div>
            <div class="f-kpi-value">{{ $counts['pending'] }}</div>
            <div class="f-kpi-meta">With the office</div>
        </div>
        <div class="f-kpi tone-green">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="badge-check"></i></span><span class="f-kpi-label">Accepted</span></div>
            <div class="f-kpi-value">{{ $counts['accepted'] }}</div>
            <div class="f-kpi-meta">Now on the partner portal</div>
        </div>
        <div class="f-kpi tone-rose">
            <div class="f-kpi-top"><span class="f-ico"><i data-lucide="x-circle"></i></span><span class="f-kpi-label">Not accepted</span></div>
            <div class="f-kpi-value">{{ $counts['rejected'] }}</div>
            <div class="f-kpi-meta">Check the office note</div>
        </div>
    </div>

    <a class="f-btn f-btn-primary f-btn-block f-mobile-only" style="margin-bottom:12px" href="{{ route('field.partners.create') }}"><i data-lucide="handshake"></i> New partner application</a>

    <section class="f-panel">
        <div class="f-panel-head">
            <h2><span class="f-ico tone-teal"><i data-lucide="handshake"></i></span>My applications</h2>
            <a href="{{ route('field.partners.create') }}">+ Apply for a shop</a>
        </div>
        @if ($applications->isEmpty())
            <div class="f-panel-body">
                <div class="f-empty">
                    <i data-lucide="handshake"></i>
                    <strong>No applications yet</strong>
                    When a shop owner wants to order online, apply for them here.
                </div>
            </div>
        @else
            <div class="f-table-wrap">
                <table class="f-table">
                    <thead><tr><th>Business</th><th class="f-desktop-only">Owner login email</th><th class="f-desktop-only">Phone</th><th class="f-desktop-only">Sent</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($applications as $app)
                            @php
                                $tone = $app->isAccepted() ? 'green' : ($app->isPending() ? 'amber' : 'red');
                                $label = $app->isAccepted() ? 'Accepted' : ($app->isPending() ? 'Waiting' : 'Not accepted');
                            @endphp
                            <tr>
                                <td>
                                    <span class="strong">{{ $app->business_name }}</span>
                                    <span class="sub">{{ collect([$app->contact_name, $app->city, $app->shop?->code])->filter()->implode(' · ') ?: '—' }}</span>
                                    @if (! $app->isPending() && ! $app->isAccepted() && $app->admin_notes)
                                        <span class="sub" style="color:var(--f-red)">Office: {{ \Illuminate\Support\Str::limit($app->admin_notes, 80) }}</span>
                                    @endif
                                </td>
                                <td class="f-desktop-only">{{ $app->portal_email ?: $app->email }}</td>
                                <td class="f-desktop-only">{{ $app->phone }}</td>
                                <td class="f-muted f-desktop-only" style="white-space:nowrap">{{ $app->created_at?->format('d M') }}</td>
                                <td>
                                    <span class="f-status {{ $tone }}">{{ $label }}</span>
                                    @if ($app->isAccepted())
                                        <span class="sub">{{ $app->portal_email ? 'Login ready' : 'Needs login' }}</span>
                                    @endif
                                </td>
                                <td class="num">
                                    @if ($app->needsLogin())
                                        <a class="f-btn f-btn-primary f-btn-sm" href="{{ route('field.partners.login', $app) }}"><i data-lucide="user-plus"></i> Login</a>
                                    @elseif ($app->isAccepted())
                                        <a class="f-btn f-btn-ghost f-btn-sm" href="{{ route('field.partners.login', $app) }}" title="Reset the owner's password"><i data-lucide="key-round"></i> Reset</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($applications->hasPages())
                <div class="f-panel-body">{{ $applications->links() }}</div>
            @endif
        @endif
    </section>
@endsection
