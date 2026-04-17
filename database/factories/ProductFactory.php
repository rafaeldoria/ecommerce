<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'url_img' => fake()->imageUrl(),
            'quantity' => fake()->numberBetween(1, 10),
            'price' => fake()->numberBetween(100, 250000),
            'game_id' => Game::factory(),
            'rarity_id' => Rarity::factory(),
        ];
    }
}
