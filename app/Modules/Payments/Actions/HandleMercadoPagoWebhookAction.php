<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\DTOs\MercadoPagoWebhookRequestData;
use App\Modules\Payments\DTOs\MercadoPagoWebhookResponse;
use App\Modules\Payments\MercadoPago\MercadoPagoWebhookSignatureVerifier;
use App\Modules\Payments\Models\MercadoPagoWebhookRequest;
use Illuminate\Support\Arr;

class HandleMercadoPagoWebhookAction
{
    public function __construct(
        private readonly MercadoPagoWebhookSignatureVerifier $signatureVerifier,
        private readonly ProcessMercadoPagoPaymentUpdateAction $processMercadoPagoPaymentUpdateAction,
    ) {}

    public function execute(MercadoPagoWebhookRequestData $data): MercadoPagoWebhookResponse
    {
        $eventType = $this->stringValue($data->payload['type'] ?? $data->query['type'] ?? $data->query['topic'] ?? null);
        $dataId = $this->journalDataId($data);
        $signatureDataId = $this->signatureDataId($data);
        $xRequestId = $data->header('x-request-id');
        $xSignature = $data->header('x-signature');

        $webhookRequest = MercadoPagoWebhookRequest::query()->create([
            'received_at' => now(),
            'processing_status' => 'received',
            'event_type' => $eventType,
            'event_action' => $this->stringValue($data->payload['action'] ?? null),
            'notification_id' => $this->stringValue($data->payload['id'] ?? null),
            'data_id' => $dataId,
            'live_mode' => $this->booleanValue($data->payload['live_mode'] ?? null),
            'user_id' => $this->stringValue($data->payload['user_id'] ?? null),
            'x_request_id' => $xRequestId,
            'x_signature' => $xSignature,
            'headers' => $data->headers,
            'query' => $data->query,
            'payload' => $data->payload,
        ]);

        if ($this->isLegacyFeedNotification($data)) {
            $webhookRequest->update([
                'processing_status' => 'ignored_ipn',
                'http_status_returned' => 200,
                'signature_valid' => null,
                'validation_error' => null,
                'processed_at' => now(),
                'error_message' => 'legacy_ipn_feed_notification',
            ]);

            return new MercadoPagoWebhookResponse(httpStatus: 200, status: 'ignored_ipn');
        }

        $verification = $this->signatureVerifier->verify($xSignature, $xRequestId, $signatureDataId);

        if (!$verification->valid) {
            $webhookRequest->update([
                'processing_status' => 'failed',
                'http_status_returned' => 401,
                'signature_ts' => $verification->timestamp,
                'signature_hash' => $verification->hash,
                'signature_manifest' => $verification->manifest,
                'signature_valid' => false,
                'validation_error' => $verification->error,
                'processed_at' => now(),
                'error_message' => $verification->error,
            ]);

            return new MercadoPagoWebhookResponse(httpStatus: 401, status: 'invalid_signature');
        }

        $verifiedUpdate = [
            'processing_status' => 'verified',
            'http_status_returned' => 200,
            'signature_ts' => $verification->timestamp,
            'signature_hash' => $verification->hash,
            'signature_manifest' => $verification->manifest,
            'signature_valid' => true,
            'validation_error' => null,
            'processed_at' => now(),
        ];

        if ($eventType !== 'payment') {
            $webhookRequest->update(array_merge($verifiedUpdate, [
                'processing_status' => 'ignored',
                'error_message' => 'unsupported_event_type',
            ]));

            return new MercadoPagoWebhookResponse(httpStatus: 200, status: 'ignored');
        }

        if ($dataId === null) {
            $webhookRequest->update(array_merge($verifiedUpdate, [
                'processing_status' => 'ignored',
                'error_message' => 'missing_provider_payment_id',
            ]));

            return new MercadoPagoWebhookResponse(httpStatus: 200, status: 'ignored');
        }

        try {
            $paymentUpdate = $this->processMercadoPagoPaymentUpdateAction->execute($dataId);
        } catch (\Throwable $exception) {
            report($exception);

            $webhookRequest->update(array_merge($verifiedUpdate, [
                'processing_status' => 'failed',
                'http_status_returned' => 500,
                'provider_payment_id' => $dataId,
                'error_message' => $exception->getMessage(),
            ]));

            return new MercadoPagoWebhookResponse(httpStatus: 500, status: 'payment_processing_failed');
        }

        $webhookRequest->update(array_merge($verifiedUpdate, [
            'processing_status' => $paymentUpdate->status === 'processed' ? 'processed' : 'failed',
            'related_payment_id' => $paymentUpdate->paymentId,
            'provider_payment_id' => $paymentUpdate->providerPaymentId ?? $dataId,
            'error_message' => $paymentUpdate->status === 'processed' ? null : $paymentUpdate->status,
        ]));

        return new MercadoPagoWebhookResponse(httpStatus: 200, status: $paymentUpdate->status);
    }

    private function signatureDataId(MercadoPagoWebhookRequestData $data): ?string
    {
        return $this->stringValue($data->query['data.id'] ?? $data->query['data_id'] ?? Arr::get($data->query, 'data.id'));
    }

    private function journalDataId(MercadoPagoWebhookRequestData $data): ?string
    {
        return $this->signatureDataId($data)
            ?? $this->stringValue(Arr::get($data->payload, 'data.id'))
            ?? $this->stringValue($data->query['id'] ?? null);
    }

    private function isLegacyFeedNotification(MercadoPagoWebhookRequestData $data): bool
    {
        return $this->stringValue($data->query['topic'] ?? null) !== null
            && $this->stringValue($data->query['id'] ?? null) !== null
            && $this->signatureDataId($data) === null;
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function booleanValue(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (!is_scalar($value)) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }
}
