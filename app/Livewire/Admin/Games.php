<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Queries\ListAdminGamesQuery;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Games extends Component
{
    use UsesLocalizedPageTitle;

    public function render(ListAdminGamesQuery $listAdminGamesQuery)
    {
        return view('livewire.admin.games', [
            'games' => $listAdminGamesQuery->execute(),
        ]);
    }

    protected function titleKey(): string
    {
        return 'admin.games.title';
    }
}
