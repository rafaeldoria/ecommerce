<?php

namespace App\Modules\Orders\DTOs;

readonly class CreateOrderData
{
    public function __construct(
        public string $email,
        public string $whatsapp,
    ) {}
}
