<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Games as AdminGames;
use App\Livewire\Admin\Login as AdminLogin;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrdersShow;
use App\Livewire\Admin\Products as AdminProducts;
use App\Livewire\Admin\Rarities as AdminRarities;
use App\Livewire\Storefront\About;
use App\Livewire\Storefront\Cart;
use App\Livewire\Storefront\Catalog;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Storefront\Contact;
use App\Livewire\Storefront\Faq;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\ProductShow;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('storefront.home');
Route::get('/catalog', Catalog::class)->name('storefront.catalog');
Route::get('/products/{product}', ProductShow::class)->name('storefront.products.show');
Route::get('/cart', Cart::class)->name('storefront.cart');
Route::get('/checkout', Checkout::class)->name('storefront.checkout');
Route::get('/about', About::class)->name('storefront.about');
Route::get('/contact', Contact::class)->name('storefront.contact');
Route::get('/faq', Faq::class)->name('storefront.faq');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', AdminLogin::class)->name('login');

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::get('/', AdminDashboard::class)->name('dashboard');
        Route::get('/games', AdminGames::class)->name('games.index');
        Route::get('/rarities', AdminRarities::class)->name('rarities.index');
        Route::get('/products', AdminProducts::class)->name('products.index');
        Route::get('/orders', AdminOrdersIndex::class)->name('orders.index');
        Route::get('/orders/{order}', AdminOrdersShow::class)
            ->whereNumber('order')
            ->name('orders.show');
    });
});
