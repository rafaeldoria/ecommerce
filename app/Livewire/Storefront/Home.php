<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Home extends Component
{
    use UsesLocalizedPageTitle;

    public function render()
    {
        return view('livewire.storefront.home');
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.home.title';
    }
}
