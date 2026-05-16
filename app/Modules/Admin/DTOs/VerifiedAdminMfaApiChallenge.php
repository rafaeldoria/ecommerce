<?php

namespace App\Modules\Admin\DTOs;

use App\Models\User;

class VerifiedAdminMfaApiChallenge
{
    public function __construct(
        public readonly User $admin,
        public readonly string $deviceName,
    ) {}
}
