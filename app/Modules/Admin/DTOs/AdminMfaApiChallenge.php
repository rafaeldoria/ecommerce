<?php

namespace App\Modules\Admin\DTOs;

use DateTimeInterface;

class AdminMfaApiChallenge
{
    public function __construct(
        public readonly string $id,
        public readonly DateTimeInterface $expiresAt,
    ) {}
}
