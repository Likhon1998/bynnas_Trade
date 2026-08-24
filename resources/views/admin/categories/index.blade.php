@extends('layouts.app')
@section('title', 'Categories')
@section('content')
    <x-page-header title="Categories" subtitle="Catalogue structure">
        <x-slot:description>Categories control how shops browse assigned products.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="grid-2">
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:12px">Add category</div>
            <form action="{{ route('categories.store') }}" method="post">
                @csrf
                <label class="label">Name</label>
                <input class="input" style="width:100%;margin-bottom:10px" name="name" required>
                <label class="label">Description</label>
                <textarea class="textarea" name="description" rows="3"></textarea>
                <button class="btn btn-primary" style="margin-top:12px" type="submit">Create</button>
            </form>
        </div>
        <div class="card">
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Category</th><th>Products</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td style="font-weight:600">{{ $category->name }}</td>
                                <td>{{ $category->products_count }}</td>
                                <td><x-badge :status="$category->is_active ? 'Active' : 'On Hold'" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
