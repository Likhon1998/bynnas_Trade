@extends('layouts.app')
@section('title', 'Add Shop')
@section('content')
    <x-page-header title="Add Shop" subtitle="Issue portal access after review" />
    <div class="card" style="padding:22px;max-width:860px">
        <form class="form-grid" action="{{ route('shops.index') }}" method="get">
            <div>
                <label class="label">Shop name</label>
                <input class="input" style="width:100%" placeholder="e.g. Tech Zone">
            </div>
            <div>
                <label class="label">Owner name</label>
                <input class="input" style="width:100%" placeholder="Registered proprietor">
            </div>
            <div>
                <label class="label">Work email</label>
                <input class="input" style="width:100%" type="email" placeholder="Credentials will be sent here">
            </div>
            <div>
                <label class="label">Mobile</label>
                <input class="input" style="width:100%" placeholder="01XXXXXXXXX">
            </div>
            <div>
                <label class="label">City</label>
                <select class="select" style="width:100%"><option>Dhaka</option><option>Chattogram</option><option>Sylhet</option><option>Khulna</option><option>Rajshahi</option></select>
            </div>
            <div>
                <label class="label">Assigned salesman</label>
                <select class="select" style="width:100%">
                    @foreach ($salesmen as $row)
                        <option>{{ $row['name'] }} — {{ $row['territory'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Credit limit (৳)</label>
                <input class="input" style="width:100%" value="150000">
            </div>
            <div>
                <label class="label">Price list</label>
                <select class="select" style="width:100%"><option>Standard wholesale</option><option>Preferred partner</option><option>Volume tier A</option></select>
            </div>
            <div class="form-span">
                <label class="label">Business address</label>
                <textarea class="textarea" rows="3" placeholder="Registered trading address"></textarea>
            </div>
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('shops.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Save for Super Admin review</button>
            </div>
        </form>
    </div>
@endsection
