<?php

namespace Tests\Feature\Frontend;

use App\Livewire\Storefront\Cart;
use App\Livewire\Storefront\ProductShow;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrontendPartThreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[Test]
    public function catalog_is_paginated_by_selected_game_and_keeps_the_game_query(): void
    {
        $game = Game::factory()->create(['name' => 'Dota 2']);
        $rarity = Rarity::factory()->create();

        Product::factory()->count(10)->sequence(
            fn ($sequence) => [
                'name' => sprintf('Dota Item %02d', $sequence->index + 1),
                'game_id' => $game->getKey(),
                'rarity_id' => $rarity->getKey(),
            ],
        )->create();

        $this->get(route('storefront.catalog', ['game' => 'dota-2']))
            ->assertOk()
            ->assertSee('Dota Item 01')
            ->assertDontSee('Dota Item 10')
            ->assertSee('game=dota-2', false);
    }

    #[Test]
    public function product_detail_adds_the_current_product_to_cart_and_cart_renders_items(): void
    {
        $game = Game::factory()->create(['name' => 'Dota 2']);
        $rarity = Rarity::factory()->create(['name' => 'Arcana']);
        $product = Product::factory()->create([
            'name' => 'Phantom Assassin Arcana',
            'price' => 139900,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        Livewire::test(ProductShow::class, ['product' => $product])
            ->call('addToCart')
            ->assertRedirect(route('storefront.cart'));

        $this->get(route('storefront.cart'))
            ->assertOk()
            ->assertSee('Phantom Assassin Arcana')
            ->assertSee('R$ 1,399.00')
            ->assertSee(__('storefront.navigation.cart_aria', ['count' => 1]), false)
            ->assertSee(__('storefront.cart.checkout'));
    }

    #[Test]
    public function cart_remove_action_updates_the_rendered_cart(): void
    {
        $product = Product::factory()->create(['name' => 'Removable Item']);

        Livewire::test(ProductShow::class, ['product' => $product])
            ->call('addToCart');

        Livewire::test(Cart::class)
            ->assertSee('Removable Item')
            ->call('removeItem', $product->getKey())
            ->assertDontSee('Removable Item')
            ->assertSee(__('storefront.cart.empty_title'));
    }

    #[Test]
    public function product_detail_redirects_to_catalog_when_product_is_deleted_before_add_to_cart(): void
    {
        $product = Product::factory()->create(['name' => 'Soon Deleted Item']);
        $component = Livewire::test(ProductShow::class, ['product' => $product]);

        $product->delete();

        $component
            ->call('addToCart')
            ->assertRedirect(route('storefront.catalog'));

        $this->assertSame(__('storefront.cart.messages.product_unavailable'), session('cart.status'));
    }

    #[Test]
    public function cart_quantity_error_is_cleared_after_successful_update(): void
    {
        $product = Product::factory()->create(['name' => 'Quantity Check Item']);

        Livewire::test(ProductShow::class, ['product' => $product])
            ->call('addToCart');

        Livewire::test(Cart::class)
            ->set("quantities.{$product->getKey()}", 0)
            ->call('updateQuantity', $product->getKey())
            ->assertHasErrors(["quantities.{$product->getKey()}"])
            ->set("quantities.{$product->getKey()}", 2)
            ->call('updateQuantity', $product->getKey())
            ->assertHasNoErrors(["quantities.{$product->getKey()}"]);
    }

    #[Test]
    public function locale_selector_persists_the_selected_language(): void
    {
        $this->get(route('storefront.locale', ['locale' => 'pt-BR']))
            ->assertRedirect();

        $this->get(route('storefront.catalog'))
            ->assertOk()
            ->assertSee('Catalogo')
            ->assertSee('🇧🇷');
    }
}
