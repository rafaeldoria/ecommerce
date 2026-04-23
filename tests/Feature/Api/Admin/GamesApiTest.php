<?php

namespace Tests\Feature\Api\Admin;

use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GamesApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_manage_games(): void
    {
        $this->actingAsAdmin();
        $game = Game::factory()->create(['name' => 'Dota 2']);

        $this->getJson('/api/admin/games')
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.games.listed'))
            ->assertJsonFragment(['name' => 'Dota 2']);

        $createdResponse = $this->postJson('/api/admin/games', [
            'name' => 'Counter-Strike 2',
        ]);

        $createdResponse
            ->assertCreated()
            ->assertJsonPath('message', __('general.api.admin.games.created'))
            ->assertJsonPath('data.name', 'Counter-Strike 2');

        $createdGameId = $createdResponse->json('data.id');

        $this->getJson("/api/admin/games/{$game->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.id', $game->getKey());

        $this->patchJson("/api/admin/games/{$createdGameId}", [
            'name' => 'CS2',
        ])
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.games.updated'))
            ->assertJsonPath('data.name', 'CS2');

        $this->deleteJson("/api/admin/games/{$createdGameId}")
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.games.deleted'));

        $this->assertSoftDeleted('games', ['id' => $createdGameId]);
    }

    #[Test]
    public function game_delete_is_blocked_when_products_still_reference_it(): void
    {
        $this->actingAsAdmin();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();
        Product::factory()->create([
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        $this->deleteJson("/api/admin/games/{$game->getKey()}")
            ->assertUnprocessable()
            ->assertJsonPath('error', 'catalog_resource_in_use')
            ->assertJsonPath('message', __('general.errors.game_in_use'));
    }

    #[Test]
    public function game_writes_are_validated(): void
    {
        $this->actingAsAdmin();
        Game::factory()->create(['name' => 'Dota 2']);

        $this->postJson('/api/admin/games', [
            'name' => 'Dota 2',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function soft_deleted_game_names_still_fail_validation_to_match_database_uniqueness(): void
    {
        $this->actingAsAdmin();
        $game = Game::factory()->create(['name' => 'Dota 2']);
        $game->delete();

        $this->postJson('/api/admin/games', [
            'name' => 'Dota 2',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function non_numeric_game_routes_return_not_found(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/admin/games/not-a-number')
            ->assertNotFound()
            ->assertJsonPath('error', 'resource_not_found');
    }

    #[Test]
    public function anonymous_users_cannot_access_admin_games(): void
    {
        $this->getJson('/api/admin/games')
            ->assertUnauthorized()
            ->assertJsonPath('error', 'unauthenticated');
    }

    #[Test]
    public function customers_cannot_access_admin_games(): void
    {
        $this->actingAsCustomer();

        $this->getJson('/api/admin/games')
            ->assertForbidden()
            ->assertJsonPath('error', 'forbidden');
    }
}
