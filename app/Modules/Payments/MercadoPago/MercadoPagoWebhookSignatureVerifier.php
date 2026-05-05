<?php

namespace App\Modules\Payments\MercadoPago;

use App\Modules\Payments\DTOs\MercadoPagoWebhookSignatureVerificationResult;

class MercadoPagoWebhookSignatureVerifier
{
    public function verify(?string $xSignature, ?string $xRequestId, ?string $dataId): MercadoPagoWebhookSignatureVerificationResult
    {
        $parsedSignature = $this->parseSignatureHeader($xSignature);
        $timestamp = $parsedSignature['ts'] ?? null;
        $hash = $parsedSignature['v1'] ?? null;
        $manifest = $this->manifest($dataId, $xRequestId, $timestamp);

        if ($xSignature === null) {
            return new MercadoPagoWebhookSignatureVerificationResult(
                valid: false,
                timestamp: $timestamp,
                hash: $hash,
                manifest: $manifest,
                error: 'signature_header_missing',
            );
        }

        if ($timestamp === null || $hash === null) {
            return new MercadoPagoWebhookSignatureVerificationResult(
                valid: false,
                timestamp: $timestamp,
                hash: $hash,
                manifest: $manifest,
                error: 'signature_header_invalid',
            );
        }

        $secret = trim((string) config('services.mercado_pago.webhook_secret', ''));

        if ($secret === '') {
            return new MercadoPagoWebhookSignatureVerificationResult(
                valid: false,
                timestamp: $timestamp,
                hash: $hash,
                manifest: $manifest,
                error: 'webhook_secret_missing',
            );
        }

        $expectedHash = hash_hmac('sha256', $manifest, $secret);

        if (!hash_equals($expectedHash, $hash)) {
            return new MercadoPagoWebhookSignatureVerificationResult(
                valid: false,
                timestamp: $timestamp,
                hash: $hash,
                manifest: $manifest,
                error: 'signature_mismatch',
            );
        }

        if (!$this->timestampWithinTolerance($timestamp)) {
            return new MercadoPagoWebhookSignatureVerificationResult(
                valid: false,
                timestamp: $timestamp,
                hash: $hash,
                manifest: $manifest,
                error: 'signature_timestamp_out_of_tolerance',
            );
        }

        return new MercadoPagoWebhookSignatureVerificationResult(
            valid: true,
            timestamp: $timestamp,
            hash: $hash,
            manifest: $manifest,
        );
    }

    /**
     * @return array{ts?: string, v1?: string}
     */
    public function parseSignatureHeader(?string $xSignature): array
    {
        if ($xSignature === null || trim($xSignature) === '') {
            return [];
        }

        $parsed = [];

        foreach (explode(',', $xSignature) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);

            $key = trim((string) $key);
            $value = trim((string) $value);

            if ($key === 'ts' && $value !== '') {
                $parsed['ts'] = $value;
            }

            if ($key === 'v1' && $value !== '') {
                $parsed['v1'] = $value;
            }
        }

        return $parsed;
    }

    public function manifest(?string $dataId, ?string $xRequestId, ?string $timestamp): string
    {
        $manifest = '';

        if ($this->filled($dataId)) {
            $manifest .= "id:{$dataId};";
        }

        if ($this->filled($xRequestId)) {
            $manifest .= "request-id:{$xRequestId};";
        }

        if ($this->filled($timestamp)) {
            $manifest .= "ts:{$timestamp};";
        }

        return $manifest;
    }

    private function timestampWithinTolerance(string $timestamp): bool
    {
        $toleranceSeconds = (int) config('services.mercado_pago.webhook_signature_tolerance_seconds', 0);

        if ($toleranceSeconds <= 0) {
            return true;
        }

        if (!ctype_digit($timestamp)) {
            return false;
        }

        $timestampSeconds = strlen($timestamp) > 10
            ? (int) floor(((int) $timestamp) / 1000)
            : (int) $timestamp;

        return abs(time() - $timestampSeconds) <= $toleranceSeconds;
    }

    private function filled(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
