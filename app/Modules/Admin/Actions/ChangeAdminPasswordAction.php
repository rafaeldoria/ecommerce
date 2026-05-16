<?php

namespace App\Modules\Admin\Actions;

use App\Models\User;
use App\Modules\Admin\DTOs\ChangeAdminPasswordData;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangeAdminPasswordAction
{
    public function __construct(
        private readonly VerifyAdminMfaChallengeAction $verifyAdminMfaChallengeAction,
    ) {}

    public function execute(User $admin, ChangeAdminPasswordData $data): void
    {
        $this->verifyAdminMfaChallengeAction->execute($admin, $data->mfaCode, null);

        if (!Hash::check($data->currentPassword, $admin->password)) {
            throw ValidationException::withMessages([
                'currentPassword' => __('admin.password.errors.invalid_current_password'),
            ]);
        }

        $admin->forceFill([
            'password' => $data->newPassword,
        ])->save();
    }
}
