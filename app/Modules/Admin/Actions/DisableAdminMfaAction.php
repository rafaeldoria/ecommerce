<?php

namespace App\Modules\Admin\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DisableAdminMfaAction
{
    public function execute(User $admin, string $password): void
    {
        if (!Hash::check($password, $admin->password)) {
            throw ValidationException::withMessages([
                'disablePassword' => __('admin.security.errors.invalid_password'),
            ]);
        }

        $admin->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }
}
