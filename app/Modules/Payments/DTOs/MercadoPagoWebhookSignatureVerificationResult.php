<?php

namespace App\Modules\Payments\DTOs;

readonly class MercadoPagoWebhookSignatureVerificationResult
{
    public function __construct(
        public bool $valid,
        public ?string $timestamp = null,
        public ?string $hash = null,
        public ?string $manifest = null,
        public ?string $error = null,
    ) {}
}
