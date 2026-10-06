<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSalesmanPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('field.login');
        }

        $profile = $user->salesmanProfile;
        $problem = match (true) {
            $user->portal !== User::PORTAL_SALESMAN => 'Please sign in with a field officer account.',
            ! $user->is_active => 'This account has been deactivated.',
            ! $profile || ! $profile->is_active => 'Your salesman profile is not active.',
            default => null,
        };

        if ($problem) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('field.login')->withErrors(['email' => $problem]);
        }

        view()->share('salesmanProfile', $profile);

        return $next($request);
    }
}
