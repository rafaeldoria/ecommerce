<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Models\User;
use App\Modules\Admin\Actions\ConfirmAdminMfaSetupAction;
use App\Modules\Admin\Actions\DisableAdminMfaAction;
use App\Modules\Admin\Actions\RegenerateAdminMfaRecoveryCodesAction;
use App\Modules\Admin\Actions\StartAdminMfaSetupAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Security extends Component
{
    use UsesLocalizedPageTitle;

    public bool $setupInProgress = false;

    public string $qrCodeSvg = '';

    public string $qrCodeUrl = '';

    /** @var array<int, string> */
    public array $recoveryCodes = [];

    public string $confirmationCode = '';

    public string $disablePassword = '';

    public ?string $statusMessage = null;

    public function mount(StartAdminMfaSetupAction $startAdminMfaSetupAction): void
    {
        $admin = $this->admin();

        if ($admin->hasConfirmedMfa()) {
            return;
        }

        if ((bool) config('security.admin_mfa.required', false)) {
            $this->startSetup($startAdminMfaSetupAction);
        }
    }

    public function startSetup(StartAdminMfaSetupAction $startAdminMfaSetupAction): void
    {
        $result = $startAdminMfaSetupAction->execute($this->admin());

        $this->setupInProgress = true;
        $this->qrCodeSvg = $result->qrCodeSvg;
        $this->qrCodeUrl = $result->qrCodeUrl;
        $this->recoveryCodes = [];
        $this->confirmationCode = '';
        $this->statusMessage = null;
    }

    public function confirmSetup(ConfirmAdminMfaSetupAction $confirmAdminMfaSetupAction): void
    {
        $validated = $this->validate([
            'confirmationCode' => ['required', 'string'],
        ]);

        $confirmAdminMfaSetupAction->execute($this->admin(), trim((string) $validated['confirmationCode']));

        $admin = $this->admin()->refresh();
        Auth::setUser($admin);

        $this->setupInProgress = false;
        $this->confirmationCode = '';
        $this->recoveryCodes = $admin->recoveryCodes();
        $this->statusMessage = __('admin.security.messages.enabled');
    }

    public function regenerateRecoveryCodes(RegenerateAdminMfaRecoveryCodesAction $regenerateAdminMfaRecoveryCodesAction): void
    {
        $this->recoveryCodes = $regenerateAdminMfaRecoveryCodesAction->execute($this->admin());
        $this->statusMessage = __('admin.security.messages.recovery_codes_regenerated');
    }

    public function disableMfa(DisableAdminMfaAction $disableAdminMfaAction): void
    {
        if ((bool) config('security.admin_mfa.required', false)) {
            throw ValidationException::withMessages([
                'disablePassword' => __('admin.security.errors.disable_blocked_when_required'),
            ]);
        }

        $validated = $this->validate([
            'disablePassword' => ['required', 'string'],
        ]);

        $disableAdminMfaAction->execute($this->admin(), (string) $validated['disablePassword']);

        $this->setupInProgress = false;
        $this->qrCodeSvg = '';
        $this->qrCodeUrl = '';
        $this->recoveryCodes = [];
        $this->disablePassword = '';
        $this->statusMessage = __('admin.security.messages.disabled');
    }

    public function render()
    {
        return $this->pageView('livewire.admin.security', [
            'admin' => $this->admin(),
            'mfaRequired' => (bool) config('security.admin_mfa.required', false),
        ]);
    }

    protected function titleKey(): string
    {
        return 'admin.security.title';
    }

    private function admin(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->isAdmin(), 403);

        return $user;
    }
}
