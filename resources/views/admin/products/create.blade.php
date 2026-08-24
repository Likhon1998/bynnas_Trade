@extends('layouts.app')
@section('title', 'Add Product')
@section('content')
    <x-page-header title="Add Product" subtitle="SKU, pricing and opening stock" />
    <div class="card" style="padding:22px;max-width:960px">
        <form class="form-grid" action="{{ route('products.store') }}" method="post">
            @csrf
            @include('admin.products._form', ['product' => null])
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('products.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Save product</button>
            </div>
        </form>
    </div>
@endsection
