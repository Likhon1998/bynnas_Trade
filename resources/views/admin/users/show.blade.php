@extends('layouts.app')

@section('title', $user->name)

@section('content')
    <x-page-header title="{{ $user->name }}" subtitle="{{ $user->email }}">
        <x-slot:tools>
            <x-badge :status="$user->is_active ? 'Active' : 'On Hold'" />
            @can('users.edit')
                <a class="btn btn-primary" href="{{ route('users.edit', $user) }}">Edit user</a>
            @endcan
        </x-slot:tools>
    </x-page-header>

    <div class="grid-4" style="margin-bottom:16px">
        @foreach ([
            ['Portal', ucfirst($user->portal)],
            ['Roles', $user->roles->pluck('name')->join(', ') ?: '—'],
            ['Access scope', $user->scopeLabel()],
            ['Last login', $user->last_login_at?->format('d M Y H:i') ?? 'Never'],
        ] as $card)
            <div class="card" style="padding:16px">
                <div class="muted">{{ $card[0] }}</div>
                <div style="font-size:16px;font-weight:800;margin-top:6px">{{ $card[1] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid-2">
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px">Role permissions</div>
            @forelse ($user->getPermissionsViaRoles()->pluck('name') as $permission)
                <div class="muted" style="margin-bottom:4px">{{ $permission }}</div>
            @empty
                <p class="muted">No role permissions assigned.</p>
            @endforelse
        </div>
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:10px">Direct overrides</div>
            @forelse ($user->getDirectPermissions()->pluck('name') as $permission)
                <div class="muted" style="margin-bottom:4px">{{ $permission }}</div>
            @empty
                <p class="muted">No direct permission overrides.</p>
            @endforelse
        </div>
    </div>
@endsection
