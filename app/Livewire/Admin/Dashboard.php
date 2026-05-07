<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Admin\Queries\GetAdminDashboardStatsQuery;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    use UsesLocalizedPageTitle;

    public array $stats = [];

    public function render(GetAdminDashboardStatsQuery $getAdminDashboardStatsQuery)
    {
        $this->stats = $getAdminDashboardStatsQuery->execute();

        return $this->pageView('livewire.admin.dashboard');
    }

    protected function titleKey(): string
    {
        return 'admin.dashboard.title';
    }
}
