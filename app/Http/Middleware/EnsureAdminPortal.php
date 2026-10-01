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

            $home = $user->portalLogin();

            return redirect()->route('login')
                ->withErrors(['email' => "This is a {$home['account']} account, so it can't open the Admin portal. Please use the {$home['label']}."])
                ->with('portal_hint', ['label' => $home['label'], 'url' => route($home['route'], ['email' => $user->email])]);
        }

        return $next($request);
    }
}
