<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\DTOs\ProviderPaymentDetails;

interface PaymentDetailsGateway
{
    public function find(string $providerPaymentId): ProviderPaymentDetails;
}
