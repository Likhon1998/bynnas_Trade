<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(30);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;
        if (! $url) {
            return back();
        }

        // Relative paths (preferred) and same-app absolute URLs both stay on this host.
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
