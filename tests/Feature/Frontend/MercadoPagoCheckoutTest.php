<?php

namespace Tests\Feature\Frontend;

use App\Livewire\Storefront\Checkout;
use App\Modules\Cart\Actions\AddToCartAction;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\DTOs\AddToCartData;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
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
            ->call('createPreference')
            ->assertHasErrors(['checkout']);
    }

    #[Test]
    public function checkout_validates_contact_data_before_creating_a_preference(): void
    {
        Product::factory()->create();

        Livewire::test(Checkout::class)
            ->set('email', 'invalid-email')
            ->set('whatsapp', '123')
            ->call('createPreference')
            ->assertHasErrors(['email', 'whatsapp']);
    }

    #[Test]
    public function checkout_renders_the_wallet_container_after_creating_a_preference_without_touching_orders_or_stock(): void
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
            ->call('createPreference')
            ->assertHasNoErrors()
            ->assertSet('preferenceId', 'pref_test_123')
            ->assertSet('publicKey', 'TEST-public-key')
            ->assertSee('walletBrick_container', false);

        $checkoutView = file_get_contents(resource_path('views/livewire/storefront/checkout.blade.php'));

        $this->assertStringContainsString("\$wire.\$on('mercado-pago-preference-created'", (string) $checkoutView);
        $this->assertDatabaseCount((new Order)->getTable(), 0);
        $this->assertSame(4, $product->refresh()->quantity);
        $this->assertCount(1, app(GetCurrentCartAction::class)->execute());
    }

    #[Test]
    public function mercado_pago_return_pages_render_the_received_status_context(): void
    {
        $this->get(route('storefront.mercado-pago.success', [
            'status' => 'approved',
            'payment_id' => '123',
            'preference_id' => 'pref_test_123',
            'external_reference' => 'cart-test-123',
        ]))
            ->assertOk()
            ->assertSee('approved')
            ->assertSee('123')
            ->assertSee('pref_test_123')
            ->assertSee('cart-test-123');
    }
}
