<?php

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\DTOs\CheckoutPreferenceResult;

interface CheckoutPreferenceGateway
{
    public function create(CheckoutPreferenceData $data): CheckoutPreferenceResult;
}
