<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Games;
use App\Livewire\Admin\Games\Edit as EditGame;
use App\Models\User;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GamesCrudTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_create_update_and_delete_games_from_the_web_page(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Games::class)
            ->call('beginCreate')
            ->set('name', 'Counter-Strike 2')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee(__('admin.games.messages.created'))
            ->assertSet('isFormOpen', false);

        $game = Game::query()->where('name', 'Counter-Strike 2')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(EditGame::class, ['game' => $game])
            ->assertSet('name', 'Counter-Strike 2')
            ->set('name', 'CS2')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.games.index'));

        $this->assertDatabaseHas('games', [
            'id' => $game->getKey(),
            'name' => 'CS2',
        ]);

        Livewire::actingAs($admin)
            ->test(Games::class)
            ->call('confirmDelete', $game->getKey())
            ->assertSet('confirmingDeleteGameId', $game->getKey())
            ->call('delete')
            ->assertSee(__('admin.games.messages.deleted'));

        $this->assertSoftDeleted('games', ['id' => $game->getKey()]);
    }

    #[Test]
    public function game_name_validation_is_shown_in_livewire(): void
    {
        $admin = User::factory()->admin()->create();
        Game::factory()->create(['name' => 'Dota 2']);

        Livewire::actingAs($admin)
            ->test(Games::class)
            ->call('beginCreate')
            ->set('name', 'Dota 2')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    #[Test]
    public function deleting_a_referenced_game_shows_blocked_feedback(): void
    {
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->create(['name' => 'Dota 2']);
        $rarity = Rarity::factory()->create();
        Product::factory()->create([
            'game_id' => $game->getKey(),
            'rarity_id' => $rarity->getKey(),
        ]);

        Livewire::actingAs($admin)
            ->test(Games::class)
            ->call('confirmDelete', $game->getKey())
            ->call('delete')
            ->assertSee(__('general.errors.game_in_use'));

        $this->assertNotSoftDeleted('games', ['id' => $game->getKey()]);
    }

    #[Test]
    public function non_admin_users_cannot_reach_the_games_page(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.games.index'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_games_index_is_paginated_and_links_to_dedicated_edit_pages(): void
    {
        $admin = User::factory()->admin()->create();
        Game::factory()->count(11)->sequence(
            fn ($sequence) => ['name' => sprintf('Game %02d', $sequence->index + 1)]
        )->create();

        $this->actingAs($admin)
            ->get(route('admin.games.index'))
            ->assertOk()
            ->assertSee('Game 01')
            ->assertSee(route('admin.games.edit', ['game' => Game::query()->where('name', 'Game 01')->first()]), false)
            ->assertDontSee('Game 11');
    }
}
