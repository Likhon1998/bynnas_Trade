@extends('layouts.app')

@section('title', 'Invite user')

@section('content')
    <x-page-header title="Invite user" subtitle="Create account, role, scope and optional permission overrides" />

    <div class="card" style="padding:22px;max-width:960px">
        <form class="form-grid" action="{{ route('users.store') }}" method="post">
            @csrf
            @include('admin.users._form', ['user' => null, 'selectedPermissions' => old('permissions', [])])
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('users.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Create user</button>
            </div>
        </form>
    </div>
@endsection
