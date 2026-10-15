@extends('layouts.app')
@section('title', 'Announcements')
@section('content')
    <x-page-header title="Announcements" subtitle="Message all salesmen or selected field staff" />

    @if (session('success'))
        <div class="card" style="padding:12px 16px;margin-bottom:14px;background:#e8f8ee;color:#15803d">{{ session('success') }}</div>
    @endif

    @can('announcements.manage')
        <div class="card" style="padding:16px;margin-bottom:14px">
            <form method="post" action="{{ route('announcements.store') }}">
                @csrf
                <div class="field" style="margin-bottom:10px"><label class="label">Title</label><input class="input" style="width:100%" name="title" required></div>
                <div class="field" style="margin-bottom:10px"><label class="label">Message</label><textarea class="textarea" name="body" rows="4" required></textarea></div>
                <div class="field" style="margin-bottom:10px">
                    <label><input type="checkbox" name="send_to_all" value="1" checked> Send to all active salesmen</label>
                </div>
                <div class="field" style="margin-bottom:10px">
                    <label class="label">Or select salesmen</label>
                    <select class="select" name="salesman_ids[]" multiple size="6" style="width:100%">
                        @foreach ($salesmen as $salesman)
                            <option value="{{ $salesman->id }}">{{ $salesman->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Send announcement</button>
            </form>
        </div>
    @endcan

    <div class="card">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Title</th><th>Audience</th><th>Published</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($announcements as $announcement)
                        <tr>
                            <td>
                                <div style="font-weight:600">{{ $announcement->title }}</div>
                                <div class="muted" style="font-size:12px">{{ \Illuminate\Support\Str::limit($announcement->body, 120) }}</div>
                            </td>
                            <td>{{ $announcement->send_to_all ? 'All salesmen' : $announcement->salesmen_count.' selected' }}</td>
                            <td>{{ $announcement->published_at?->format('d M Y H:i') }}</td>
                            <td>{{ $announcement->is_active ? 'Active' : 'Off' }}</td>
                            <td>
                                @can('announcements.manage')
                                    <form method="post" action="{{ route('announcements.destroy', $announcement) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-ghost btn-sm" type="submit">Deactivate</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted" style="padding:16px">No announcements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px">{{ $announcements->links() }}</div>
    </div>
@endsection
