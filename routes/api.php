<?php

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
