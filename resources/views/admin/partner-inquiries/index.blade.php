@extends('layouts.app')
@section('title', 'Partner leads')
@section('content')
    <x-page-header title="Partner & contact leads" subtitle="Public site applications and messages">
        <a class="btn btn-ghost" href="{{ route('site.home') }}" target="_blank">Open public site</a>
    </x-page-header>

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    <div class="card" style="padding:14px;margin-bottom:14px">
        <form class="filters" method="get">
            <select class="select" name="status">
                <option value="">All partner statuses</option>
                @foreach (['new','contacted','converted','closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>

    <div class="card" style="margin-bottom:18px">
        <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Partner applications</div>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Contact</th>
                        <th>City</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inquiries as $row)
                        <tr>
                            <td>
                                <div style="font-weight:700">{{ $row->business_name }}</div>
                                <div class="muted" style="font-size:12px">{{ $row->created_at?->format('d M Y H:i') }}</div>
                                @if ($row->message)<div class="muted" style="font-size:12px;margin-top:4px">{{ \Illuminate\Support\Str::limit($row->message, 90) }}</div>@endif
                            </td>
                            <td>
                                <div>{{ $row->contact_name }}</div>
                                <div class="muted" style="font-size:12px">{{ $row->email }}</div>
                                <div class="muted" style="font-size:12px">{{ $row->phone }}</div>
                            </td>
                            <td>{{ $row->city ?: '—' }}</td>
                            <td>{{ ucfirst($row->business_type ?: '—') }}</td>
                            <td><x-badge :status="$row->statusLabel()" /></td>
                            <td>
                                <form method="post" action="{{ route('partner-inquiries.update', $row) }}" style="display:grid;gap:6px;min-width:180px">
                                    @csrf
                                    @method('put')
                                    <select class="select" name="status">
                                        @foreach (['new','contacted','converted','closed'] as $status)
                                            <option value="{{ $status }}" @selected($row->status === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <input class="input" name="admin_notes" value="{{ $row->admin_notes }}" placeholder="Notes">
                                    <button class="btn btn-ghost" type="submit" style="font-size:12px">Save</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">No partner applications yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($inquiries->hasPages())
            <div style="padding:12px 16px">{{ $inquiries->links() }}</div>
        @endif
    </div>

    <div class="card">
        <div style="padding:14px;font-weight:700;border-bottom:1px solid #eee">Contact messages</div>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $msg)
                        <tr>
                            <td>
                                <div style="font-weight:600">{{ $msg->name }}</div>
                                <div class="muted" style="font-size:12px">{{ $msg->email }}</div>
                            </td>
                            <td>{{ $msg->subject ?: '—' }}</td>
                            <td class="muted" style="max-width:280px">{{ \Illuminate\Support\Str::limit($msg->message, 120) }}</td>
                            <td><x-badge :status="$msg->statusLabel()" /></td>
                            <td>
                                <form method="post" action="{{ route('contact-messages.update', $msg) }}">
                                    @csrf
                                    @method('put')
                                    <select class="select" name="status" onchange="this.form.submit()">
                                        @foreach (['new','read','closed'] as $status)
                                            <option value="{{ $status }}" @selected($msg->status === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted" style="padding:24px;text-align:center">No contact messages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($messages->hasPages())
            <div style="padding:12px 16px">{{ $messages->links() }}</div>
        @endif
    </div>
@endsection
