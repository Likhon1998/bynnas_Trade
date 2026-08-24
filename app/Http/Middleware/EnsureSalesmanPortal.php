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

        if (! $user || $user->portal !== User::PORTAL_SALESMAN || ! $user->is_active) {
            return redirect()->route('field.login');
        }

        $profile = $user->salesmanProfile;

        if (! $profile || ! $profile->is_active) {
            Auth::logout();

            return redirect()->route('field.login')->withErrors([
                'email' => 'Your salesman profile is not active.',
            ]);
        }

        view()->share('salesmanProfile', $profile);

        return $next($request);
    }
}
