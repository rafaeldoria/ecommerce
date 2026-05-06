<?php

namespace App\Modules\Payments\DTOs;

readonly class CheckoutPreferenceData
{
    /**
     * @param  array<int, array{id: string, title: string, quantity: int, unit_price: float, currency_id: string}>  $items
     * @param  array{success: string, failure: string, pending: string}  $backUrls
     */
    public function __construct(
        public string $email,
        public string $externalReference,
        public array $items,
        public array $backUrls,
        public ?string $notificationUrl = null,
    ) {}
}
