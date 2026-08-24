<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'email' => 'This account has been deactivated.',
            ]);
        }

        if ($user->portal !== \App\Models\User::PORTAL_ADMIN && ! $user->isSuperAdmin()) {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'email' => 'This account is not authorized for the admin portal.',
            ]);
        }

        return $next($request);
    }
}
