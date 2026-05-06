<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Payments\Contracts\PaymentDetailsGateway;
use App\Modules\Payments\DTOs\PaymentUpdateResult;
use App\Modules\Payments\DTOs\ProviderPaymentDetails;
use App\Modules\Payments\Enums\PaymentProvider;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Payment;
use Illuminate\Support\Facades\DB;

class ProcessMercadoPagoPaymentUpdateAction
{
    public function __construct(
        private readonly PaymentDetailsGateway $paymentDetailsGateway,
    ) {}

    public function execute(string $providerPaymentId): PaymentUpdateResult
    {
        $details = $this->paymentDetailsGateway->find($providerPaymentId);

        if ($details->externalReference === null) {
            return new PaymentUpdateResult(
                status: 'missing_external_reference',
                providerPaymentId: $details->providerPaymentId,
            );
        }

        return DB::transaction(function () use ($details): PaymentUpdateResult {
            /** @var Payment|null $payment */
            $payment = Payment::query()
                ->where('provider', PaymentProvider::MercadoPago->value)
                ->where('external_reference', $details->externalReference)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return new PaymentUpdateResult(
                    status: 'local_payment_not_found',
                    providerPaymentId: $details->providerPaymentId,
                );
            }

            /** @var Order|null $order */
            $order = Order::query()
                ->with('items')
                ->lockForUpdate()
                ->find($payment->order_id);

            if ($order === null) {
                return new PaymentUpdateResult(
                    status: 'local_order_not_found',
                    paymentId: $payment->getKey(),
                    providerPaymentId: $details->providerPaymentId,
                );
            }

            $localPaymentStatus = $this->localPaymentStatus($details);
            $metadata = $this->metadata($payment, $details);

            if ($localPaymentStatus === PaymentStatus::Approved && !$this->providerPaymentMatchesLocalPayment($payment, $details)) {
                $localPaymentStatus = PaymentStatus::Unknown;
                $metadata['provider_payment_mismatch'] = true;
            } else {
                unset($metadata['provider_payment_mismatch']);
            }

            $orderStatus = $this->orderStatus($details, $localPaymentStatus, $order);

            if ($this->shouldRestoreStock($localPaymentStatus, $metadata)) {
                $this->restoreStock($order);

                $metadata['stock_restored_at'] = now()->toISOString();
                $metadata['stock_restored_provider_payment_id'] = $details->providerPaymentId;
            }

            $payment->update([
                'provider_payment_id' => $details->providerPaymentId,
                'status' => $localPaymentStatus->value,
                'provider_status' => $details->status,
                'provider_status_detail' => $details->statusDetail,
                'currency' => $details->currency ?? $payment->currency,
                'raw_provider_snapshot' => $details->rawProviderResponse,
                'metadata' => $metadata,
            ]);

            if ($order->status !== $orderStatus->value) {
                $order->update(['status' => $orderStatus->value]);
            }

            return new PaymentUpdateResult(
                status: 'processed',
                paymentId: $payment->getKey(),
                orderId: $order->getKey(),
                providerPaymentId: $details->providerPaymentId,
            );
        });
    }

    private function localPaymentStatus(ProviderPaymentDetails $details): PaymentStatus
    {
        return match ($this->normalizedStatus($details)) {
            'approved' => PaymentStatus::Approved,
            'rejected' => PaymentStatus::Rejected,
            'cancelled', 'canceled' => PaymentStatus::Cancelled,
            'refunded' => PaymentStatus::Refunded,
            'charged_back' => PaymentStatus::ChargedBack,
            'pending', 'in_process', 'in_mediation' => PaymentStatus::Pending,
            default => PaymentStatus::Unknown,
        };
    }

    private function orderStatus(
        ProviderPaymentDetails $details,
        PaymentStatus $paymentStatus,
        Order $order,
    ): OrderStatus {
        if ($paymentStatus === PaymentStatus::Approved && $this->normalizedStatusDetail($details) === 'accredited') {
            return OrderStatus::Paid;
        }

        if ($this->isFailedTerminalStatus($paymentStatus)) {
            return OrderStatus::PaymentFailed;
        }

        return $this->currentOrderStatus($order) ?? OrderStatus::PendingPayment;
    }

    private function currentOrderStatus(Order $order): ?OrderStatus
    {
        return OrderStatus::tryFrom((string) $order->status);
    }

    private function metadata(Payment $payment, ProviderPaymentDetails $details): array
    {
        $metadata = $payment->metadata ?? [];

        if ($details->amountCents !== null) {
            $metadata['provider_amount_cents'] = $details->amountCents;
        }

        return $metadata;
    }

    private function providerPaymentMatchesLocalPayment(Payment $payment, ProviderPaymentDetails $details): bool
    {
        if ($details->amountCents === null || $details->currency === null) {
            return false;
        }

        return $details->amountCents === $payment->amount_cents
            && strtoupper($details->currency) === strtoupper($payment->currency);
    }

    private function shouldRestoreStock(PaymentStatus $paymentStatus, array $metadata): bool
    {
        return $this->isFailedTerminalStatus($paymentStatus)
            && !array_key_exists('stock_restored_at', $metadata);
    }

    private function isFailedTerminalStatus(PaymentStatus $paymentStatus): bool
    {
        return in_array($paymentStatus, [
            PaymentStatus::Rejected,
            PaymentStatus::Cancelled,
            PaymentStatus::Refunded,
            PaymentStatus::ChargedBack,
        ], true);
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            /** @var OrderItem $item */
            Product::withTrashed()
                ->whereKey($item->product_id)
                ->increment('quantity', $item->quantity);
        }
    }

    private function normalizedStatus(ProviderPaymentDetails $details): string
    {
        return strtolower(trim((string) $details->status));
    }

    private function normalizedStatusDetail(ProviderPaymentDetails $details): string
    {
        return strtolower(trim((string) $details->statusDetail));
    }
}
