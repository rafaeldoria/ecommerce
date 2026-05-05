# Mercado Pago Payment Assessment Update

## Current Project State

The current payment implementation is still an initial Checkout Pro test path, not a complete payment module.

Implemented now:

- `/checkout` collects email and WhatsApp.
- The app creates a Mercado Pago preference from the current session cart.
- The Checkout Pro Wallet Brick is rendered after preference creation.
- Return pages display Mercado Pago query parameters.
- Mercado Pago SDK credentials are configured in `config/services.php`.

Not implemented yet:

- local payment persistence;
- local order creation during payment checkout;
- durable `external_reference`;
- webhook route;
- webhook request journal;
- Mercado Pago signature validation;
- payment fetch from Mercado Pago after webhook;
- idempotent local payment/order update;
- admin payment visibility.

## Main Documentation Finding

Mercado Pago Webhooks for Checkout Pro should use the `payment` event/topic. The webhook must be validated through `x-signature`, `x-request-id`, query `data.id` when present, and the webhook secret.

Important: Mercado Pago's Webhooks documentation says test payments created with test credentials do not send real notifications. For local/test mode, webhook receiving must be tested through the "Simular" action in "Suas integracoes". A test purchase still validates the hosted checkout, but webhook processing should be validated separately.

## Recommended Direction

Keep the implementation simple:

- use test credentials locally;
- use ngrok HTTPS for the webhook endpoint;
- enable only the `Pagamentos` event;
- avoid trusting browser return URLs;
- create local order/payment before redirect;
- validate every webhook signature;
- fetch full payment details from Mercado Pago before updating local state;
- keep Payments decoupled through Actions, DTOs, contracts, and gateways.

## Updated Planning Output

The canonical plan is now `.codex/docs/payment/tasks-payment.md`.

The plan is split into eight waves:

- Wave 00: reassessment and documentation.
- Wave 01: Mercado Pago environment proof.
- Wave 02: payment persistence and local order state.
- Wave 03: Checkout Pro preference from local payment.
- Wave 04: webhook route, journal, and signature validation.
- Wave 05: payment fetch gateway and idempotent processor.
- Wave 06: return pages and admin visibility.
- Wave 07: final validation and operational docs.

Each completed wave must create `.codex/docs/pt-BR/wave-payment-##.md` with what changed and what the user should test.
