<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Actions\CreateRarityAction;
use App\Modules\Catalog\Actions\DeleteRarityAction;
use App\Modules\Catalog\Actions\UpdateRarityAction;
use App\Modules\Catalog\Exceptions\CatalogResourceInUse;
use App\Modules\Catalog\Models\Rarity;
use App\Modules\Catalog\Queries\ListAdminRaritiesQuery;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Rarities extends Component
{
    use UsesLocalizedPageTitle;

    public string $name = '';

    public ?int $editingRarityId = null;

    public ?int $confirmingDeleteRarityId = null;

    public bool $isFormOpen = false;

    public ?string $statusMessage = null;

    public string $statusTone = 'success';

    public function render(ListAdminRaritiesQuery $listAdminRaritiesQuery)
    {
        return $this->pageView('livewire.admin.rarities', [
            'rarities' => $listAdminRaritiesQuery->execute(),
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        if ($this->editingRarityId === null) {
            app(CreateRarityAction::class)->execute((string) $validated['name']);
            $this->flashStatus(__('admin.rarities.messages.created'));
        } else {
            app(UpdateRarityAction::class)->execute($this->editingRarityId, (string) $validated['name']);
            $this->flashStatus(__('admin.rarities.messages.updated'));
        }

        $this->resetForm();
    }

    public function beginCreate(): void
    {
        $this->resetForm();
        $this->clearStatus();
        $this->isFormOpen = true;
    }

    public function edit(int $rarityId): void
    {
        $rarity = Rarity::query()->findOrFail($rarityId);

        $this->editingRarityId = $rarity->getKey();
        $this->confirmingDeleteRarityId = null;
        $this->isFormOpen = true;
        $this->name = $rarity->name;
        $this->resetValidation();
        $this->clearStatus();
    }

    public function confirmDelete(int $rarityId): void
    {
        $this->confirmingDeleteRarityId = $rarityId;
        $this->clearStatus();
    }

    public function delete(): void
    {
        if ($this->confirmingDeleteRarityId === null) {
            return;
        }

        try {
            app(DeleteRarityAction::class)->execute($this->confirmingDeleteRarityId);
            $this->flashStatus(__('admin.rarities.messages.deleted'));
            $this->resetForm();
        } catch (CatalogResourceInUse $exception) {
            $this->flashStatus($exception->getMessage(), 'danger');
        } finally {
            $this->confirmingDeleteRarityId = null;
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->clearStatus();
    }

    protected function titleKey(): string
    {
        return 'admin.rarities.title';
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
                Rule::unique(Rarity::class, 'name')->ignore($this->editingRarityId),
            ],
        ];
    }

    private function resetForm(): void
    {
        $this->reset('name', 'editingRarityId', 'confirmingDeleteRarityId', 'isFormOpen');
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
