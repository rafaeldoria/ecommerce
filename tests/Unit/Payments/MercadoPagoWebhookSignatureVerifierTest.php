<?php

namespace Tests\Unit\Payments;

use App\Modules\Payments\MercadoPago\MercadoPagoWebhookSignatureVerifier;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MercadoPagoWebhookSignatureVerifierTest extends TestCase
{
    private string $secret = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.mercado_pago.webhook_secret' => $this->secret,
            'services.mercado_pago.webhook_signature_tolerance_seconds' => 0,
        ]);
    }

    #[Test]
    public function it_validates_a_complete_signature_manifest(): void
    {
        $timestamp = '1742505638683';
        $requestId = 'bb56a2f1-6aae-46ac-982e-9dcd3581d08e';
        $dataId = '123456';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $hash = hash_hmac('sha256', $manifest, $this->secret);

        $result = app(MercadoPagoWebhookSignatureVerifier::class)->verify(
            xSignature: "ts={$timestamp},v1={$hash}",
            xRequestId: $requestId,
            dataId: $dataId,
        );

        $this->assertTrue($result->valid);
        $this->assertSame($timestamp, $result->timestamp);
        $this->assertSame($hash, $result->hash);
        $this->assertSame($manifest, $result->manifest);
    }

    #[Test]
    public function it_removes_the_data_id_manifest_part_when_data_id_is_absent(): void
    {
        $timestamp = '1742505638683';
        $requestId = 'bb56a2f1-6aae-46ac-982e-9dcd3581d08e';
        $manifest = "request-id:{$requestId};ts:{$timestamp};";
        $hash = hash_hmac('sha256', $manifest, $this->secret);

        $result = app(MercadoPagoWebhookSignatureVerifier::class)->verify(
            xSignature: "ts={$timestamp},v1={$hash}",
            xRequestId: $requestId,
            dataId: null,
        );

        $this->assertTrue($result->valid);
        $this->assertSame($manifest, $result->manifest);
    }

    #[Test]
    public function it_parses_signature_parts_with_extra_spacing_and_order_changes(): void
    {
        $timestamp = '1742505638683';
        $requestId = 'request-id-123';
        $dataId = '999999999';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $hash = hash_hmac('sha256', $manifest, $this->secret);

        $result = app(MercadoPagoWebhookSignatureVerifier::class)->verify(
            xSignature: " v1={$hash} , ignored=value, ts={$timestamp} ",
            xRequestId: $requestId,
            dataId: $dataId,
        );

        $this->assertTrue($result->valid);
        $this->assertSame($hash, $result->hash);
    }

    #[Test]
    public function it_applies_timestamp_tolerance_only_when_configured(): void
    {
        $timestamp = '1000';
        $requestId = 'request-id-123';
        $dataId = '123456';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $hash = hash_hmac('sha256', $manifest, $this->secret);

        $withoutTolerance = app(MercadoPagoWebhookSignatureVerifier::class)->verify(
            xSignature: "ts={$timestamp},v1={$hash}",
            xRequestId: $requestId,
            dataId: $dataId,
        );

        config(['services.mercado_pago.webhook_signature_tolerance_seconds' => 300]);

        $withTolerance = app(MercadoPagoWebhookSignatureVerifier::class)->verify(
            xSignature: "ts={$timestamp},v1={$hash}",
            xRequestId: $requestId,
            dataId: $dataId,
        );

        $this->assertTrue($withoutTolerance->valid);
        $this->assertFalse($withTolerance->valid);
        $this->assertSame('signature_timestamp_out_of_tolerance', $withTolerance->error);
    }

    #[Test]
    public function it_accepts_seconds_and_milliseconds_timestamps_inside_the_configured_tolerance(): void
    {
        config(['services.mercado_pago.webhook_signature_tolerance_seconds' => 300]);

        $requestId = 'request-id-123';
        $dataId = '123456';

        foreach ([(string) time(), (string) (time() * 1000)] as $timestamp) {
            $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
            $hash = hash_hmac('sha256', $manifest, $this->secret);

            $result = app(MercadoPagoWebhookSignatureVerifier::class)->verify(
                xSignature: "ts={$timestamp},v1={$hash}",
                xRequestId: $requestId,
                dataId: $dataId,
            );

            $this->assertTrue($result->valid);
        }
    }
}
