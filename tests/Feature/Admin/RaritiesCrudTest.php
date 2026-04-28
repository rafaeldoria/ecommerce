<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Rarities;
use App\Livewire\Admin\Rarities\Edit as EditRarity;
use App\Models\User;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RaritiesCrudTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_create_update_and_delete_rarities_from_the_web_page(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Rarities::class)
            ->call('beginCreate')
            ->set('name', 'Arcana')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee(__('admin.rarities.messages.created'))
            ->assertSet('isFormOpen', false);

        $rarity = Rarity::query()->where('name', 'Arcana')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(EditRarity::class, ['rarity' => $rarity])
            ->assertSet('name', 'Arcana')
            ->set('name', 'Immortal')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.rarities.index'));

        $this->assertDatabaseHas('rarities', [
            'id' => $rarity->getKey(),
            'name' => 'Immortal',
        ]);

        Livewire::actingAs($admin)
            ->test(Rarities::class)
            ->call('confirmDelete', $rarity->getKey())
            ->assertSet('confirmingDeleteRarityId', $rarity->getKey())
            ->call('delete')
            ->assertSee(__('admin.rarities.messages.deleted'));

        $this->assertSoftDeleted('rarities', ['id' => $rarity->getKey()]);
    }

    #[Test]
    public function rarity_name_validation_is_shown_in_livewire(): void
    {
        $admin = User::factory()->admin()->create();
        Rarity::factory()->create(['name' => 'Arcana']);

        Livewire::actingAs($admin)
            ->test(Rarities::class)
            ->call('beginCreate')
            ->set('name', 'Arcana')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    #[Test]
    public function deleting_a_referenced_rarity_shows_blocked_feedback(): void
    {
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create();
        $rarity = Rarity::factory()->create(['name' => 'Arcana']);
        Product::factory()->create([
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        Livewire::actingAs($admin)
            ->test(Rarities::class)
            ->call('confirmDelete', $rarity->getKey())
            ->call('delete')
            ->assertSee(__('general.errors.rarity_in_use'));

        $this->assertNotSoftDeleted('rarities', ['id' => $rarity->getKey()]);
    }

    #[Test]
    public function non_admin_users_cannot_reach_the_rarities_page(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.rarities.index'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_rarities_index_is_paginated_and_links_to_dedicated_edit_pages(): void
    {
        $admin = User::factory()->admin()->create();
        Rarity::factory()->count(11)->sequence(
            fn ($sequence) => ['name' => sprintf('Rarity %02d', $sequence->index + 1)]
        )->create();

        $this->actingAs($admin)
            ->get(route('admin.rarities.index'))
            ->assertOk()
            ->assertSee('Rarity 01')
            ->assertSee(route('admin.rarities.edit', ['rarity' => Rarity::query()->where('name', 'Rarity 01')->first()]), false)
            ->assertDontSee('Rarity 11');
    }
}
