<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('portal.login');
        }

        $shop = $user->portal === User::PORTAL_SHOP ? $user->primaryShop() : null;
        $problem = match (true) {
            $user->portal !== User::PORTAL_SHOP => 'Please sign in with a partner (shop) account.',
            ! $user->is_active => 'This account has been deactivated.',
            ! $shop || $shop->status !== Shop::STATUS_ACTIVE => 'Your shop access is not active.',
            default => null,
        };

        if ($problem) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('portal.login')->withErrors(['email' => $problem]);
        }

        $request->attributes->set('shop', $shop);
        view()->share('currentShop', $shop);

        return $next($request);
    }
}
