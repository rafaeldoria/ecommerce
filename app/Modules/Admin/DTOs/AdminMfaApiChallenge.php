<?php

namespace App\Modules\Admin\DTOs;

class AdminMfaApiChallenge
{
    public function __construct(
        public readonly string $id,
        public readonly \DateTimeInterface $expiresAt,
    ) {}
}
