<?php

namespace App\Modules\Admin\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class ConfirmAdminMfaSetupAction
{
    public function __construct(
        private readonly TwoFactorAuthenticationProvider $provider,
    ) {}

    public function execute(User $admin, string $code): void
    {
        if ($admin->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'code' => __('admin.security.errors.setup_required'),
            ]);
        }

        $secret = Fortify::currentEncrypter()->decrypt($admin->two_factor_secret);

        if (!$this->provider->verify($secret, $code)) {
            throw ValidationException::withMessages([
                'code' => __('admin.security.errors.invalid_code'),
            ]);
        }

        $admin->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();
    }
}
