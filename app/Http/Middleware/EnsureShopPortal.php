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

        if (! $user || $user->portal !== User::PORTAL_SHOP || ! $user->is_active) {
            return redirect()->route('portal.login');
        }

        $shop = $user->primaryShop();

        if (! $shop || $shop->status !== Shop::STATUS_ACTIVE) {
            Auth::logout();

            return redirect()->route('portal.login')->withErrors([
                'email' => 'Your shop access is not active.',
            ]);
        }

        $request->attributes->set('shop', $shop);
        view()->share('currentShop', $shop);

        return $next($request);
    }
}
