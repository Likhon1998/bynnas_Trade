@extends('layouts.app')
@section('title', 'Edit Product')
@section('content')
    <form class="product-desk" action="{{ route('products.update', $product) }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <x-page-header title="Edit Product" subtitle="{{ $product->sku }}">
            <x-slot:description>{{ $product->name }}</x-slot:description>
            <a class="btn btn-ghost" href="{{ route('products.index') }}">Back</a>
            <button class="btn btn-primary" type="submit">Save changes</button>
        </x-page-header>
        @include('admin.products._form', ['product' => $product])
        <div class="product-desk-foot">
            <a class="btn btn-ghost" href="{{ route('products.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Save changes</button>
        </div>
    </form>
@endsection
