@extends('layouts.app')

@section('title', 'Edit role')

@section('content')
    <x-page-header title="Edit role" subtitle="{{ $role->name }}">
        <x-slot:description>Adjust access for this role. Use a template to reset to the recommended permission set.</x-slot:description>
    </x-page-header>

    <form class="rbac-shell" action="{{ route('roles.update', $role) }}" method="post">
        @csrf
        @method('PUT')
        @include('admin.roles._form', ['role' => $role, 'selected' => old('permissions', $selected)])
        <div class="rbac-actions">
            <a class="btn btn-ghost" href="{{ route('roles.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit" @if($role->name === 'Super Admin') disabled @endif>
                Save role
            </button>
        </div>
    </form>
@endsection
