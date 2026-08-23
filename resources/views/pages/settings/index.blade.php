@extends('layouts.app')
@section('title', 'Settings')
@section('content')
    <x-page-header title="Settings" subtitle="Company, credit, commission and notifications">
        <x-slot:description>Business rules that Super Admin will later persist: credit days, salesman commission, order audit and warehouse defaults.</x-slot:description>
    </x-page-header>
    <div class="grid-2">
        <div class="card" style="padding:22px">
            <div class="section-title" style="margin-bottom:14px">Company profile</div>
            <form class="form-grid">
                <div>
                    <label class="label">Trading name</label>
                    <input class="input" style="width:100%" value="Bynnas Trade">
                </div>
                <div>
                    <label class="label">Currency</label>
                    <input class="input" style="width:100%" value="BDT (৳)">
                </div>
                <div>
                    <label class="label">Default warehouse</label>
                    <select class="select" style="width:100%">
                        @foreach ($warehouses as $warehouse)
                            <option>{{ $warehouse['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Order audit</label>
                    <select class="select" style="width:100%">
                        <option>Required for shop and salesman orders</option>
                        <option>Required for salesman orders only</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="card" style="padding:22px">
            <div class="section-title" style="margin-bottom:14px">Credit & commission</div>
            <form class="form-grid">
                <div>
                    <label class="label">Default credit days</label>
                    <input class="input" style="width:100%" value="21">
                </div>
                <div>
                    <label class="label">Default credit limit (৳)</label>
                    <input class="input" style="width:100%" value="150000">
                </div>
                <div>
                    <label class="label">Salesman commission</label>
                    <input class="input" style="width:100%" value="3.5% on collected payments">
                </div>
                <div>
                    <label class="label">Target bonus</label>
                    <input class="input" style="width:100%" value="1% when monthly target is met">
                </div>
                <div class="form-span" style="display:flex;justify-content:flex-end">
                    <button class="btn btn-primary" type="button">Save rules</button>
                </div>
            </form>
        </div>
    </div>
@endsection
