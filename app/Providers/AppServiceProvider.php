<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopVisit;
use App\Models\User;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ShopPolicy;
use App\Policies\ShopVisitPolicy;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.admin');
        Paginator::defaultSimpleView('vendor.pagination.simple-admin');

        Gate::policy(Shop::class, ShopPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ShopVisit::class, ShopVisitPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);

        RedirectIfAuthenticated::redirectUsing(function ($request) {
            $user = $request->user();

            if ($user?->portal === User::PORTAL_SHOP) {
                return route('portal.dashboard');
            }

            if ($user?->portal === User::PORTAL_SALESMAN) {
                return route('field.dashboard');
            }

            return route('dashboard');
        });

        Gate::before(function ($user, string $ability) {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        View::composer('field.layouts.app', function ($view) {
            $user = auth()->user();
            $view->with([
                'fieldProfile' => $user?->salesmanProfile,
                'fieldOpenVisit' => $user
                    ? ShopVisit::query()->with('shop')->where('salesman_id', $user->id)->whereNull('checked_out_at')->first()
                    : null,
            ]);
        });

        RateLimiter::for('login', function (Request $request) {
            $tooMany = fn () => back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Too many login attempts. Please wait a minute and try again.']);

            return [
                Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip())->response($tooMany),
                Limit::perMinute(20)->by($request->ip())->response($tooMany),
            ];
        });
    }
}
