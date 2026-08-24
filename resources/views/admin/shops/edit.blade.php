@extends('layouts.app')
@section('title', 'Edit Shop')
@section('content')
    <x-page-header title="Edit Shop" subtitle="{{ $shop->code }}" />
    <div class="card" style="padding:22px;max-width:920px">
        <form class="form-grid" action="{{ route('shops.update', $shop) }}" method="post">
            @csrf
            @method('PUT')
            @include('admin.shops._form', ['shop' => $shop])
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('shops.show', $shop) }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Save changes</button>
            </div>
        </form>
    </div>
@endsection
