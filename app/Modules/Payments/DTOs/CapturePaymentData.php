<?php

namespace App\Modules\Payments\DTOs;

readonly class CapturePaymentData
{
    public function __construct(
        public int $orderId,
        public string $method,
    ) {}
}
