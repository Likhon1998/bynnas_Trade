<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('audit.view');

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->module, fn ($q, $module) => $q->where('module', $module))
            ->when($request->action, fn ($q, $action) => $q->where('action', $action))
            ->when($request->actor, fn ($q, $actor) => $q->where('user_id', $actor))
            ->when($request->from, fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->to, fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $tz = config('app.timezone');

        $stats = [
            'today' => AuditLog::query()->whereDate('created_at', now($tz)->toDateString())->count(),
            'week' => AuditLog::query()->where('created_at', '>=', now($tz)->copy()->startOfWeek())->count(),
            'total' => AuditLog::query()->count(),
            'actors' => (int) AuditLog::query()->whereNotNull('user_id')->distinct()->count('user_id'),
        ];

        $modules = AuditLog::query()
            ->select('module', DB::raw('COUNT(*) as cnt'))
            ->groupBy('module')
            ->orderByDesc('cnt')
            ->pluck('cnt', 'module');

        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $actors = User::query()
            ->whereIn('id', AuditLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.audit.index', compact('logs', 'stats', 'modules', 'actions', 'actors'));
    }
}
