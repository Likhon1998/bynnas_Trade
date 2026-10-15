@extends('layouts.app')
@section('title', 'Login activity · '.$salesman->name)
@section('content')
    <x-page-header title="Login activity" subtitle="{{ $salesman->name }} · {{ $salesman->email }}">
        <a class="btn btn-ghost" href="{{ route('salesmen.show', $salesman) }}">Back</a>
    </x-page-header>

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>When</th><th>Action</th><th>IP</th><th>Browser</th><th>Description</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                            <td>{{ $log->actionLabel() }}</td>
                            <td>{{ $log->ip_address ?: '—' }}</td>
                            <td>{{ $log->browserHint() ?: '—' }}</td>
                            <td>{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted" style="padding:16px">No login logs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px">{{ $logs->links() }}</div>
    </div>
@endsection
