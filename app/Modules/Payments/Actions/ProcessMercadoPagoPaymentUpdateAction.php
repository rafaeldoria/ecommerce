<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Payments\DTOs\MercadoPagoPaymentDetails;
use App\Modules\Payments\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessMercadoPagoPaymentUpdateAction
{
    public function execute(MercadoPagoPaymentDetails $details): Payment
    {
        return DB::transaction(function () use ($details): Payment {
            $payment = Payment::query()
                ->where('external_reference', $details->externalReference)
                ->lockForUpdate()
                ->firstOrFail();

            $payment->forceFill([
                'mercado_pago_payment_id' => $details->paymentId,
                'status' => $details->status,
                'status_detail' => $details->statusDetail,
                'amount_cents' => $details->amountCents > 0 ? $details->amountCents : $payment->amount_cents,
                'metadata' => $details->metadata,
            ])->save();

            $order = $payment->order()->lockForUpdate()->firstOrFail();
            $nextStatus = $this->mapOrderStatus($details);

            if ($order->status === OrderStatus::Completed && $nextStatus !== OrderStatus::Completed) {
                return $payment->load('order');
            }

            if ($nextStatus !== null && $order->status !== $nextStatus) {
                $order->forceFill(['status' => $nextStatus])->save();
            }

            if ($nextStatus === null) {
                Log::warning('Unknown Mercado Pago payment status received.', [
                    'payment_id' => $details->paymentId,
                    'external_reference' => $details->externalReference,
                    'status' => $details->status,
                    'status_detail' => $details->statusDetail,
                ]);
            }

            return $payment->load('order');
        });
    }

    private function mapOrderStatus(MercadoPagoPaymentDetails $details): ?OrderStatus
    {
        return match ($details->status) {
            'approved' => $details->statusDetail === 'accredited' ? OrderStatus::Completed : OrderStatus::Pending,
            'pending', 'in_process', 'in_mediation' => OrderStatus::Pending,
            'rejected', 'cancelled', 'canceled', 'refunded', 'charged_back' => OrderStatus::Error,
            default => null,
        };
    }
}
