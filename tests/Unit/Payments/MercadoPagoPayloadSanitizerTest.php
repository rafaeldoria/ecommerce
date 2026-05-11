<?php

namespace Tests\Unit\Payments;

use App\Modules\Payments\MercadoPago\MercadoPagoPayloadSanitizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MercadoPagoPayloadSanitizerTest extends TestCase
{
    #[Test]
    public function it_keeps_only_operational_webhook_fields(): void
    {
        $sanitized = app(MercadoPagoPayloadSanitizer::class)->webhookPayload([
            'type' => 'payment',
            'data' => ['id' => '123456'],
            'payer' => ['email' => 'buyer@example.com'],
            'card' => ['last_four_digits' => '1234'],
        ]);

        $this->assertSame([
            'data' => ['id' => '123456'],
            'type' => 'payment',
        ], $sanitized);
    }
}
