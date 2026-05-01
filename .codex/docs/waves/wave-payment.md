# Payment Checkout Pro Test Summary

## Wave Goal

This wave adds a first real Mercado Pago Checkout Pro test path in sandbox without changing the MVP order lifecycle.

It delivers:

- Mercado Pago SDK installation and Laravel configuration keys
- a `Payments` module gateway for creating Checkout Pro preferences
- a storefront checkout form that collects email and WhatsApp
- Wallet Brick rendering after the preference is created
- simple public return pages for success, failure, and pending states
- tests proving that this test flow does not create orders, clear the cart, or decrement stock

## Short Flow

```mermaid
flowchart TD
    A[Buyer cart] --> B[/checkout Livewire component]
    B --> C[Validate email and WhatsApp]
    C --> D[CreateCheckoutPreferenceAction]
    D --> E[Session cart items]
    D --> F[MercadoPagoCheckoutPreferenceGateway]
    F --> G[PreferenceClient create]
    G --> H[Preference ID + Public Key]
    H --> I[Wallet Brick button]
    I --> J[Mercado Pago sandbox checkout]
    J --> K[Return URL: success, failure, or pending]

    D -. does not call .-> L[CreateOrderAction]
    D -. does not update .-> M[Product stock]
```

## Main Call Direction Between Modules

### Storefront Checkout

- `App\Livewire\Storefront\Checkout` is the public entrypoint for the test flow.
- It renders the cart summary, validates buyer contact fields, and asks the Payments module to create a preference.
- After receiving the preference result, it dispatches a browser event so the Mercado Pago Wallet Brick can render with the returned `preferenceId` and configured public key.

### Payments

- `CreateCheckoutPreferenceAction` reads the current session cart and maps cart items into Mercado Pago item payloads.
- `MercadoPagoCheckoutPreferenceGateway` owns SDK authentication, runtime environment selection, and the call to `PreferenceClient::create`.
- `MercadoPagoPreferenceRequestFactory` builds the request body with items, payer email, return URLs, statement descriptor, `external_reference`, and `auto_return=approved`.

### Orders And Catalog

- `CreateOrderAction` is intentionally not called by this test flow.
- Product stock is intentionally not decremented.
- The session cart remains intact after the preference is created.

## Central Idea Of Each Module

### Payments

Central idea:
own Mercado Pago integration details behind an explicit gateway so the rest of the app does not depend directly on SDK classes.

What it does now:

- creates Checkout Pro preferences for sandbox testing
- converts internal cents-based prices into BRL decimal values for Mercado Pago
- chooses `sandbox_init_point` when `MERCADO_PAGO_ENV=sandbox`
- fails clearly when Mercado Pago credentials are missing

### Storefront

Central idea:
provide a minimal, honest checkout test surface while the business still uses manual fulfillment.

What it does now:

- collects email and WhatsApp
- shows the current cart total and item lines
- renders the official Mercado Pago Wallet Brick after preference creation
- shows simple return pages with Mercado Pago query context

### Orders

Central idea:
keep the real order creation flow separate until payment confirmation is designed.

What it does now:

- remains responsible for the existing manual fulfillment order flow
- is not automatically triggered by Mercado Pago return URLs
- is protected from accidental stock changes during this sandbox test

## What This Wave Does Not Cover Yet

This wave still does not include:

- webhook handling
- payment signature validation
- payment status persistence
- automatic order creation after approved payment
- automatic stock decrement after payment confirmation
- refund, cancellation, or dispute handling
- production rollout settings beyond the environment keys

## Practical Reading Of The Design

If you want the shortest interpretation:

1. the buyer can start a real Mercado Pago Checkout Pro sandbox session from `/checkout`
2. the app creates a real Mercado Pago preference from the session cart
3. the current MVP order and stock behavior remains unchanged until webhooks/payment confirmation are implemented
