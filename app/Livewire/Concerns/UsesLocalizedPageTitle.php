<?php

namespace App\Livewire\Concerns;

trait UsesLocalizedPageTitle
{
    public function title(): string
    {
        return (string) __($this->titleKey());
    }

    abstract protected function titleKey(): string;
}
