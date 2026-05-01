<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Catalog\Models\Product;
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

            $metadata = $this->mergePaymentMetadata($payment->metadata ?? [], $details->metadata);

            $payment->forceFill([
                'mercado_pago_payment_id' => $details->paymentId,
                'status' => $details->status,
                'status_detail' => $details->statusDetail,
                'amount_cents' => $details->amountCents > 0 ? $details->amountCents : $payment->amount_cents,
                'metadata' => $metadata,
            ])->save();

            $order = $payment->order()->lockForUpdate()->firstOrFail();
            $nextStatus = $this->mapOrderStatus($details);

            if ($order->status === OrderStatus::Completed && $nextStatus !== OrderStatus::Completed) {
                return $payment->load('order');
            }

            if ($nextStatus === OrderStatus::Error) {
                $this->restoreStockIfNeeded($payment);
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

    private function restoreStockIfNeeded(Payment $payment): void
    {
        $payment->loadMissing('order.items');
        $metadata = $payment->metadata ?? [];

        if (($metadata['stock_restored'] ?? false) === true) {
            return;
        }

        foreach ($payment->order->items as $item) {
            Product::query()
                ->whereKey($item->product_id)
                ->increment('quantity', (int) $item->quantity);
        }

        $metadata['stock_restored'] = true;
        $metadata['stock_restored_reason'] = 'payment_error';

        $payment->forceFill(['metadata' => $metadata])->save();
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergePaymentMetadata(array $current, array $incoming): array
    {
        return array_merge($incoming, array_intersect_key($current, array_flip([
            'stock_restored',
            'stock_restored_reason',
        ])));
    }
}
