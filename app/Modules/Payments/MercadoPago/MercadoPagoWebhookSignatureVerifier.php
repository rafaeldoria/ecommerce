<?php

namespace App\Modules\Payments\MercadoPago;

use Illuminate\Http\Request;

class MercadoPagoWebhookSignatureVerifier
{
    public function isValid(Request $request): bool
    {
        $secret = (string) config('services.mercado_pago.webhook_secret', '');

        if ($secret === '') {
            return false;
        }

        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id');
        $dataId = $this->paymentId($request);

        if (!is_string($xSignature) || !is_string($xRequestId) || !is_string($dataId)) {
            return false;
        }

        $signature = $this->parseSignature($xSignature);

        if ($signature['ts'] === null || $signature['v1'] === null) {
            return false;
        }

        if (!$this->timestampIsFresh($signature['ts'])) {
            return false;
        }

        $manifest = sprintf('id:%s;request-id:%s;ts:%s;', $dataId, $xRequestId, $signature['ts']);
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $signature['v1']);
    }

    private function paymentId(Request $request): ?string
    {
        $dataId = $request->query->get('data.id')
            ?? $request->query('data_id')
            ?? data_get($request->query('data'), 'id');

        return is_string($dataId) && $dataId !== '' ? $dataId : null;
    }

    /**
     * @return array{ts: ?string, v1: ?string}
     */
    private function parseSignature(string $xSignature): array
    {
        $parsed = ['ts' => null, 'v1' => null];

        foreach (explode(',', $xSignature) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, null);

            if ($key === null || $value === null) {
                continue;
            }

            $key = trim($key);
            $value = trim($value);

            if ($key === 'ts' || $key === 'v1') {
                $parsed[$key] = $value;
            }
        }

        return $parsed;
    }

    private function timestampIsFresh(string $timestamp): bool
    {
        $tolerance = (int) config('services.mercado_pago.webhook_tolerance_seconds', 300);

        if ($tolerance <= 0) {
            return true;
        }

        if (!ctype_digit($timestamp)) {
            return false;
        }

        $receivedAt = (int) $timestamp;

        if ($receivedAt > 9999999999) {
            $receivedAt = (int) floor($receivedAt / 1000);
        }

        return abs(time() - $receivedAt) <= $tolerance;
    }
}
