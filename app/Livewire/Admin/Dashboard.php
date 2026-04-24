<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    use UsesLocalizedPageTitle;

    public function render()
    {
        return view('livewire.admin.dashboard');
    }

    protected function titleKey(): string
    {
        return 'admin.dashboard.title';
    }
}
