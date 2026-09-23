@extends('layouts.app')

@section('title', 'Roles')

@section('content')
    <x-page-header title="Roles" subtitle="Who can access what" action="{{ route('roles.create') }}" action-label="Create role">
        <x-slot:description>System roles ship with recommended permissions. Open any role to review or refine its access.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;border-color:#bbf7d0;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="rbac-index">
        @foreach ($roles as $role)
            <article class="rbac-role-card">
                <div class="rbac-role-card__top">
                    <h3>{{ $role->name }}</h3>
                    <div class="rbac-role-card__stats">
                        <span>{{ $role->users_count }} users</span>
                        <span>{{ $role->permissions_count }} permissions</span>
                    </div>
                </div>
                @if (! empty($presetDescriptions[$role->name] ?? null))
                    <p class="rbac-role-card__desc">{{ $presetDescriptions[$role->name] }}</p>
                @else
                    <p class="rbac-role-card__desc muted">Custom role</p>
                @endif
                <div class="rbac-role-card__actions">
                    @can('roles.edit')
                        <a class="btn btn-primary" href="{{ route('roles.edit', $role) }}">Configure access</a>
                    @endcan
                    @can('roles.delete')
                        @if ($role->name !== 'Super Admin')
                            <form action="{{ route('roles.destroy', $role) }}" method="post" onsubmit="return confirm('Delete this role?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost" type="submit" style="color:#b91c1c">Delete</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </article>
        @endforeach
    </div>
@endsection
