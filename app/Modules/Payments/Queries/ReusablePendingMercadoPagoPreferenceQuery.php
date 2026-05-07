<?php

namespace App\Modules\Payments\Queries;

use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Payments\Enums\PaymentProvider;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Builder;

class ReusablePendingMercadoPagoPreferenceQuery
{
    public function findByCheckoutIntentHash(string $checkoutIntentHash): ?Payment
    {
        $query = $this->baseQuery();

        if ($query === null) {
            return null;
        }

        /** @var Payment|null $payment */
        $payment = $query
            ->where('metadata->checkout_intent_hash', $checkoutIntentHash)
            ->latest('id')
            ->first();

        return $payment;
    }

    public function findByPaymentId(int $paymentId): ?Payment
    {
        $query = $this->baseQuery();

        if ($query === null) {
            return null;
        }

        /** @var Payment|null $payment */
        $payment = $query
            ->whereKey($paymentId)
            ->first();

        return $payment;
    }

    public function isEnabled(): bool
    {
        return $this->reuseMinutes() > 0;
    }

    private function baseQuery(): ?Builder
    {
        $reuseMinutes = $this->reuseMinutes();

        if ($reuseMinutes <= 0) {
            return null;
        }

        return Payment::query()
            ->with('order.items')
            ->where('provider', PaymentProvider::MercadoPago->value)
            ->where('status', PaymentStatus::Pending->value)
            ->whereNotNull('provider_preference_id')
            ->whereNotNull('checkout_url')
            ->whereHas('order', fn ($query) => $query->where('status', OrderStatus::PendingPayment->value))
            ->where('created_at', '>=', now()->subMinutes($reuseMinutes));
    }

    private function reuseMinutes(): int
    {
        return (int) config('services.mercado_pago.pending_checkout_reuse_minutes', 30);
    }
}
