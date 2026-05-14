<?php

namespace App\Modules\Admin\Actions;

use App\Models\User;
use App\Modules\Admin\DTOs\AdminMfaSetupResult;
use Illuminate\Support\Collection;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;

class StartAdminMfaSetupAction
{
    public function __construct(
        private readonly TwoFactorAuthenticationProvider $provider,
    ) {}

    public function execute(User $admin): AdminMfaSetupResult
    {
        if ($admin->hasConfirmedMfa()) {
            return new AdminMfaSetupResult('', '', $admin->recoveryCodes());
        }

        $secretLength = (int) config('fortify-options.two-factor-authentication.secret-length', 16);
        $secret = $this->provider->generateSecretKey($secretLength > 0 ? $secretLength : 16);
        $recoveryCodes = Collection::times(8, fn (): string => RecoveryCode::generate())->all();

        $admin->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => null,
        ])->save();

        return new AdminMfaSetupResult(
            qrCodeSvg: $admin->twoFactorQrCodeSvg(),
            qrCodeUrl: $admin->twoFactorQrCodeUrl(),
            recoveryCodes: $recoveryCodes,
        );
    }
}
