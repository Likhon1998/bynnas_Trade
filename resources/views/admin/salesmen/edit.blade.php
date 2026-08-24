@extends('layouts.app')
@section('title', 'Edit Salesman')
@section('content')
    <x-page-header title="Edit {{ $salesman->name }}" subtitle="{{ $salesman->salesmanProfile?->employee_code }}">
        <a class="btn btn-ghost" href="{{ route('salesmen.show', $salesman) }}">Back</a>
    </x-page-header>

    @if ($errors->any())
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#fef2f2;color:#b91c1c">
            <ul style="margin:0;padding-left:18px">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form class="card" style="padding:20px" method="post" action="{{ route('salesmen.update', $salesman) }}">
        @csrf @method('PUT')
        @include('admin.salesmen._form')
        <div style="margin-top:18px;display:flex;gap:10px">
            <button class="btn btn-primary" type="submit">Save changes</button>
            <a class="btn btn-ghost" href="{{ route('salesmen.show', $salesman) }}">Cancel</a>
        </div>
    </form>
@endsection
