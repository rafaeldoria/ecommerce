<?php

namespace App\Modules\Payments\DTOs;

readonly class MercadoPagoPaymentDetails
{
    public function __construct(
        public string $paymentId,
        public string $externalReference,
        public ?string $status,
        public ?string $statusDetail,
        public int $amountCents,
        public array $metadata = [],
    ) {}
}
