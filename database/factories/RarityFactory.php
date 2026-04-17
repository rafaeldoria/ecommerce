<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Rarity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rarity>
 */
class RarityFactory extends Factory
{
    protected $model = Rarity::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
        ];
    }
}
