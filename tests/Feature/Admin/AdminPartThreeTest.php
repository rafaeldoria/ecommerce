<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Payments\Enums\PaymentProvider;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\MercadoPagoWebhookRequest;
use App\Modules\Payments\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPartThreeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dashboard_stat_cards_link_to_admin_modules(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.games.index'), false)
            ->assertSee(route('admin.rarities.index'), false)
            ->assertSee(route('admin.products.index'), false)
            ->assertSee(route('admin.orders.index'), false);
    }

    #[Test]
    public function admin_orders_are_paginated(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            $order = Order::query()->create([
                'email' => sprintf('buyer%02d@example.com', $index),
                'whatsapp' => '+55 11 99999-0000',
                'status' => OrderStatus::PendingFulfillment->value,
            ]);
            $order->forceFill([
                'created_at' => now()->addMinutes($index),
                'updated_at' => now()->addMinutes($index),
            ])->save();

            OrderItem::query()->create([
                'order_id' => $order->getKey(),
                'product_id' => $product->getKey(),
                'product_name' => $product->name,
                'unit_price' => $product->price,
                'quantity' => 1,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('buyer11@example.com')
            ->assertSee(__('admin.orders.open_detail'))
            ->assertDontSee('buyer01@example.com');
    }

    #[Test]
    public function admin_order_detail_shows_sanitized_payment_verification_state(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['name' => 'Dragonclaw Hook']);
        $order = Order::query()->create([
            'email' => 'buyer@example.com',
            'whatsapp' => '+55 11 99999-0000',
            'status' => OrderStatus::Paid->value,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'product_name' => $product->name,
            'unit_price' => 159900,
            'quantity' => 1,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->getKey(),
            'provider' => PaymentProvider::MercadoPago->value,
            'provider_preference_id' => 'pref_test_123',
            'provider_payment_id' => 'mp-payment-123',
            'external_reference' => 'payment-external-reference-123',
            'checkout_url' => 'https://www.mercadopago.com.br/checkout/v1/redirect',
            'amount_cents' => 159900,
            'currency' => 'BRL',
            'status' => PaymentStatus::Approved->value,
            'provider_status' => 'approved',
            'provider_status_detail' => 'accredited',
            'raw_provider_snapshot' => [
                'card' => ['first_six_digits' => '411111'],
                'authorization' => 'Bearer secret-token',
            ],
        ]);

        MercadoPagoWebhookRequest::query()->create([
            'received_at' => now(),
            'processing_status' => 'processed',
            'http_status_returned' => 200,
            'event_type' => 'payment',
            'data_id' => 'mp-payment-123',
            'signature_valid' => true,
            'related_payment_id' => $payment->getKey(),
            'provider_payment_id' => 'mp-payment-123',
            'processed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', ['order' => $order]))
            ->assertOk()
            ->assertSee('Payment verification')
            ->assertSee('Fulfillment allowed')
            ->assertSee('approved')
            ->assertSee('mp-payment-123')
            ->assertSee('accredited')
            ->assertSee('processed')
            ->assertDontSee('411111')
            ->assertDontSee('Bearer secret-token');
    }

    #[Test]
    public function dedicated_edit_pages_remain_admin_protected(): void
    {
        $customer = User::factory()->customer()->create();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();
        $product = Product::factory()->create([
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        $this->actingAs($customer)->get(route('admin.games.edit', ['game' => $game]))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.rarities.edit', ['rarity' => $rarity]))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.products.edit', ['product' => $product]))->assertForbidden();
    }
}
