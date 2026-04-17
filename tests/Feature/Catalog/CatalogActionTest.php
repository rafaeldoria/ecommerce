<?php

namespace Tests\Feature\Catalog;

use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Catalog\DTOs\CreateProductData;
use App\Modules\Catalog\DTOs\UpdateProductData;
use App\Modules\Catalog\Exceptions\InvalidProductData;
use App\Modules\Catalog\Exceptions\InvalidProductReference;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function create_product_action_persists_a_product_with_game_and_rarity(): void
    {
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();

        $product = app(CreateProductAction::class)->execute(new CreateProductData(
            name: 'Dragonclaw Hook',
            urlImg: 'https://example.com/hook.png',
            quantity: 5,
            price: 159900,
            gameId: $game->getKey(),
            rarityId: $rarity->getKey(),
        ));

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'name' => 'Dragonclaw Hook',
            'price' => 159900,
            'quantity' => 5,
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);
    }

    #[Test]
    public function create_product_action_rejects_a_missing_game(): void
    {
        $rarity = Rarity::factory()->create();

        $this->expectException(InvalidProductReference::class);

        app(CreateProductAction::class)->execute(new CreateProductData(
            name: 'Arcana',
            urlImg: 'https://example.com/arcana.png',
            quantity: 2,
            price: 9999,
            gameId: 999,
            rarityId: $rarity->getKey(),
        ));
    }

    #[Test]
    public function create_product_action_rejects_a_missing_rarity(): void
    {
        $game = Game::factory()->create();

        $this->expectException(InvalidProductReference::class);

        app(CreateProductAction::class)->execute(new CreateProductData(
            name: 'Arcana',
            urlImg: 'https://example.com/arcana.png',
            quantity: 2,
            price: 9999,
            gameId: $game->getKey(),
            rarityId: 999,
        ));
    }

    #[Test]
    public function create_product_action_rejects_negative_price_or_quantity(): void
    {
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();

        $this->expectException(InvalidProductData::class);

        app(CreateProductAction::class)->execute(new CreateProductData(
            name: 'Arcana',
            urlImg: 'https://example.com/arcana.png',
            quantity: -1,
            price: 9999,
            gameId: $game->getKey(),
            rarityId: $rarity->getKey(),
        ));
    }

    #[Test]
    public function update_product_action_updates_the_product_without_introducing_category(): void
    {
        $product = Product::factory()->create([
            'name' => 'Old Name',
        ]);
        $newGame = Game::factory()->create();
        $newRarity = Rarity::factory()->create();

        $updatedProduct = app(UpdateProductAction::class)->execute(
            $product->getKey(),
            new UpdateProductData(
                name: 'Updated Name',
                urlImg: 'https://example.com/updated.png',
                quantity: 8,
                price: 250000,
                gameId: $newGame->getKey(),
                rarityId: $newRarity->getKey(),
            ),
        );

        $this->assertSame('Updated Name', $updatedProduct->name);
        $this->assertSame(8, $updatedProduct->quantity);
        $this->assertSame(250000, $updatedProduct->price);
        $this->assertArrayNotHasKey('category', $updatedProduct->toArray());
    }

    #[Test]
    public function update_product_action_fails_for_unknown_products(): void
    {
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(UpdateProductAction::class)->execute(
            999,
            new UpdateProductData(
                name: 'Updated Name',
                urlImg: 'https://example.com/updated.png',
                quantity: 8,
                price: 250000,
                gameId: $game->getKey(),
                rarityId: $rarity->getKey(),
            ),
        );
    }
}
