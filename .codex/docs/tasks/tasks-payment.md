# Tasks - Mercado Pago Payment Completion Plan

## Purpose

This file replaces the previous payment task plan that marked the payment flow as complete.

The current repository still has only an initial Checkout Pro test path:

- the buyer fills email and WhatsApp on `/checkout`;
- the app creates a Mercado Pago preference from the session cart;
- the UI renders the Wallet Brick;
- return pages display Mercado Pago query parameters;
- no local order is created by the payment flow;
- no payment table exists;
- no webhook route, request journal, signature verifier, payment fetch gateway, or idempotent status processor exists in the current code;
- the current `external_reference` is a temporary `cart-test-*` value and is not tied to a durable local order/payment.

The new plan must turn this into a simple, working, documented Checkout Pro payment flow without overengineering the MVP.

## Mercado Pago Documentation Findings

Documentation checked on 2026-05-03:

- Checkout Pro overview: https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/overview
- Payment notifications for Checkout Pro: https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/payment-notifications
- Webhooks: https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/additional-content/notifications/webhooks
- Test accounts: https://www.mercadopago.com.br/developers/pt/docs/your-integrations/test/accounts
- Integration test: https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/integration-test
- Test purchases: https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/integration-test/test-purchases
- Create preference API reference: https://www.mercadopago.com.br/developers/pt/reference/online-payments/checkout-pro/preferences/create-preference/post
- Get payment API reference: https://www.mercadopago.com.br/developers/en/reference/online-payments/checkout-pro/get-payment/get

Key decisions from the docs:

- Checkout Pro is a hosted redirect/Wallet Brick flow. The customer pays in Mercado Pago and returns to the store.
- Payment notifications for Checkout Pro use the `payment` topic/event.
- Webhooks are preferred over IPN because they support origin validation through the secret signature.
- The webhook signature uses `x-signature`, `x-request-id`, `data.id` from query params when present, and the webhook secret.
- The signature manifest is `id:[data.id_url];request-id:[x-request-id_header];ts:[ts_header];`.
- If a manifest value is absent in the received notification, that value must be removed from the manifest.
- After receiving a valid webhook, the app must fetch the full payment resource from `GET https://api.mercadopago.com/v1/payments/{id}` before updating local state.
- Mercado Pago expects `200 OK` or `201 CREATED` within 22 seconds; otherwise it retries.
- The general Webhooks page states that test payments created with test credentials do not send real notifications. The documented way to test receiving notifications in test mode is the "Simular" action in "Suas integracoes".
- Test purchases still use test credentials, test buyer accounts, and test cards. This validates the checkout flow, but the webhook must be validated separately with the simulator or with production credentials later.

## Environment Decision

Do not keep the old ambiguous `local/prod/sandbox` mental model.

Use these concepts instead:

- Laravel runtime: `local`, `testing`, `production`.
- Mercado Pago credential mode: `test` or `production`.
- Checkout URL strategy: `init_point` by default, `sandbox_init_point` only if a dedicated validation proves it does not create the sandbox loop reported by the user.

Initial recommendation:

- Use Mercado Pago test credentials in local development.
- Use a public HTTPS tunnel for local callbacks, currently:
  `https://gains-bootlace-slacking.ngrok-free.dev/webhooks/mercado-pago`
- Configure "Modo de teste" Webhook URL in Mercado Pago with only the `Pagamentos` event enabled.
- Store the test webhook secret in `.env` as `MERCADO_PAGO_WEBHOOK_SECRET`.
- Do not commit real credentials or the real secret.
- Treat test-purchase webhooks as unreliable/not expected based on the Webhooks documentation. Use the Mercado Pago simulator to test webhook delivery and signature validation.

The first implementation wave must explicitly validate whether this account/application should use `init_point`, `sandbox_init_point`, or the Wallet Brick only. Until that proof is documented, avoid hard-coding sandbox behavior.

## Architecture Rules

- Keep Payments isolated behind Actions, DTOs, contracts, and Mercado Pago gateway classes.
- Controllers only receive HTTP and call Actions.
- Livewire only coordinates UI state.
- Orders own buyer contact and manual fulfillment status.
- Payments own gateway status, provider identifiers, webhook processing, and payment audit data.
- Browser return URLs are never proof of payment.
- Webhook processing must be idempotent.
- Money stays as integer cents locally.
- Admin fulfillment stays manual after verified payment completion.
- Each completed wave must create a pt-BR document at `.codex/docs/pt-BR/wave-payment-##.md` with:
  - what was implemented;
  - what the user must test in the browser/admin/Mercado Pago panel;
  - expected results;
  - known limitations.

## Target Flow

1. Buyer reviews the cart and fills only email and WhatsApp.
2. The app creates a local pending order and a local payment record in one transactional flow.
3. The app creates a Checkout Pro preference with `external_reference` pointing to the local payment/order reference.
4. Buyer is redirected/rendered into Mercado Pago Checkout Pro.
5. Mercado Pago returns the buyer to the store, but the page only says the payment is being verified.
6. Mercado Pago webhook reaches `/webhooks/mercado-pago`.
7. The app stores a request journal row for diagnostics.
8. The app validates the signature.
9. If valid and topic is `payment`, the app fetches the full payment from Mercado Pago.
10. The app updates the local payment and order idempotently.
11. Admin fulfills manually only after local order status is paid/completed.

## Local Status Mapping

Recommended local order statuses:

- `pending_payment`: order exists and stock is reserved/decremented, waiting for payment.
- `payment_failed`: payment reached a failed terminal state and stock was restored once.
- `paid`: Mercado Pago payment was verified as approved/accredited.
- Existing `pending_fulfillment` can remain only if the project chooses to separate payment status from fulfillment status; if so, do not overload it as "paid".

Recommended local payment statuses:

- `pending`
- `approved`
- `rejected`
- `cancelled`
- `refunded`
- `charged_back`
- `unknown`

Mercado Pago mapping:

- `approved` with `status_detail=accredited` -> local payment `approved`, order `paid`
- `pending`, `in_process`, `in_mediation` -> local payment `pending`, order remains `pending_payment`
- `rejected`, `cancelled`, `canceled`, `refunded`, `charged_back` -> local failure status, order `payment_failed`
- unknown status -> save exact provider status, keep order pending, log/record the unknown value

## Webhook Request Journal

Create a simple table that records one row per received HTTP request. It must be useful for debugging without becoming the source of truth for payment state.

Suggested table: `mercado_pago_webhook_requests`

Suggested columns:

- `id`
- `received_at`
- `processing_status`: `received`, `verified`, `ignored`, `processed`, `failed`
- `http_status_returned`
- `event_type`
- `event_action`
- `notification_id`
- `data_id`
- `live_mode`
- `user_id`
- `x_request_id`
- `x_signature`
- `signature_ts`
- `signature_hash`
- `signature_valid`
- `validation_error`
- `headers` JSON, limited/sanitized
- `query` JSON
- `payload` JSON
- `related_payment_id` nullable FK to local payments
- `provider_payment_id`
- `processed_at`
- `error_message`
- timestamps

Rules:

- Save the received request before processing so 401/invalid cases can be diagnosed.
- Do not store application secrets.
- Store the incoming signature header because it is diagnostic evidence, not the secret itself.
- Return `401` for missing/invalid signatures.
- Return `200` for valid but unsupported/ignored topics after recording the reason.
- Return `200` for legacy IPN/Feed notifications that arrive with `topic` and `id`, recording them as ignored instead of validating them as Webhooks.
- Only update local payments/orders after signature validation and provider payment fetch.
- Use the payment processor, not the request journal, as the source of truth.

## Delivery Waves

### Wave 00 - Payment Reassessment And Documentation

Goal: document the real current state and reset the plan.

Tasks:

- Confirm current code creates only a Mercado Pago preference.
- Confirm no webhook/payment persistence exists.
- Confirm Mercado Pago docs for Checkout Pro, Webhooks, test accounts, and get-payment API.
- Update `tasks-payment.md`.
- Create `task-payment-upt.md` in English and pt-BR.

Acceptance:

- The old "everything done" task state is removed.
- The plan clearly separates test checkout validation from webhook validation.

User test doc:

- `.codex/docs/pt-BR/wave-payment-00.md` must be created when this wave is formally closed.

### Wave 01 - Mercado Pago Environment Proof

Goal: remove the sandbox/test/prod confusion before implementation.

Tasks:

- Replace `MERCADO_PAGO_ENV=sandbox` with clearer config names, for example `MERCADO_PAGO_MODE=test|production`.
- Add an explicit checkout URL strategy config if needed: `MERCADO_PAGO_CHECKOUT_URL_STRATEGY=init_point|sandbox_init_point`.
- Validate with current test credentials whether `init_point`, `sandbox_init_point`, or Wallet Brick should be used.
- Document the result in the wave doc.
- Keep `.env.example` updated without secrets.

Acceptance:

- The app has one documented local test setup.
- The sandbox loop is either reproduced and avoided, or the safe sandbox usage is proven.
- No production credential is needed for local test checkout.

User must test:

- Start checkout from `/checkout`.
- Confirm whether the Mercado Pago hosted page opens without looping.
- Use an incognito window and the test buyer account.

### Wave 02 - Payment Persistence And Local Order State

Goal: create durable local state before redirecting to Mercado Pago.

Tasks:

- Add order status enum or equivalent explicit constants.
- Add a `payments` table/model owned by `App\Modules\Payments`.
- Store order id, provider, provider preference id, provider payment id, external reference, checkout URL, amount cents, currency, provider status/detail, raw provider snapshot, metadata.
- Create a unique local external reference, for example `order-{order_id}-payment-{payment_id}` or a non-guessable UUID stored on the payment.
- Keep buyer contact fields unchanged: email and WhatsApp only.

Acceptance:

- A pending checkout creates one local order and one local payment.
- The payment can be found by `external_reference`.
- Stock movement remains explicit and test-covered.

User must test:

- Add product to cart.
- Submit checkout.
- Confirm an order/payment exists before leaving for Mercado Pago.

### Wave 03 - Checkout Pro Preference From Local Payment

Goal: create the Mercado Pago preference from the local payment instead of a temporary cart reference.

Tasks:

- Change checkout action to create order/payment first.
- Create preference with:
  - items from order items;
  - payer email;
  - `external_reference` from the local payment;
  - `back_urls`;
  - optional `notification_url` only if the wave decision requires per-preference URL.
- Persist returned `preference_id` and selected checkout URL.
- Redirect or render Wallet Brick using the decided strategy.
- Prevent duplicate checkout submissions with a server-side pending payment guard.

Acceptance:

- `external_reference` is durable.
- Browser return pages are not used to mark payment paid.
- Duplicate clicks do not create multiple orders for the same cart intent.

User must test:

- Complete checkout start once.
- Refresh/back/retry and confirm it does not create accidental duplicate orders.

### Wave 04 - Webhook Route, Journal, And Signature Validation

Goal: receive Mercado Pago webhooks securely and make 401s explainable.

Tasks:

- Add `POST /webhooks/mercado-pago`.
- Exclude only this route from CSRF.
- Create `mercado_pago_webhook_requests`.
- Implement a small signature verifier:
  - parse `x-signature` into `ts` and `v1`;
  - read `x-request-id`;
  - read `data.id` from query params when present;
  - remove absent manifest parts as documented;
  - compute `hash_hmac('sha256', manifest, secret)`;
  - compare with `hash_equals`;
  - optionally enforce timestamp tolerance.
- Return 401 for invalid signatures.
- Return 200 for valid ignored events.
- Add feature tests with valid signature, invalid signature, missing headers, and unsupported topic.

Acceptance:

- The Mercado Pago simulator can hit the ngrok URL and receive 200 when signed correctly.
- Invalid requests are saved and rejected.
- The request table is readable and not confused with payment state.

User must test:

- In Mercado Pago Webhooks, use "Modo de teste".
- URL: `https://gains-bootlace-slacking.ngrok-free.dev/webhooks/mercado-pago`.
- Enable only `Pagamentos`.
- Use "Simular" with event `payment` and a known Data ID.
- Confirm the app returns 200 for valid simulation and stores a journal row.

### Wave 05 - Payment Fetch Gateway And Idempotent Processor

Goal: update local payment/order from verified provider data.

Tasks:

- Add a `PaymentDetailsGateway` contract.
- Implement Mercado Pago get-payment using SDK or HTTP behind the contract.
- Fetch `GET /v1/payments/{id}` after a verified webhook.
- Normalize provider fields into a DTO.
- Match local payment by `external_reference`.
- Update local payment status/detail/provider snapshot.
- Update local order status using the mapping in this document.
- Restore stock once for failed terminal states if the current order creation decrements stock before payment.
- Make repeated webhook delivery idempotent.

Acceptance:

- No order is updated from unverified browser query params.
- Repeated webhooks do not duplicate state changes.
- Provider status and status detail are preserved.

User must test:

- Complete a test purchase.
- If real test-payment notification does not arrive, use the Mercado Pago simulator with the returned `payment_id` as Data ID.
- Confirm local payment/order update after the simulator hits the webhook.

### Wave 06 - Return Pages And Admin Visibility

Goal: make the buyer/admin experience honest and operational.

Tasks:

- Return pages explain that payment status is confirmed server-side.
- Admin order detail shows local payment status, provider payment id, status detail, last provider update, and webhook journal link/reference if practical.
- Do not show secrets or card data.
- Add pt-BR and English copy.

Acceptance:

- Buyer is not told that payment is approved only because the browser returned.
- Admin can tell whether manual fulfillment is allowed.

User must test:

- Return from success/pending/failure pages.
- Open admin order detail.
- Confirm fulfillment decision is clear.

### Wave 07 - Final Validation And Operational Docs

Goal: close the payment module cleanly.

Tasks:

- Run focused tests for Payments, Orders, Checkout, Webhook, Admin orders.
- Run Pint test if available.
- Update local webhook testing docs.
- Create/update each `.codex/docs/pt-BR/wave-payment-##.md`.
- Run project `code-review` skill against payment changes.

Acceptance:

- No critical payment security/correctness finding remains.
- User has a clear manual checklist to validate the full flow.

User must test:

- Full checkout start.
- Mercado Pago test buyer purchase.
- Webhook simulator with the payment id.
- Admin payment verification.

## Immediate Risks To Address

- The previous task file marked unimplemented capabilities as done.
- Current code creates no order/payment before redirect, so a final purchase cannot be reconciled safely.
- Current `external_reference` is temporary and not useful for durable reconciliation.
- Current sandbox URL selection may be contributing to the reported loop.
- Test payments may not emit real webhooks; simulator testing must be planned explicitly.
- Accepting unsigned webhooks must not remain in the implementation.

## Definition Of Done

The payment module is complete for this MVP when:

- checkout creates durable local order/payment records;
- Checkout Pro receives a durable `external_reference`;
- webhook requests are journaled;
- Mercado Pago signatures are validated clearly;
- valid payment webhooks fetch provider payment data server-side;
- local payment/order states update idempotently;
- admin manual fulfillment has a clear verified-payment signal;
- every wave has a pt-BR user test document.
