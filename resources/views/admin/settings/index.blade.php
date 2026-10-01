@extends('layouts.app')
@section('title', 'Settings')
@section('content')
    <x-page-header title="Settings" subtitle="Business rules currently in effect">
        <x-slot:description>Each rule is managed on its own page — use the links to change it.</x-slot:description>
    </x-page-header>

    @php
        $whatsappLabel = match ($whatsappDriver) {
            'meta' => 'Live (Meta WhatsApp Cloud API)',
            'off' => 'Disabled',
            default => 'Log only — messages are written to the log, not sent',
        };
    @endphp

    <div class="grid-2">
        <div class="card" style="padding:22px">
            <div class="section-title" style="margin-bottom:14px">Company</div>
            <dl class="settings-list">
                <dt>Trading name</dt><dd>{{ config('app.name') }}</dd>
                <dt>Currency</dt><dd>BDT (৳)</dd>
                <dt>Timezone</dt><dd>{{ $timezone }}</dd>
                <dt>Default warehouse</dt>
                <dd>
                    {{ $defaultWarehouse?->name ?? 'Not set' }}
                    <span class="muted">· {{ $warehouseCount }} active</span>
                    @can('warehouses.view')
                        <a class="link" href="{{ route('warehouses.index') }}">Manage</a>
                    @endcan
                </dd>
            </dl>
        </div>

        <div class="card" style="padding:22px">
            <div class="section-title" style="margin-bottom:14px">Salesman commission</div>
            <dl class="settings-list">
                <dt>On collected payments</dt>
                <dd>{{ $commissionRule ? rtrim(rtrim(number_format((float) $commissionRule->collection_rate_percent, 2), '0'), '.').'%' : 'No active rule' }}</dd>
                <dt>Monthly target bonus</dt>
                <dd>{{ $commissionRule ? rtrim(rtrim(number_format((float) $commissionRule->target_bonus_percent, 2), '0'), '.').'%' : '—' }}</dd>
                <dt>Change</dt>
                <dd>
                    @can('commissions.view')
                        <a class="link" href="{{ route('commissions.index') }}">Commission rule &amp; payouts</a>
                    @else
                        <span class="muted">Requires commissions access</span>
                    @endcan
                </dd>
            </dl>
        </div>

        <div class="card" style="padding:22px">
            <div class="section-title" style="margin-bottom:14px">Pricing &amp; credit</div>
            <dl class="settings-list">
                <dt>Price groups</dt>
                <dd>
                    {{ $priceGroupCount }} active · default: {{ $defaultPriceGroup?->name ?? 'none' }}
                    @can('price_groups.view')
                        <a class="link" href="{{ route('price-groups.index') }}">Manage</a>
                    @endcan
                </dd>
                <dt>New shop payment terms</dt><dd>21 days</dd>
                <dt>New shop credit limit</dt><dd>৳ 0 until set on the shop</dd>
                <dt>Order audit</dt><dd>Every shop and salesman order needs Super Admin approval</dd>
            </dl>
        </div>

        <div class="card" style="padding:22px">
            <div class="section-title" style="margin-bottom:14px">Notifications</div>
            <dl class="settings-list">
                <dt>WhatsApp</dt><dd>{{ $whatsappLabel }}</dd>
                <dt>Email</dt><dd>{{ $mailDriver === 'log' ? 'Log only — emails are written to the log, not sent' : ucfirst($mailDriver) }}</dd>
                <dt>Configure</dt><dd class="muted">Set <code>WHATSAPP_DRIVER</code> and <code>MAIL_MAILER</code> in the server <code>.env</code> file</dd>
            </dl>
        </div>
    </div>
@endsection
