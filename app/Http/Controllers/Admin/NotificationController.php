<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all'); // all | unread | read

        $notifications = $request->user()
            ->notifications()
            ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($filter === 'read', fn ($q) => $q->whereNotNull('read_at'))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('data->title', 'like', "%{$search}%")
                        ->orWhere('data->body', 'like', "%{$search}%")
                        ->orWhere('data->category', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(40)
            ->withQueryString();

        $unreadCount = $request->user()->unreadNotifications()->count();
        $totalCount = $request->user()->notifications()->count();

        return view('admin.notifications.index', compact('notifications', 'filter', 'unreadCount', 'totalCount'));
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;
        if (! $url) {
            return back();
        }

        if (str_starts_with($url, '/')) {
            return redirect()->to($url);
        }

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        if (is_string($path) && str_starts_with($path, '/admin')) {
            return redirect()->to($path.($query ? '?'.$query : ''));
        }

        return redirect()->away($url);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
