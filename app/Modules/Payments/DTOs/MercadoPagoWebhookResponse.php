<?php

namespace App\Modules\Payments\DTOs;

readonly class MercadoPagoWebhookResponse
{
    public function __construct(
        public int $httpStatus,
        public string $status,
    ) {}
}
