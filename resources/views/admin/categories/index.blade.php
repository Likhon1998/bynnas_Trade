@extends('layouts.app')
@section('title', 'Categories')
@section('content')
    <x-page-header title="Categories" subtitle="Catalogue structure">
        <x-slot:description>Create, edit or delete categories used on products.</x-slot:description>
        <a class="btn btn-ghost" href="{{ route('products.create') }}">Add product</a>
        <a class="btn btn-ghost" href="{{ route('brands.index') }}">Brands</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">{{ $errors->first() }}</div>
    @endif

    <div class="grid-2">
        @can('categories.create')
            <div class="card" style="padding:18px">
                <div class="section-title" style="margin-bottom:12px">Add category</div>
                <form action="{{ route('categories.store') }}" method="post">
                    @csrf
                    <label class="label">Name</label>
                    <input class="input" style="width:100%;margin-bottom:10px" name="name" value="{{ old('name') }}" required>
                    <label class="label">Description</label>
                    <textarea class="textarea" name="description" rows="3">{{ old('description') }}</textarea>
                    <button class="btn btn-primary" style="margin-top:12px" type="submit">Create</button>
                </form>
            </div>
        @endcan

        <div class="card">
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Category</th><th>Products</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr x-data="{ editing: false }">
                                <td style="min-width:220px">
                                    <div x-show="!editing" style="font-weight:600">{{ $category->name }}</div>
                                    <div x-show="!editing" class="muted" style="font-size:12px">{{ $category->description }}</div>
                                    @can('categories.edit')
                                        <form x-show="editing" x-cloak method="post" action="{{ route('categories.update', $category) }}" style="display:grid;gap:8px">
                                            @csrf @method('PUT')
                                            <input class="input" name="name" value="{{ $category->name }}" required>
                                            <textarea class="textarea" name="description" rows="2">{{ $category->description }}</textarea>
                                            <label style="font-size:12px;display:flex;gap:6px;align-items:center">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Active
                                            </label>
                                            <div style="display:flex;gap:6px">
                                                <button class="btn btn-primary btn-sm" type="submit">Save</button>
                                                <button class="btn btn-ghost btn-sm" type="button" @click="editing=false">Cancel</button>
                                            </div>
                                        </form>
                                    @endcan
                                </td>
                                <td>{{ $category->products_count }}</td>
                                <td><x-badge :status="$category->is_active ? 'Active' : 'On Hold'" /></td>
                                <td style="white-space:nowrap">
                                    @can('categories.edit')
                                        <button class="btn btn-ghost btn-sm" type="button" x-show="!editing" @click="editing=true">Edit</button>
                                    @endcan
                                    @can('categories.delete')
                                        <form method="post" action="{{ route('categories.destroy', $category) }}" style="display:inline"
                                            onsubmit="return confirm('Delete this category?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-ghost btn-sm" type="submit" @disabled($category->products_count > 0)
                                                title="{{ $category->products_count > 0 ? 'Reassign products first' : 'Delete' }}">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="muted" style="padding:16px">No categories yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
