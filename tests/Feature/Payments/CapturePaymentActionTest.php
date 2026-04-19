<?php

namespace Tests\Feature\Payments;

use App\Modules\Payments\Actions\CapturePaymentAction;
use App\Modules\Payments\DTOs\CapturePaymentData;
use App\Modules\Payments\Exceptions\PaymentProcessingDeferred;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CapturePaymentActionTest extends TestCase
{
    #[Test]
    public function it_keeps_payment_processing_explicitly_deferred(): void
    {
        $this->expectException(PaymentProcessingDeferred::class);
        $this->expectExceptionMessage(__('general.errors.payment_processing_deferred'));

        app(CapturePaymentAction::class)->execute(new CapturePaymentData(
            orderId: 10,
            method: 'pix',
        ));
    }
}
