<?php

namespace App\Modules\Payments\DTOs;

readonly class CreatePendingCheckoutPaymentData
{
    public function __construct(
        public string $email,
        public string $whatsapp,
        public ?string $checkoutIntentHash = null,
    ) {}
}
