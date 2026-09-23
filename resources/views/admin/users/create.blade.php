@extends('layouts.app')

@section('title', 'Invite user')

@section('content')
    <x-page-header title="Invite user" subtitle="Account, role and access in one place">
        <x-slot:description>Choose a role and the matching permissions appear automatically. Add overrides only if this person needs extras.</x-slot:description>
    </x-page-header>

    <form class="rbac-shell" action="{{ route('users.store') }}" method="post">
        @csrf
        @include('admin.users._form', ['user' => null, 'selectedPermissions' => old('permissions', [])])
        <div class="rbac-actions">
            <a class="btn btn-ghost" href="{{ route('users.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Create user</button>
        </div>
    </form>
@endsection
