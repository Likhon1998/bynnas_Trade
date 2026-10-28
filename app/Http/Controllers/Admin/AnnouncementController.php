<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\SystemAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('announcements.view'), 403);

        $announcements = Announcement::query()
            ->with('creator')
            ->withCount('salesmen')
            ->latest()
            ->paginate(20);

        $salesmen = User::query()
            ->where('portal', User::PORTAL_SALESMAN)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.announcements.index', compact('announcements', 'salesmen'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('announcements.manage'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'send_to_all' => ['sometimes', 'boolean'],
            'salesman_ids' => ['nullable', 'array'],
            'salesman_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $sendToAll = $request->boolean('send_to_all', empty($data['salesman_ids'] ?? []));

        $announcement = Announcement::query()->create([
            'title' => $data['title'],
            'body' => $data['body'],
            'send_to_all' => $sendToAll,
            'is_active' => true,
            'published_at' => now(),
            'created_by' => $request->user()->id,
        ]);

        $recipients = User::query()
            ->where('portal', User::PORTAL_SALESMAN)
            ->where('is_active', true)
            ->when(! $sendToAll, fn ($q) => $q->whereIn('id', $data['salesman_ids'] ?? []))
            ->get();

        if (! $sendToAll) {
            $announcement->salesmen()->sync($recipients->pluck('id'));
        }

        if ($recipients->isNotEmpty()) {
            Notification::send(
                $recipients,
                new SystemAlert(
                    $announcement->title,
                    $announcement->body,
                    '/field/notifications',
                    'announcements',
                    'info',
                )
            );
        }

        return back()->with('success', 'Announcement sent to '.$recipients->count().' salesman/men.');
    }

    public function destroy(Request $request, Announcement $announcement)
    {
        abort_unless($request->user()->can('announcements.manage'), 403);

        $announcement->update(['is_active' => false]);

        return back()->with('success', 'Announcement deactivated.');
    }
}
