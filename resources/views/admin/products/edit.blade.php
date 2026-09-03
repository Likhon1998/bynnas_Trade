@extends('layouts.app')
@section('title', 'Edit Product')
@section('content')
    <x-page-header title="Edit Product" subtitle="{{ $product->sku }}" />
    <div class="card" style="padding:22px;max-width:960px">
        <form class="form-grid" action="{{ route('products.update', $product) }}" method="post" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.products._form', ['product' => $product])
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('products.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Save changes</button>
            </div>
        </form>
    </div>
@endsection
