<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Catalog extends Component
{
    use UsesLocalizedPageTitle;

    public function render()
    {
        return view('livewire.storefront.catalog');
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.catalog.title';
    }
}
