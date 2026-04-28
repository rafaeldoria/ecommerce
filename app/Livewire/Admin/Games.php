<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Actions\CreateGameAction;
use App\Modules\Catalog\Actions\DeleteGameAction;
use App\Modules\Catalog\Exceptions\CatalogResourceInUse;
use App\Modules\Catalog\Models\Game;
use App\Modules\Catalog\Queries\ListAdminGamesQuery;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Games extends Component
{
    use UsesLocalizedPageTitle;
    use WithPagination;

    public string $name = '';

    public ?int $confirmingDeleteGameId = null;

    public bool $isFormOpen = false;

    public ?string $statusMessage = null;

    public string $statusTone = 'success';

    public function render(ListAdminGamesQuery $listAdminGamesQuery)
    {
        return $this->pageView('livewire.admin.games', [
            'games' => $listAdminGamesQuery->executePaginated(10),
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        app(CreateGameAction::class)->execute((string) $validated['name']);
        $this->flashStatus(__('admin.games.messages.created'));

        $this->resetForm();
        $this->resetPage();
    }

    public function beginCreate(): void
    {
        $this->resetForm();
        $this->clearStatus();
        $this->isFormOpen = true;
    }

    public function confirmDelete(int $gameId): void
    {
        $this->confirmingDeleteGameId = $gameId;
        $this->clearStatus();
    }

    public function delete(): void
    {
        if ($this->confirmingDeleteGameId === null) {
            return;
        }

        try {
            app(DeleteGameAction::class)->execute($this->confirmingDeleteGameId);
            $this->flashStatus(__('admin.games.messages.deleted'));
            $this->resetForm();
            $this->resetPage();
        } catch (CatalogResourceInUse $exception) {
            $this->flashStatus($exception->getMessage(), 'danger');
        } finally {
            $this->confirmingDeleteGameId = null;
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->clearStatus();
    }

    protected function titleKey(): string
    {
        return 'admin.games.title';
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Game::class, 'name'),
            ],
        ];
    }

    private function resetForm(): void
    {
        $this->reset('name', 'confirmingDeleteGameId', 'isFormOpen');
        $this->resetValidation();
    }

    private function flashStatus(string $message, string $tone = 'success'): void
    {
        $this->statusMessage = $message;
        $this->statusTone = $tone;
    }

    private function clearStatus(): void
    {
        $this->statusMessage = null;
        $this->statusTone = 'success';
    }
}
