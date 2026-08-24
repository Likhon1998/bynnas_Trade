@extends('layouts.app')

@section('title', 'Edit role')

@section('content')
    <x-page-header title="Edit role" subtitle="{{ $role->name }}" />
    <div class="card" style="padding:22px;max-width:960px">
        <form action="{{ route('roles.update', $role) }}" method="post">
            @csrf
            @method('PUT')
            @include('admin.roles._form', ['role' => $role, 'selected' => old('permissions', $selected)])
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
                <a class="btn btn-ghost" href="{{ route('roles.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Save role</button>
            </div>
        </form>
    </div>
@endsection
