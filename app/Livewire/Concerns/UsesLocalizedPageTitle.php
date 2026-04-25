<?php

namespace App\Livewire\Concerns;

use Illuminate\Contracts\View\View;

trait UsesLocalizedPageTitle
{
    public function title(): string
    {
        return (string) __($this->titleKey());
    }

    protected function pageView(string $view, array $data = []): View
    {
        return view($view, $data)->title($this->title());
    }

    abstract protected function titleKey(): string;
}
