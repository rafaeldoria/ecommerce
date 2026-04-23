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

        $response = $this->deleteJson("/api/admin/games/{$game->getKey()}");

        $this->assertProblemDetails(
            $response,
            'catalog_resource_in_use',
            422,
            __('general.errors.game_in_use'),
        );
    }

    #[Test]
    public function game_writes_are_validated(): void
    {
        $this->actingAsAdmin();
        Game::factory()->create(['name' => 'Dota 2']);

        $response = $this->postJson('/api/admin/games', [
            'name' => 'Dota 2',
        ]);

        $this->assertProblemDetails(
            $response,
            'validation_failed',
            422,
            __('general.api.errors.validation_failed'),
        );

        $response
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function soft_deleted_game_names_still_fail_validation_to_match_database_uniqueness(): void
    {
        $this->actingAsAdmin();
        $game = Game::factory()->create(['name' => 'Dota 2']);
        $game->delete();

        $response = $this->postJson('/api/admin/games', [
            'name' => 'Dota 2',
        ]);

        $this->assertProblemDetails(
            $response,
            'validation_failed',
            422,
            __('general.api.errors.validation_failed'),
        );

        $response
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function non_numeric_game_routes_return_not_found(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/admin/games/not-a-number');

        $this->assertProblemDetails(
            $response,
            'resource_not_found',
            404,
            __('general.api.errors.resource_not_found'),
        );
    }

    #[Test]
    public function anonymous_users_cannot_access_admin_games(): void
    {
        $response = $this->getJson('/api/admin/games');

        $this->assertProblemDetails(
            $response,
            'unauthenticated',
            401,
            __('general.api.errors.unauthenticated'),
        );
    }

    #[Test]
    public function customers_cannot_access_admin_games(): void
    {
        $this->actingAsCustomer();

        $response = $this->getJson('/api/admin/games');

        $this->assertProblemDetails(
            $response,
            'forbidden',
            403,
            __('general.api.errors.forbidden'),
        );
    }
}
