@extends('layouts.app')
@section('title', 'Categories')
@section('content')
    <x-page-header title="Categories" subtitle="Catalogue structure for wholesale gadgets">
        <x-slot:description>Categories control how shops browse assigned products and how reports group contribution.</x-slot:description>
    </x-page-header>
    <div class="grid-4">
        @foreach ($categories as $category)
            <div class="card" style="padding:18px">
                <div class="section-title">{{ $category['name'] }}</div>
                <p class="muted" style="margin:8px 0 0">{{ $category['products'] }} products · {{ $category['skus'] }} SKUs</p>
            </div>
        @endforeach
    </div>
@endsection
