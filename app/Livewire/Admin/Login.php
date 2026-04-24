<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Login extends Component
{
    use UsesLocalizedPageTitle;

    public function render()
    {
        return view('livewire.admin.login');
    }

    protected function titleKey(): string
    {
        return 'admin.auth.login_title';
    }
}
