<?php

namespace App\Modules\Admin\Actions;

use App\Models\User;
use App\Modules\Admin\DTOs\AdminLoginData;
use App\Modules\Admin\Exceptions\InvalidAdminCredentials;
use Illuminate\Support\Facades\Hash;

class AuthenticateAdminAction
{
    public function execute(AdminLoginData $data): User
    {
        $query = User::query();

        if ($data->username !== null) {
            $query->where('username', $data->username);
        }

        if ($data->username === null && $data->email !== null) {
            $query->where('email', $data->email);
        }

        $user = $query->first();

        if (
            $user === null
            || !$user->isAdmin()
            || !Hash::check($data->password, $user->password)
        ) {
            throw new InvalidAdminCredentials(__('general.errors.invalid_admin_credentials'));
        }

        return $user;
    }
}
