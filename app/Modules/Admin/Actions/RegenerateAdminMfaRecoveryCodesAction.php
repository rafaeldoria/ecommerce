<?php

namespace App\Modules\Admin\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;

class RegenerateAdminMfaRecoveryCodesAction
{
    /**
     * @return array<int, string>
     */
    public function execute(User $admin): array
    {
        $recoveryCodes = Collection::times(8, fn (): string => RecoveryCode::generate())->all();

        $admin->forceFill([
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode($recoveryCodes)),
        ])->save();

        return $recoveryCodes;
    }
}
