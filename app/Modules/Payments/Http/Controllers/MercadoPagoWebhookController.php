<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Modules\Payments\Actions\ProcessMercadoPagoPaymentUpdateAction;
use App\Modules\Payments\Contracts\PaymentDetailsGateway;
use App\Modules\Payments\MercadoPago\MercadoPagoWebhookSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Throwable;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MercadoPagoWebhookSignatureVerifier $signatureVerifier,
        PaymentDetailsGateway $paymentDetailsGateway,
        ProcessMercadoPagoPaymentUpdateAction $processPaymentUpdateAction,
    ): JsonResponse {
        if (!$signatureVerifier->isValid($request)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $paymentId = $request->query->get('data.id')
            ?? $request->query('data_id')
            ?? data_get($request->query('data'), 'id');

        if (!is_string($paymentId) || $paymentId === '') {
            return response()->json(['message' => 'Payment id missing.'], 422);
        }

        try {
            $details = $paymentDetailsGateway->get($paymentId);
            $processPaymentUpdateAction->execute($details);
        } catch (Throwable $exception) {
            report($exception);

            Log::warning('Mercado Pago webhook could not be processed.', [
                'payment_id' => $paymentId,
            ]);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }

        return response()->json(['message' => 'Webhook processed.']);
    }
}
