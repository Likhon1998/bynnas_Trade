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
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

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
    }
}
