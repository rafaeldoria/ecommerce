<?php

namespace App\Modules\Admin\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class VerifyAdminMfaChallengeAction
{
    public function __construct(
        private readonly TwoFactorAuthenticationProvider $provider,
    ) {}

    public function execute(User $admin, ?string $code, ?string $recoveryCode): void
    {
        if (!$admin->hasConfirmedMfa() || $admin->two_factor_secret === null) {
            throw ValidationException::withMessages([
                'code' => __('admin.security.errors.setup_required'),
            ]);
        }

        if ($code !== null && $code !== '') {
            $this->verifyTotpCode($admin, $code);

            return;
        }

        if ($recoveryCode !== null && $recoveryCode !== '') {
            $this->verifyRecoveryCode($admin, $recoveryCode);

            return;
        }

        throw ValidationException::withMessages([
            'code' => __('admin.security.errors.challenge_required'),
        ]);
    }

    private function verifyTotpCode(User $admin, string $code): void
    {
        $secret = Fortify::currentEncrypter()->decrypt((string) $admin->two_factor_secret);

        if ($this->provider->verify($secret, $code)) {
            return;
        }

        throw ValidationException::withMessages([
            'code' => __('admin.security.errors.invalid_code'),
        ]);
    }

    private function verifyRecoveryCode(User $admin, string $recoveryCode): void
    {
        foreach ($admin->recoveryCodes() as $knownCode) {
            if (!hash_equals($knownCode, $recoveryCode)) {
                continue;
            }

            $admin->replaceRecoveryCode($knownCode);

            return;
        }

        throw ValidationException::withMessages([
            'recovery_code' => __('admin.security.errors.invalid_recovery_code'),
        ]);
    }
}
