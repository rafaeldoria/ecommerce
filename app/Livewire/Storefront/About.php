<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class About extends Component
{
    use UsesLocalizedPageTitle;

    public function render()
    {
        return $this->pageView('livewire.storefront.about');
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.about.title';
    }
}
