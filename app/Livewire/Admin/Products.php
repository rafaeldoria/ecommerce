<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Catalog\Queries\ListAdminProductsQuery;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Products extends Component
{
    use UsesLocalizedPageTitle;

    public function render(ListAdminProductsQuery $listAdminProductsQuery)
    {
        return $this->pageView('livewire.admin.products', [
            'products' => $listAdminProductsQuery->execute(),
        ]);
    }

    protected function titleKey(): string
    {
        return 'admin.products.title';
    }
}
