<?php

namespace Tests\Feature\Api;

use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_available_products_with_filter_metadata(): void
    {
        $game = Game::factory()->create(['name' => 'Dota 2']);
        $rarity = Rarity::factory()->create(['name' => 'Arcana']);
        $matchingProduct = Product::factory()->create([
            'name' => 'Phantom Assassin Arcana',
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
            'quantity' => 4,
        ]);

        Product::factory()->create([
            'quantity' => 0,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        $response = $this->getJson("/api/catalog/products?game_id={$game->getKey()}&rarity_id={$rarity->getKey()}");

        $response
            ->assertOk()
            ->assertJsonPath('message', __('general.api.catalog.products_listed'))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingProduct->getKey())
            ->assertJsonPath('data.0.game.id', $game->getKey())
            ->assertJsonPath('data.0.rarity.id', $rarity->getKey())
            ->assertJsonFragment(['name' => 'Dota 2'])
            ->assertJsonFragment(['name' => 'Arcana']);
    }

    #[Test]
    public function it_validates_catalog_filters(): void
    {
        $response = $this->getJson('/api/catalog/products?game_id=999');

        $this->assertProblemDetails(
            $response,
            'validation_failed',
            422,
            __('general.api.errors.validation_failed'),
        );

        $response
            ->assertJsonValidationErrors(['game_id']);
    }
}
