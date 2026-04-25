<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'username' => 'test-user',
        ], [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => User::ROLE_CUSTOMER,
        ]);

        User::query()->updateOrCreate([
            'username' => 'admin',
        ], [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $dota = Game::query()->firstOrCreate(['name' => 'Dota 2']);
        $cs2 = Game::query()->firstOrCreate(['name' => 'CS2']);

        $arcana = Rarity::query()->firstOrCreate(['name' => 'Arcana']);
        $immortal = Rarity::query()->firstOrCreate(['name' => 'Immortal']);
        $covert = Rarity::query()->firstOrCreate(['name' => 'Covert']);
        $classified = Rarity::query()->firstOrCreate(['name' => 'Classified']);

        $this->seedDemoProducts($dota, [
            ['name' => 'Phantom Assassin Arcana', 'price' => 139900, 'rarity_id' => $arcana->id, 'quantity' => 3, 'accent' => '#14b8a6'],
            ['name' => 'Pudge Dragonclaw Hook', 'price' => 189900, 'rarity_id' => $immortal->id, 'quantity' => 2, 'accent' => '#f97316'],
            ['name' => 'Invoker Dark Artistry Set', 'price' => 124500, 'rarity_id' => $arcana->id, 'quantity' => 4, 'accent' => '#8b5cf6'],
            ['name' => 'Juggernaut Bladeform Legacy', 'price' => 118900, 'rarity_id' => $arcana->id, 'quantity' => 5, 'accent' => '#f59e0b'],
            ['name' => 'Rubick Magus Cypher Bundle', 'price' => 97900, 'rarity_id' => $immortal->id, 'quantity' => 6, 'accent' => '#22c55e'],
        ]);

        $this->seedDemoProducts($cs2, [
            ['name' => 'AK-47 Neon Rider', 'price' => 84500, 'rarity_id' => $covert->id, 'quantity' => 7, 'accent' => '#06b6d4'],
            ['name' => 'AWP Asiimov', 'price' => 162900, 'rarity_id' => $covert->id, 'quantity' => 2, 'accent' => '#f97316'],
            ['name' => 'Butterfly Knife Doppler', 'price' => 259900, 'rarity_id' => $classified->id, 'quantity' => 1, 'accent' => '#a855f7'],
            ['name' => 'M4A1-S Printstream', 'price' => 119900, 'rarity_id' => $classified->id, 'quantity' => 4, 'accent' => '#e5e7eb'],
            ['name' => 'Sport Gloves Vice', 'price' => 229900, 'rarity_id' => $covert->id, 'quantity' => 2, 'accent' => '#ec4899'],
        ]);
    }

    /**
     * @param  array<int, array{name: string, price: int, rarity_id: int, quantity: int, accent: string}>  $products
     */
    private function seedDemoProducts(Game $game, array $products): void
    {
        foreach ($products as $product) {
            Product::query()->updateOrCreate([
                'name' => $product['name'],
                'game_id' => $game->id,
            ], [
                'rarity_id' => $product['rarity_id'],
                'price' => $product['price'],
                'quantity' => $product['quantity'],
                'url_img' => $this->placeholderImage($product['name'], $product['accent'], $game->name),
            ]);
        }
    }

    private function placeholderImage(string $title, string $accent, string $subtitle): string
    {
        $label = rawurlencode(Str::limit($title, 18, ''));
        $accent = ltrim($accent, '#');
        $subtitle = rawurlencode(Str::limit($subtitle, 10, ''));

        return "https://placehold.co/640x480/0f172a/e2e8f0?text={$label}&font=montserrat&subtitle={$subtitle}&accent={$accent}";
    }
}
