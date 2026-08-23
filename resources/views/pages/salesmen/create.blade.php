@extends('layouts.app')
@section('title', 'Add Salesman')
@section('content')
    <x-page-header title="Add Salesman" subtitle="Field account and commission rules" />
    <div class="card" style="padding:22px;max-width:860px">
        <form class="form-grid" action="{{ route('salesmen.index') }}" method="get">
            <div>
                <label class="label">Full name</label>
                <input class="input" style="width:100%">
            </div>
            <div>
                <label class="label">Mobile</label>
                <input class="input" style="width:100%" placeholder="Used for the mobile dashboard">
            </div>
            <div>
                <label class="label">Email</label>
                <input class="input" style="width:100%" type="email">
            </div>
            <div>
                <label class="label">Territory</label>
                <input class="input" style="width:100%" placeholder="e.g. Dhaka North">
            </div>
            <div>
                <label class="label">Monthly target (৳)</label>
                <input class="input" style="width:100%" value="500000">
            </div>
            <div>
                <label class="label">Commission scheme</label>
                <select class="select" style="width:100%">
                    <option>3.5% on collected payments</option>
                    <option>2.5% on billed orders + target bonus</option>
                    <option>Custom rule</option>
                </select>
            </div>
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('salesmen.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Create salesman account</button>
            </div>
        </form>
    </div>
@endsection
