<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Queries\ListAdminRaritiesQuery;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Rarities extends Component
{
    use UsesLocalizedPageTitle;

    public function render(ListAdminRaritiesQuery $listAdminRaritiesQuery)
    {
        return $this->pageView('livewire.admin.rarities', [
            'rarities' => $listAdminRaritiesQuery->execute(),
        ]);
    }

    protected function titleKey(): string
    {
        return 'admin.rarities.title';
    }
}
