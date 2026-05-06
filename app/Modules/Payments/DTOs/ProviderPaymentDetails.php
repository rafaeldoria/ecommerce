<?php

namespace App\Modules\Payments\DTOs;

readonly class ProviderPaymentDetails
{
    public function __construct(
        public string $providerPaymentId,
        public ?string $externalReference,
        public ?string $status,
        public ?string $statusDetail,
        public ?int $amountCents,
        public ?string $currency,
        public array $rawProviderResponse,
    ) {}
}
