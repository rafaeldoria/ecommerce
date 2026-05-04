<?php

namespace App\Modules\Payments\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Payments\Contracts\CheckoutPreferenceGateway;
use App\Modules\Payments\DTOs\CheckoutPreferenceData;
use App\Modules\Payments\DTOs\CheckoutPreferenceResult;
use App\Modules\Payments\DTOs\CreateCheckoutPreferenceData;
use App\Modules\Payments\DTOs\CreatePendingCheckoutPaymentData;
use App\Modules\Payments\Models\Payment;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateCheckoutPreferenceAction
{
    public function __construct(
        private readonly CreatePendingCheckoutPaymentAction $createPendingCheckoutPaymentAction,
        private readonly CheckoutPreferenceGateway $checkoutPreferenceGateway,
    ) {}

    public function execute(CreateCheckoutPreferenceData $data): CheckoutPreferenceResult
    {
        $payment = $this->createPendingCheckoutPaymentAction->execute(new CreatePendingCheckoutPaymentData(
            email: $data->email,
            whatsapp: $data->whatsapp,
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
            ));

            $payment->update([
                'provider_preference_id' => $preference->preferenceId,
                'checkout_url' => $preference->checkoutUrl,
                'raw_provider_snapshot' => $preference->rawProviderResponse === []
                    ? null
                    : $preference->rawProviderResponse,
            ]);

            return $preference;
        } catch (Throwable $exception) {
            $this->discardPendingCheckout($payment);

            throw $exception;
        }
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
                return;
            }

            foreach ($order->items as $item) {
                Product::withTrashed()
                    ->whereKey($item->product_id)
                    ->increment('quantity', $item->quantity);
            }

            $order->delete();
        });
    }
}
