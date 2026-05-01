<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\DTOs\MercadoPagoPaymentDetails;

interface PaymentDetailsGateway
{
    public function get(string $paymentId): MercadoPagoPaymentDetails;
}
