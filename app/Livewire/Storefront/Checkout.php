<?php

namespace App\Livewire\Storefront;

use App\Livewire\Concerns\UsesLocalizedPageTitle;
use App\Modules\Cart\Actions\GetCurrentCartAction;
use App\Modules\Cart\Exceptions\EmptyCart;
use App\Modules\Payments\Actions\StartPaymentCheckoutAction;
use App\Modules\Payments\DTOs\StartPaymentCheckoutData;
use App\Modules\Payments\Exceptions\InvalidCheckoutContact;
use App\Modules\Payments\Exceptions\PaymentConfigurationMissing;
use App\Support\MoneyFormatter;
use Illuminate\Contracts\Session\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.storefront')]
class Checkout extends Component
{
    use UsesLocalizedPageTitle;

    public string $email = '';

    public string $whatsapp = '';

    public function render(GetCurrentCartAction $getCurrentCartAction)
    {
        $items = $this->presentedItems($getCurrentCartAction->execute());

        return $this->pageView('livewire.storefront.checkout', [
            'items' => $items,
            'total' => MoneyFormatter::brlFromCents((int) collect($items)->sum('subtotal_cents')),
        ]);
    }

    public function startCheckout(StartPaymentCheckoutAction $startPaymentCheckoutAction, Session $session)
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'whatsapp' => ['required', 'string', 'min:10', 'max:20'],
        ]);

        try {
            $result = $startPaymentCheckoutAction->execute(new StartPaymentCheckoutData(
                email: $this->email,
                whatsapp: $this->whatsapp,
                existingPaymentId: $session->get('checkout.pending_payment_id'),
            ));
        } catch (EmptyCart $exception) {
            throw ValidationException::withMessages([
                'checkout' => $exception->getMessage(),
            ]);
        } catch (InvalidCheckoutContact $exception) {
            throw ValidationException::withMessages([
                'checkout' => $exception->getMessage(),
            ]);
        } catch (PaymentConfigurationMissing $exception) {
            throw ValidationException::withMessages([
                'checkout' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'checkout' => __('general.errors.payment_preference_failed'),
            ]);
        }

        $session->put('checkout.pending_payment_id', $result->payment->getKey());

        return redirect()->away((string) $result->preference->checkoutUrl);
    }

    protected function titleKey(): string
    {
        return 'storefront.metadata.checkout.title';
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price: int, product_name: string}>  $items
     * @return array<int, array{product_id: int, quantity: int, unit_price: int, product_name: string, formatted_unit_price: string, formatted_subtotal: string, subtotal_cents: int}>
     */
    private function presentedItems(array $items): array
    {
        return collect($items)
            ->map(function (array $item): array {
                $subtotal = (int) $item['unit_price'] * (int) $item['quantity'];

                return [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (int) $item['unit_price'],
                    'product_name' => (string) $item['product_name'],
                    'formatted_unit_price' => MoneyFormatter::brlFromCents((int) $item['unit_price']),
                    'formatted_subtotal' => MoneyFormatter::brlFromCents($subtotal),
                    'subtotal_cents' => $subtotal,
                ];
            })
            ->values()
            ->all();
    }
}
