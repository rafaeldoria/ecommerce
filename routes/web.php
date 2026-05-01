<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Games as AdminGames;
use App\Livewire\Admin\Games\Edit as AdminGamesEdit;
use App\Livewire\Admin\Login as AdminLogin;
use App\Livewire\Admin\Orders\Index as AdminOrdersIndex;
use App\Livewire\Admin\Orders\Show as AdminOrdersShow;
use App\Livewire\Admin\Products as AdminProducts;
use App\Livewire\Admin\Products\Edit as AdminProductsEdit;
use App\Livewire\Admin\Rarities as AdminRarities;
use App\Livewire\Admin\Rarities\Edit as AdminRaritiesEdit;
use App\Livewire\Storefront\About;
use App\Livewire\Storefront\Cart;
use App\Livewire\Storefront\Catalog;
use App\Livewire\Storefront\Checkout;
use App\Livewire\Storefront\Contact;
use App\Livewire\Storefront\Faq;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\ProductShow;
use App\Modules\Payments\Http\Controllers\MercadoPagoWebhookController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('storefront.home');
Route::get('/catalog', Catalog::class)->name('storefront.catalog');
Route::get('/products/{product}', ProductShow::class)
    ->whereNumber('product')
    ->name('storefront.products.show');
Route::get('/cart', Cart::class)->name('storefront.cart');
Route::get('/checkout', Checkout::class)->name('storefront.checkout');
Route::view('/checkout/mercado-pago/success', 'storefront.mercado-pago-return', [
    'status' => 'success',
])->name('storefront.mercado-pago.success');
Route::view('/checkout/mercado-pago/failure', 'storefront.mercado-pago-return', [
    'status' => 'failure',
])->name('storefront.mercado-pago.failure');
Route::view('/checkout/mercado-pago/pending', 'storefront.mercado-pago-return', [
    'status' => 'pending',
])->name('storefront.mercado-pago.pending');
Route::get('/about', About::class)->name('storefront.about');
Route::get('/contact', Contact::class)->name('storefront.contact');
Route::get('/faq', Faq::class)->name('storefront.faq');
Route::post('/webhooks/mercado-pago', MercadoPagoWebhookController::class)->name('webhooks.mercado-pago');
Route::get('/locale/{locale}', function (Request $request, string $locale): RedirectResponse {
    abort_unless(in_array($locale, ['en', 'pt-BR'], true), 404);

    $request->session()->put('locale', $locale === 'pt-BR' ? 'pt_BR' : 'en');

    return redirect()->back();
})->name('storefront.locale');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', AdminLogin::class)->name('login');

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::get('/', AdminDashboard::class)->name('dashboard');
        Route::get('/games', AdminGames::class)->name('games.index');
        Route::get('/games/{game}/edit', AdminGamesEdit::class)
            ->whereNumber('game')
            ->name('games.edit');
        Route::get('/rarities', AdminRarities::class)->name('rarities.index');
        Route::get('/rarities/{rarity}/edit', AdminRaritiesEdit::class)
            ->whereNumber('rarity')
            ->name('rarities.edit');
        Route::get('/products', AdminProducts::class)->name('products.index');
        Route::get('/products/{product}/edit', AdminProductsEdit::class)
            ->whereNumber('product')
            ->name('products.edit');
        Route::get('/orders', AdminOrdersIndex::class)->name('orders.index');
        Route::get('/orders/{order}', AdminOrdersShow::class)
            ->whereNumber('order')
            ->name('orders.show');

        Route::post('/logout', function (Request $request): RedirectResponse {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login');
        })->name('logout');
    });
});
