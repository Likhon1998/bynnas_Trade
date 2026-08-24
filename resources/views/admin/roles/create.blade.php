@extends('layouts.app')

@section('title', 'Create role')

@section('content')
    <x-page-header title="Create role" subtitle="Define a custom permission set" />
    <div class="card" style="padding:22px;max-width:960px">
        <form action="{{ route('roles.store') }}" method="post">
            @csrf
            @include('admin.roles._form', ['role' => null, 'selected' => old('permissions', [])])
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
                <a class="btn btn-ghost" href="{{ route('roles.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Create role</button>
            </div>
        </form>
    </div>
@endsection
