@extends('layouts.app')

@section('title', 'Audit logs')

@section('content')
    <x-page-header title="Audit logs" subtitle="Sensitive actions across the platform">
        <x-slot:description>Every login, user change, role update and (later) order or stock change is recorded with actor, module and timestamp.</x-slot:description>
    </x-page-header>

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Search description or module">
            <input class="input" name="module" value="{{ request('module') }}" placeholder="Module e.g. users">
            <input class="input" name="action" value="{{ request('action') }}" placeholder="Action e.g. created">
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="muted">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td>{{ $log->module }}</td>
                            <td style="font-weight:600">{{ $log->action }}</td>
                            <td>{{ $log->description }}</td>
                            <td class="muted">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted" style="padding:24px;text-align:center">No audit activity yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div style="padding:14px">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection
