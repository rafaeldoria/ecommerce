<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Payments\Contracts\CheckoutPreferenceGateway;
use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\DTOs\CheckoutPreferenceResult;
use App\Modules\Payments\DTOs\CreateCheckoutPreferenceData;
use App\Modules\Payments\DTOs\CreatePendingCheckoutPaymentData;
use App\Modules\Payments\Enums\PaymentProvider;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Exceptions\PaymentConfigurationMissing;
use App\Modules\Payments\Models\Payment;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateCheckoutPreferenceAction
{
    private const PENDING_PAYMENT_SESSION_KEY = 'payments.mercado_pago.pending_payment_id';

    public function __construct(
        private readonly CreatePendingCheckoutPaymentAction $createPendingCheckoutPaymentAction,
        private readonly CheckoutPreferenceGateway $checkoutPreferenceGateway,
        private readonly GetCurrentCartAction $getCurrentCartAction,
        private readonly Session $session,
    ) {}

    public function execute(CreateCheckoutPreferenceData $data): CheckoutPreferenceResult
    {
        $cartItems = $this->getCurrentCartAction->execute();

        if ($cartItems === []) {
            $existingPayment = $this->findReusableSessionPendingPreference();

            if ($existingPayment !== null) {
                return $this->preferenceResultFromPayment($existingPayment);
            }
        }

        $checkoutIntentHash = $this->checkoutIntentHash($cartItems, $data);

        $existingPayment = $this->findReusablePendingPreference($checkoutIntentHash);

        if ($existingPayment !== null) {
            return $this->preferenceResultFromPayment($existingPayment);
        }

        $payment = $this->createPendingCheckoutPaymentAction->execute(new CreatePendingCheckoutPaymentData(
            email: $data->email,
            whatsapp: $data->whatsapp,
            checkoutIntentHash: $checkoutIntentHash,
        ));

        try {
            $preference = $this->checkoutPreferenceGateway->create(new CheckoutPreferenceData(
                email: $data->email,
                externalReference: $payment->external_reference,
                items: $this->mercadoPagoItems($payment->order),
                backUrls: [
                    'success' => route('storefront.mercado-pago.success'),
                    'failure' => route('storefront.mercado-pago.failure'),
                    'pending' => route('storefront.mercado-pago.pending'),
                ],
                notificationUrl: $this->notificationUrl(),
            ));

            $checkoutUrl = $this->checkoutUrlOrFail($preference);

            $payment->update([
                'provider_preference_id' => $preference->preferenceId,
                'checkout_url' => $checkoutUrl,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'checkout_intent_hash' => $checkoutIntentHash,
                    'checkout_preference_public_key' => $preference->publicKey,
                    'checkout_url_strategy' => (string) config('services.mercado_pago.checkout_url_strategy', 'init_point'),
                    'preference_created_at' => now()->toISOString(),
                ]),
                'raw_provider_snapshot' => $preference->rawProviderResponse === []
                    ? null
                    : $preference->rawProviderResponse,
            ]);

            $this->session->put(self::PENDING_PAYMENT_SESSION_KEY, $payment->getKey());

            return new CheckoutPreferenceResult(
                preferenceId: $preference->preferenceId,
                publicKey: $preference->publicKey,
                checkoutUrl: $checkoutUrl,
                rawProviderResponse: $preference->rawProviderResponse,
            );
        } catch (Throwable $exception) {
            $this->discardPendingCheckout($payment);

            throw $exception;
        }
    }

    private function checkoutUrlOrFail(CheckoutPreferenceResult $preference): string
    {
        $checkoutUrl = trim((string) $preference->checkoutUrl);

        if ($checkoutUrl === '') {
            throw new PaymentConfigurationMissing(__('general.errors.payment_configuration_invalid'));
        }

        return $checkoutUrl;
    }

    private function notificationUrl(): ?string
    {
        $notificationUrl = trim((string) config('services.mercado_pago.notification_url', ''));

        return $notificationUrl === '' ? null : $notificationUrl;
    }

    private function findReusablePendingPreference(string $checkoutIntentHash): ?Payment
    {
        /** @var Payment|null $payment */
        $payment = $this->reusablePendingPreferenceQuery()
            ->where('metadata->checkout_intent_hash', $checkoutIntentHash)
            ->latest('id')
            ->first();

        return $payment;
    }

    private function preferenceResultFromPayment(Payment $payment): CheckoutPreferenceResult
    {
        $metadata = $payment->metadata ?? [];
        $publicKey = (string) ($metadata['checkout_preference_public_key'] ?? config('services.mercado_pago.public_key', ''));

        return new CheckoutPreferenceResult(
            preferenceId: (string) $payment->provider_preference_id,
            publicKey: $publicKey,
            checkoutUrl: $payment->checkout_url,
            rawProviderResponse: $payment->raw_provider_snapshot ?? [],
        );
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price: int}>  $cartItems
     */
    private function checkoutIntentHash(array $cartItems, CreateCheckoutPreferenceData $data): string
    {
        $normalizedItems = collect($cartItems)
            ->map(fn (array $item): array => [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price' => (int) ($item['unit_price'] ?? 0),
            ])
            ->sortBy('product_id')
            ->values()
            ->all();

        return hash('sha256', json_encode([
            'email' => strtolower(trim($data->email)),
            'whatsapp' => preg_replace('/\D+/', '', $data->whatsapp) ?? '',
            'items' => $normalizedItems,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<int, array{id: string, title: string, quantity: int, unit_price: float, currency_id: string}>
     */
    private function mercadoPagoItems(Order $order): array
    {
        return $order->items
            ->map(fn (OrderItem $item): array => [
                'id' => (string) $item->product_id,
                'title' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => round($item->unit_price / 100, 2),
                'currency_id' => 'BRL',
            ])
            ->values()
            ->all();
    }

    private function discardPendingCheckout(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $order = Order::query()
                ->with('items')
                ->lockForUpdate()
                ->find($payment->order_id);

            if ($order === null) {
                $this->session->forget(self::PENDING_PAYMENT_SESSION_KEY);

                return;
            }

            foreach ($order->items as $item) {
                Product::withTrashed()
                    ->whereKey($item->product_id)
                    ->increment('quantity', $item->quantity);
            }

            $order->delete();
            $this->session->forget(self::PENDING_PAYMENT_SESSION_KEY);
        });
    }

    private function findReusableSessionPendingPreference(): ?Payment
    {
        if (!$this->pendingPreferenceReuseEnabled()) {
            return null;
        }

        $paymentId = $this->session->get(self::PENDING_PAYMENT_SESSION_KEY);

        if (!is_int($paymentId) && !(is_string($paymentId) && ctype_digit($paymentId))) {
            return null;
        }

        $payment = $this->reusablePendingPreferenceQuery()
            ->whereKey((int) $paymentId)
            ->first();

        if ($payment === null) {
            $this->session->forget(self::PENDING_PAYMENT_SESSION_KEY);
        }

        return $payment;
    }

    private function reusablePendingPreferenceQuery(): Builder
    {
        $reuseMinutes = (int) config('services.mercado_pago.pending_checkout_reuse_minutes', 30);

        if ($reuseMinutes <= 0) {
            return Payment::query()->whereRaw('1 = 0');
        }

        $query = Payment::query()
            ->with('order.items')
            ->where('provider', PaymentProvider::MercadoPago->value)
            ->where('status', PaymentStatus::Pending->value)
            ->whereNotNull('provider_preference_id')
            ->whereNotNull('checkout_url')
            ->whereHas('order', fn ($query) => $query->where('status', OrderStatus::PendingPayment->value));

        $query->where('created_at', '>=', now()->subMinutes($reuseMinutes));

        return $query;
    }

    private function pendingPreferenceReuseEnabled(): bool
    {
        return (int) config('services.mercado_pago.pending_checkout_reuse_minutes', 30) > 0;
    }
}
