<?php

namespace App\Modules\Payments\DTOs;

readonly class StartPaymentCheckoutData
{
    public function __construct(
        public string $email,
        public string $whatsapp,
        public ?int $existingPaymentId = null,
    ) {}
}
