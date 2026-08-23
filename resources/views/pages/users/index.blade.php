@extends('layouts.app')
@section('title', 'Users & Roles')
@section('content')
    <x-page-header title="Users & Roles" subtitle="Access, scopes and credentials">
        <x-slot:description>Super Admin creates users, custom roles and warehouse or territory scopes. Shop owners receive portal credentials only after approval.</x-slot:description>
        <button class="btn btn-primary" type="button">Invite user</button>
    </x-page-header>
    <div class="card" style="margin-bottom:16px">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Access scope</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td style="font-weight:700">{{ $user['name'] }}</td>
                            <td>{{ $user['email'] }}</td>
                            <td>{{ $user['role'] }}</td>
                            <td>{{ $user['scope'] }}</td>
                            <td><x-badge :status="$user['status']" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card" style="padding:18px">
        <div class="section-title" style="margin-bottom:8px">Permission model</div>
        <p class="muted" style="margin-top:0">Roles will later bind to abilities such as approve orders, receive stock, post payments, manage shipments and view analytics. Server-side authorisation will enforce every screen.</p>
    </div>
@endsection
