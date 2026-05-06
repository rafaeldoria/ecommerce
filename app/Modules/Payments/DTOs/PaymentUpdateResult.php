<?php

namespace App\Modules\Payments\DTOs;

readonly class PaymentUpdateResult
{
    public function __construct(
        public string $status,
        public ?int $paymentId = null,
        public ?int $orderId = null,
        public ?string $providerPaymentId = null,
    ) {}
}
