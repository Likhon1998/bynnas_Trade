@extends('layouts.app')
@section('title', 'Add Shop')
@section('content')
    <x-page-header title="Add Shop" subtitle="Create partner and optionally issue portal credentials" />
    <div class="card" style="padding:22px;max-width:920px">
        <form class="form-grid" action="{{ route('shops.store') }}" method="post">
            @csrf
            @include('admin.shops._form', ['shop' => null])
            <div class="form-span" style="border-top:1px solid var(--line);padding-top:14px">
                <label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:13px;margin-bottom:10px">
                    <input type="checkbox" name="issue_credentials" value="1" @checked(old('issue_credentials'))>
                    Issue B2B portal credentials now
                </label>
                <div class="form-grid">
                    <div>
                        <label class="label">Login email</label>
                        <input class="input" style="width:100%" type="email" name="login_email" value="{{ old('login_email') }}" placeholder="Defaults to shop email">
                    </div>
                    <div>
                        <label class="label">Temporary password</label>
                        <input class="input" style="width:100%" type="text" name="login_password" value="{{ old('login_password', '12345678') }}">
                    </div>
                </div>
            </div>
            <div class="form-span" style="display:flex;gap:8px;justify-content:flex-end">
                <a class="btn btn-ghost" href="{{ route('shops.index') }}">Cancel</a>
                <button class="btn btn-primary" type="submit">Save shop</button>
            </div>
        </form>
    </div>
@endsection
