<?php

namespace App\Modules\Payments\DTOs;

readonly class CreateCheckoutPreferenceData
{
    public function __construct(
        public string $email,
        public string $whatsapp,
    ) {}
}
