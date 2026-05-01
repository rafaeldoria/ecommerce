<?php

namespace App\Modules\Payments\DTOs;

readonly class CheckoutPreferenceResult
{
    public function __construct(
        public string $preferenceId,
        public string $publicKey,
        public ?string $checkoutUrl,
    ) {}
}
