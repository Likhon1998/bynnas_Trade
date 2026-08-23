<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UiController;
use App\Http\Middleware\EnsureAdminAuthenticated;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login');

Route::prefix('admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    Route::middleware(EnsureAdminAuthenticated::class)->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', fn () => redirect()->route('dashboard'));
        Route::get('/dashboard', [UiController::class, 'dashboard'])->name('dashboard');

        Route::get('/shops', [UiController::class, 'page'])->defaults('page', 'shops')->name('shops.index');
        Route::get('/shops/create', [UiController::class, 'page'])->defaults('page', 'shops.create')->name('shops.create');
        Route::get('/shops/{id}', [UiController::class, 'show'])->defaults('resource', 'shops')->name('shops.show');

        Route::get('/salesmen', [UiController::class, 'page'])->defaults('page', 'salesmen')->name('salesmen.index');
        Route::get('/salesmen/create', [UiController::class, 'page'])->defaults('page', 'salesmen.create')->name('salesmen.create');
        Route::get('/salesmen/{id}', [UiController::class, 'show'])->defaults('resource', 'salesmen')->name('salesmen.show');

        Route::get('/orders', [UiController::class, 'page'])->defaults('page', 'orders')->name('orders.index');
        Route::get('/orders/create', [UiController::class, 'page'])->defaults('page', 'orders.create')->name('orders.create');
        Route::get('/orders/{id}', [UiController::class, 'show'])->defaults('resource', 'orders')->name('orders.show');

        Route::get('/returns', [UiController::class, 'page'])->defaults('page', 'returns')->name('returns.index');
        Route::get('/invoices', [UiController::class, 'page'])->defaults('page', 'invoices')->name('invoices.index');
        Route::get('/invoices/{id}', [UiController::class, 'show'])->defaults('resource', 'invoices')->name('invoices.show');
        Route::get('/payments', [UiController::class, 'page'])->defaults('page', 'payments')->name('payments.index');

        Route::get('/products', [UiController::class, 'page'])->defaults('page', 'products')->name('products.index');
        Route::get('/products/create', [UiController::class, 'page'])->defaults('page', 'products.create')->name('products.create');
        Route::get('/categories', [UiController::class, 'page'])->defaults('page', 'categories')->name('categories.index');
        Route::get('/inventory', [UiController::class, 'page'])->defaults('page', 'inventory')->name('inventory.index');
        Route::get('/inventory/receive', [UiController::class, 'page'])->defaults('page', 'inventory.receive')->name('inventory.receive');
        Route::get('/warehouses', [UiController::class, 'page'])->defaults('page', 'warehouses')->name('warehouses.index');

        Route::get('/shipments', [UiController::class, 'page'])->defaults('page', 'shipments')->name('shipments.index');
        Route::get('/shipments/create', [UiController::class, 'page'])->defaults('page', 'shipments.create')->name('shipments.create');
        Route::get('/shipments/{id}', [UiController::class, 'show'])->defaults('resource', 'shipments')->name('shipments.show');

        Route::get('/suppliers', [UiController::class, 'page'])->defaults('page', 'suppliers')->name('suppliers.index');
        Route::get('/purchases', [UiController::class, 'page'])->defaults('page', 'purchases')->name('purchases.index');
        Route::get('/purchases/create', [UiController::class, 'page'])->defaults('page', 'purchases.create')->name('purchases.create');

        Route::get('/reports', [UiController::class, 'page'])->defaults('page', 'reports')->name('reports.index');
        Route::get('/analytics', [UiController::class, 'page'])->defaults('page', 'analytics')->name('analytics.index');
        Route::get('/users', [UiController::class, 'page'])->defaults('page', 'users')->name('users.index');
        Route::get('/settings', [UiController::class, 'page'])->defaults('page', 'settings')->name('settings.index');
    });
});
