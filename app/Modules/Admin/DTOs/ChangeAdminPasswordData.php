<?php

namespace App\Modules\Admin\DTOs;

readonly class ChangeAdminPasswordData
{
    public function __construct(
        public string $currentPassword,
        public string $newPassword,
        public string $mfaCode,
    ) {}
}
