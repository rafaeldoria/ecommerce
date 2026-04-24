<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Contact extends Component
{
    use UsesLocalizedPageTitle;

    public function render()
    {
        return $this->pageView('livewire.storefront.contact');
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.contact.title';
    }
}
