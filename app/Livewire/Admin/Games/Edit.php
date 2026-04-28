<?php

namespace App\Livewire\Admin\Games;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Actions\UpdateGameAction;
use App\Modules\Catalog\Models\Game;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    use UsesLocalizedPageTitle;

    public Game $game;

    public string $name = '';

    public function mount(Game $game): void
    {
        $this->game = $game;
        $this->name = $game->name;
    }

    public function save(UpdateGameAction $updateGameAction)
    {
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Game::class, 'name')->ignore($this->game->getKey()),
            ],
        ]);

        $updateGameAction->execute($this->game->getKey(), (string) $validated['name']);

        session()->flash('admin.status', __('admin.games.messages.updated'));

        return $this->redirectRoute('admin.games.index');
    }

    public function render()
    {
        return $this->pageView('livewire.admin.games.edit');
    }

    protected function titleKey(): string
    {
        return 'admin.games.edit_title';
    }
}
