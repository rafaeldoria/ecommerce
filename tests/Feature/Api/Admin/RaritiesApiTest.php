<?php

namespace Tests\Feature\Api\Admin;

use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RaritiesApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_manage_rarities(): void
    {
        $this->actingAsAdmin();
        $rarity = Rarity::factory()->create(['name' => 'Arcana']);

        $this->getJson('/api/admin/rarities')
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.rarities.listed'))
            ->assertJsonFragment(['name' => 'Arcana']);

        $createdResponse = $this->postJson('/api/admin/rarities', [
            'name' => 'Immortal',
        ]);

        $createdResponse
            ->assertCreated()
            ->assertJsonPath('message', __('general.api.admin.rarities.created'))
            ->assertJsonPath('data.name', 'Immortal');

        $createdRarityId = $createdResponse->json('data.id');

        $this->getJson("/api/admin/rarities/{$rarity->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.id', $rarity->getKey());

        $this->patchJson("/api/admin/rarities/{$createdRarityId}", [
            'name' => 'Legendary',
        ])
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.rarities.updated'))
            ->assertJsonPath('data.name', 'Legendary');

        $this->deleteJson("/api/admin/rarities/{$createdRarityId}")
            ->assertOk()
            ->assertJsonPath('message', __('general.api.admin.rarities.deleted'));

        $this->assertSoftDeleted('rarities', ['id' => $createdRarityId]);
    }

    #[Test]
    public function rarity_delete_is_blocked_when_products_still_reference_it(): void
    {
        $this->actingAsAdmin();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create();
        Product::factory()->create([
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        $this->deleteJson("/api/admin/rarities/{$rarity->getKey()}")
            ->assertUnprocessable()
            ->assertJsonPath('error', 'catalog_resource_in_use')
            ->assertJsonPath('message', __('general.errors.rarity_in_use'));
    }

    #[Test]
    public function rarity_writes_are_validated(): void
    {
        $this->actingAsAdmin();
        Rarity::factory()->create(['name' => 'Arcana']);

        $this->postJson('/api/admin/rarities', [
            'name' => 'Arcana',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function soft_deleted_rarity_names_still_fail_validation_to_match_database_uniqueness(): void
    {
        $this->actingAsAdmin();
        $rarity = Rarity::factory()->create(['name' => 'Arcana']);
        $rarity->delete();

        $this->postJson('/api/admin/rarities', [
            'name' => 'Arcana',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('error', 'validation_failed')
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function anonymous_users_cannot_access_admin_rarities(): void
    {
        $this->getJson('/api/admin/rarities')
            ->assertUnauthorized()
            ->assertJsonPath('error', 'unauthenticated');
    }

    #[Test]
    public function customers_cannot_access_admin_rarities(): void
    {
        $this->actingAsCustomer();

        $this->getJson('/api/admin/rarities')
            ->assertForbidden()
            ->assertJsonPath('error', 'forbidden');
    }
}
