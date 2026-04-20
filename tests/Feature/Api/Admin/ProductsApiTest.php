<?php

namespace Tests\Feature\Api\Admin;

use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductsApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_manage_products_and_see_unavailable_items(): void
    {
        $this->actingAsAdmin();
        $game = Game::factory()->create(['name' => 'Dota 2']);
        $rarity = Rarity::factory()->create(['name' => 'Arcana']);
        $unavailableProduct = Product::factory()->create([
            'name' => 'Unavailable Arcana',
            'quantity' => 0,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        $this->getJson('/api/admin/products')
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.products.listed'))
            ->assertJsonFragment(['name' => 'Unavailable Arcana'])
            ->assertJsonFragment(['quantity' => 0]);

        $createdResponse = $this->postJson('/api/admin/products', [
            'name' => 'Phantom Assassin Arcana',
            'url_img' => 'https://example.test/pa.png',
            'quantity' => 2,
            'price' => 159900,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        $createdResponse
            ->assertCreated()
            ->assertJsonPath('message', __('general.api.admin.products.created'))
            ->assertJsonPath('data.game.id', $game->getKey())
            ->assertJsonPath('data.rarity.id', $rarity->getKey());

        $createdProductId = $createdResponse->json('data.id');

        $this->getJson("/api/admin/products/{$unavailableProduct->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.id', $unavailableProduct->getKey());

        $this->patchJson("/api/admin/products/{$createdProductId}", [
            'name' => 'Phantom Assassin Arcana Updated',
            'url_img' => 'https://example.test/pa-updated.png',
            'quantity' => 4,
            'price' => 169900,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ])
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.products.updated'))
            ->assertJsonPath('data.quantity', 4)
            ->assertJsonPath('data.price', 169900);

        $this->deleteJson("/api/admin/products/{$createdProductId}")
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.products.deleted'));

        $this->assertSoftDeleted('products', ['id' => $createdProductId]);
    }

    #[Test]
    public function product_writes_validate_references_and_numeric_rules(): void
    {
        $this->actingAsAdmin();
        $game = Game::factory()->create();
        $game->delete();
        $rarity = Rarity::factory()->create();
        $rarity->delete();

        $this->postJson('/api/admin/products', [
            'name' => 'Broken Product',
            'url_img' => 'https://example.test/item.png',
            'quantity' => -1,
            'price' => -20,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonValidationErrors(['quantity', 'price', 'game_id', 'rarity_id']);
    }

    #[Test]
    public function public_catalog_keeps_hiding_zero_quantity_products(): void
    {
        $this->actingAsAdmin();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();
        Product::factory()->create([
            'name' => 'Hidden Product',
            'quantity' => 0,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        $this->getJson('/api/catalog/products')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Hidden Product']);
    }

    #[Test]
    public function anonymous_users_cannot_access_admin_products(): void
    {
        $this->getJson('/api/admin/products')
            ->assertUnauthorized()
            ->assertJsonPath('error', 'unauthenticated');
    }

    #[Test]
    public function customers_cannot_access_admin_products(): void
    {
        $this->actingAsCustomer();

        $this->getJson('/api/admin/products')
            ->assertForbidden()
            ->assertJsonPath('error', 'forbidden');
    }
}
