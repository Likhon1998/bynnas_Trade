@extends('layouts.app')

@section('title', 'Edit user')

@section('content')
    <x-page-header title="Edit user" subtitle="{{ $user->email }}">
        <x-slot:description>Change roles to update access. Permission overrides stay on this user only.</x-slot:description>
    </x-page-header>

    <form class="rbac-shell" action="{{ route('users.update', $user) }}" method="post" style="margin-bottom:16px">
        @csrf
        @method('PUT')
        @include('admin.users._form', ['user' => $user, 'selectedPermissions' => old('permissions', $selectedPermissions)])
        <div class="rbac-actions">
            <a class="btn btn-ghost" href="{{ route('users.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Save changes</button>
        </div>
    </form>

    @can('users.reset_password')
        <div class="card" style="padding:22px">
            <div class="section-title" style="margin-bottom:12px">Reset credentials</div>
            <form class="form-grid" action="{{ route('users.reset-password', $user) }}" method="post" style="max-width:640px">
                @csrf
                <div>
                    <label class="label">New password</label>
                    <input class="input" style="width:100%" type="password" name="password" required>
                </div>
                <div>
                    <label class="label">Confirm password</label>
                    <input class="input" style="width:100%" type="password" name="password_confirmation" required>
                </div>
                <div class="form-span" style="display:flex;justify-content:flex-end">
                    <button class="btn btn-primary" type="submit">Reset password</button>
                </div>
            </form>
        </div>
    @endcan
@endsection
