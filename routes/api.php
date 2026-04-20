<?php

use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\GameController as AdminGameController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\RarityController as AdminRarityController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CatalogProductController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/catalog/products', [CatalogProductController::class, 'index'])
    ->name('api.catalog.products.index');

Route::middleware('web')->group(function (): void {
    Route::get('/cart', [CartController::class, 'show'])
        ->name('api.cart.show');
    Route::post('/cart/items', [CartController::class, 'store'])
        ->name('api.cart.items.store');
    Route::patch('/cart/items/{product}', [CartController::class, 'update'])
        ->name('api.cart.items.update');
    Route::delete('/cart/items/{product}', [CartController::class, 'destroy'])
        ->name('api.cart.items.destroy');

    Route::post('/orders', [OrderController::class, 'store'])
        ->name('api.orders.store');
});

Route::prefix('admin')->group(function (): void {
    Route::post('/auth/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:admin-login')
        ->name('api.admin.auth.login');

    Route::middleware(['auth:sanctum', 'admin'])->group(function (): void {
        Route::post('/auth/logout', [AdminAuthController::class, 'logout'])
            ->name('api.admin.auth.logout');
        Route::get('/me', [AdminAuthController::class, 'me'])
            ->name('api.admin.me');

        Route::apiResource('games', AdminGameController::class);
        Route::apiResource('rarities', AdminRarityController::class);
        Route::apiResource('products', AdminProductController::class);
        Route::get('/orders', [AdminOrderController::class, 'index'])
            ->name('api.admin.orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])
            ->name('api.admin.orders.show');
    });
});
