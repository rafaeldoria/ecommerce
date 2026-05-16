<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Models\User;
use App\Modules\Admin\Actions\ChangeAdminPasswordAction;
use App\Modules\Admin\DTOs\ChangeAdminPasswordData;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ChangePassword extends Component
{
    use UsesLocalizedPageTitle;

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public string $mfaCode = '';

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->admin();
    }

    public function changePassword(ChangeAdminPasswordAction $changeAdminPasswordAction): void
    {
        $validated = $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => [
                'required',
                'string',
                'min:6',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
            'mfaCode' => ['required', 'string'],
        ]);

        $changeAdminPasswordAction->execute($this->admin(), new ChangeAdminPasswordData(
            currentPassword: (string) $validated['currentPassword'],
            newPassword: (string) $validated['newPassword'],
            mfaCode: trim((string) $validated['mfaCode']),
        ));

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->mfaCode = '';
        $this->statusMessage = __('admin.password.messages.changed');
    }

    public function render()
    {
        return $this->pageView('livewire.admin.change-password');
    }

    protected function titleKey(): string
    {
        return 'admin.password.title';
    }

    private function admin(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->isAdmin() && $user->hasConfirmedMfa(), 403);

        return $user;
    }
}
