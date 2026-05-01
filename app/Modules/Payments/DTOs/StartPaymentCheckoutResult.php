<?php

namespace App\Modules\Payments\DTOs;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Models\Payment;

readonly class StartPaymentCheckoutResult
{
    public function __construct(
        public Order $order,
        public Payment $payment,
        public CheckoutPreferenceResult $preference,
    ) {}
}
