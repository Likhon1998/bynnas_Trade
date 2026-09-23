@extends('layouts.app')

@section('title', 'Create role')

@section('content')
    <x-page-header title="Create role" subtitle="Pick a template or build a custom permission set">
        <x-slot:description>Selecting a role template automatically applies the access rights that role should have. You can still fine-tune before saving.</x-slot:description>
    </x-page-header>

    <form class="rbac-shell" action="{{ route('roles.store') }}" method="post">
        @csrf
        @include('admin.roles._form', ['role' => null, 'selected' => old('permissions', [])])
        <div class="rbac-actions">
            <a class="btn btn-ghost" href="{{ route('roles.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Create role</button>
        </div>
    </form>
@endsection
