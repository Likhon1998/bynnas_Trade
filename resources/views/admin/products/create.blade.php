@extends('layouts.app')
@section('title', 'Add Product')
@section('content')
    <form class="product-desk" action="{{ route('products.store') }}" method="post" enctype="multipart/form-data">
        @csrf
        <x-page-header title="Add Product" subtitle="New SKU">
            <x-slot:description>Pictures, identity and prices for a new catalogue item.</x-slot:description>
            <a class="btn btn-ghost" href="{{ route('products.index') }}">Back</a>
            <button class="btn btn-primary" type="submit">Save product</button>
        </x-page-header>
        @include('admin.products._form', ['product' => null])
        <div class="product-desk-foot">
            <a class="btn btn-ghost" href="{{ route('products.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">Save product</button>
        </div>
    </form>
@endsection
