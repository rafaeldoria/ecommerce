<?php

namespace App\Modules\Admin\DTOs;

readonly class AdminLoginData
{
    public function __construct(
        public ?string $username,
        public ?string $email,
        public string $password,
        public string $deviceName,
    ) {}
}
