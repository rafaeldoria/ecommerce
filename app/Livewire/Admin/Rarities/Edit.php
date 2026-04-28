<?php

namespace App\Livewire\Admin\Rarities;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Actions\UpdateRarityAction;
use App\Modules\Catalog\Models\Rarity;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    use UsesLocalizedPageTitle;

    public Rarity $rarity;

    public string $name = '';

    public function mount(Rarity $rarity): void
    {
        $this->rarity = $rarity;
        $this->name = $rarity->name;
    }

    public function save(UpdateRarityAction $updateRarityAction)
    {
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Rarity::class, 'name')->ignore($this->rarity->getKey()),
            ],
        ]);

        $updateRarityAction->execute($this->rarity->getKey(), (string) $validated['name']);

        session()->flash('admin.status', __('admin.rarities.messages.updated'));

        return $this->redirectRoute('admin.rarities.index');
    }

    public function render()
    {
        return $this->pageView('livewire.admin.rarities.edit');
    }

    protected function titleKey(): string
    {
        return 'admin.rarities.edit_title';
    }
}
