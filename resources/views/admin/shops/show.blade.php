@extends('layouts.app')
@section('title', $shop->name)
@section('content')
    <x-page-header title="{{ $shop->name }}" subtitle="{{ $shop->code }}">
        <x-slot:tools>
            <x-badge :status="$shop->statusLabel()" />
            @can('update', $shop)
                <a class="btn btn-ghost" href="{{ route('shops.edit', $shop) }}">Edit</a>
            @endcan
            @can('approve', $shop)
                @if ($shop->status !== 'active')
                    <form action="{{ route('shops.approve', $shop) }}" method="post">@csrf
                        <button class="btn btn-primary" type="submit">Approve shop</button>
                    </form>
                @endif
            @endcan
        </x-slot:tools>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="grid-4" style="margin-bottom:16px">
        @foreach ([
            ['Credit limit', \App\Support\DemoData::taka($shop->credit_limit)],
            ['Outstanding', \App\Support\DemoData::taka($shop->outstanding_balance)],
            ['Available credit', \App\Support\DemoData::taka($shop->availableCredit())],
            ['Payment terms', $shop->payment_terms_days.' days'],
        ] as $card)
            <div class="card" style="padding:16px">
                <div class="muted">{{ $card[0] }}</div>
                <div style="font-size:20px;font-weight:800;margin-top:6px">{{ $card[1] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid-2" style="margin-bottom:16px">
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px">Partner details</div>
            <p class="muted" style="margin:0 0 6px">Owner: <strong style="color:var(--text)">{{ $shop->owner_name }}</strong></p>
            <p class="muted" style="margin:0 0 6px">City / territory: {{ $shop->city ?: '—' }} / {{ $shop->territory?->name ?: '—' }}</p>
            <p class="muted" style="margin:0 0 6px">Price group: {{ $shop->priceGroup?->name ?: '—' }}</p>
            <p class="muted" style="margin:0 0 6px">Salesman: {{ $shop->assignedSalesman?->name ?: 'Unassigned' }}</p>
            <p class="muted" style="margin:0">{{ $shop->address }}</p>
        </div>
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px">Portal users</div>
            @forelse ($shop->users as $user)
                <div style="padding:8px 0;border-bottom:1px solid #f1f3f8;font-size:13px">
                    <strong>{{ $user->name }}</strong> · {{ $user->email }}
                    @if ($user->pivot->is_primary) <span class="badge badge-active">Primary</span> @endif
                </div>
            @empty
                <p class="muted">No portal users yet.</p>
            @endforelse

            @can('manageCredentials', $shop)
                <form action="{{ route('shops.credentials', $shop) }}" method="post" class="form-grid" style="margin-top:14px">
                    @csrf
                    <div>
                        <label class="label">Login email</label>
                        <input class="input" style="width:100%" type="email" name="login_email" value="{{ $shop->email }}" required>
                    </div>
                    <div>
                        <label class="label">Password</label>
                        <input class="input" style="width:100%" type="text" name="login_password" value="12345678" required>
                    </div>
                    <div class="form-span">
                        <button class="btn btn-primary" type="submit">Issue / reset credentials</button>
                    </div>
                </form>
            @endcan
        </div>
    </div>
@endsection
