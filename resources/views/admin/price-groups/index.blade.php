@extends('layouts.app')
@section('title', 'Price groups')
@section('content')
    <x-page-header title="Price groups" subtitle="Wholesale pricing tiers for shops">
        <x-slot:description>Each shop is assigned a price group. Product overrides apply automatically in the B2B portal.</x-slot:description>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="grid-2">
        <div class="card" style="padding:18px">
            <div class="section-title" style="margin-bottom:12px">Add price group</div>
            <form action="{{ route('price-groups.store') }}" method="post">
                @csrf
                <label class="label">Name</label>
                <input class="input" style="width:100%;margin-bottom:10px" name="name" required>
                <label class="label">Code</label>
                <input class="input" style="width:100%;margin-bottom:10px" name="code">
                <label class="label">Description</label>
                <textarea class="textarea" name="description" rows="3"></textarea>
                <label style="display:flex;align-items:center;gap:8px;margin:12px 0;font-size:13px">
                    <input type="checkbox" name="is_default" value="1"> Set as default
                </label>
                <button class="btn btn-primary" type="submit">Create</button>
            </form>
        </div>
        <div class="card">
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Group</th><th>Code</th><th>Shops</th><th>Default</th></tr></thead>
                    <tbody>
                        @foreach ($groups as $group)
                            <tr>
                                <td style="font-weight:600">{{ $group->name }}</td>
                                <td>{{ $group->code ?: '—' }}</td>
                                <td>{{ $group->shops_count }}</td>
                                <td>{{ $group->is_default ? 'Yes' : 'No' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
