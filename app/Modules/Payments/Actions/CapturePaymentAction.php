<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\DTOs\CapturePaymentData;
use App\Modules\Payments\Exceptions\PaymentProcessingDeferred;

class CapturePaymentAction
{
    public function execute(CapturePaymentData $data): never
    {
        throw new PaymentProcessingDeferred(__('general.errors.payment_processing_deferred'));
    }
}
