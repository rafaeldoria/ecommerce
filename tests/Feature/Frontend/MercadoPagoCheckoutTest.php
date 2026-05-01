<?php

namespace Tests\Feature\Frontend;

use App\Livewire\Storefront\Checkout;
use App\Modules\Cart\Actions\AddToCartAction;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\DTOs\AddToCartData;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Payments\Contracts\CheckoutPreferenceGateway;
use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\DTOs\CheckoutPreferenceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MercadoPagoCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function checkout_blocks_preference_creation_when_the_cart_is_empty(): void
    {
        Livewire::test(Checkout::class)
            ->set('email', 'buyer@example.com')
            ->set('whatsapp', '+55 11 99999-1111')
            ->call('startCheckout')
            ->assertHasErrors(['checkout']);
    }

    #[Test]
    public function checkout_validates_contact_data_before_creating_a_preference(): void
    {
        Product::factory()->create();

        Livewire::test(Checkout::class)
            ->set('email', 'invalid-email')
            ->set('whatsapp', '123')
            ->call('startCheckout')
            ->assertHasErrors(['email', 'whatsapp']);
    }

    #[Test]
    public function checkout_creates_a_pending_order_and_redirects_to_mercado_pago(): void
    {
        $this->app->bind(CheckoutPreferenceGateway::class, fn () => new class implements CheckoutPreferenceGateway
        {
            public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult
            {
                return new CheckoutPreferenceResult(
                    preferenceId: 'pref_test_123',
                    publicKey: 'TEST-public-key',
                    checkoutUrl: 'https://sandbox.mercadopago.test/checkout',
                );
            }
        });

        $product = Product::factory()->create([
            'name' => 'AK-47 Redline',
            'price' => 2590,
            'quantity' => 4,
        ]);

        app(AddToCartAction::class)->execute(new AddToCartData(
            productId: $product->getKey(),
            quantity: 1,
        ));

        Livewire::test(Checkout::class)
            ->assertSee('AK-47 Redline')
            ->set('email', 'buyer@example.com')
            ->set('whatsapp', '+55 11 99999-1111')
            ->call('startCheckout')
            ->assertHasNoErrors()
            ->assertRedirect('https://sandbox.mercadopago.test/checkout');

        $checkoutView = file_get_contents(resource_path('views/livewire/storefront/checkout.blade.php'));

        $this->assertStringNotContainsString('walletBrick_container', (string) $checkoutView);
        $this->assertStringNotContainsString('mercado-pago-preference-created', (string) $checkoutView);
        $this->assertDatabaseHas('orders', [
            'email' => 'buyer@example.com',
            'status' => OrderStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('payments', [
            'mercado_pago_preference_id' => 'pref_test_123',
            'amount_cents' => 2590,
        ]);
        $this->assertSame(3, $product->refresh()->quantity);
        $this->assertSame([], app(GetCurrentCartAction::class)->execute());
        $this->assertNotNull(session('checkout.pending_payment_id'));
    }

    #[Test]
    public function mercado_pago_return_pages_show_safe_buyer_guidance(): void
    {
        $this->get(route('storefront.mercado-pago.success', [
            'status' => 'approved',
            'payment_id' => '123',
            'preference_id' => 'pref_test_123',
            'external_reference' => 'cart-test-123',
        ]))
            ->assertOk()
            ->assertSee(__('storefront.payment_return.success.title'))
            ->assertSee(__('storefront.payment_return.reference_note'))
            ->assertSee('123')
            ->assertSee('pref_test_123')
            ->assertSee('cart-test-123');
    }
}
