<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
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
                'status' => Order::STATUS_PENDING_FULFILLMENT,
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
            ->assertDontSee('buyer01@example.com');
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
