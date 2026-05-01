# Tasks - Payment Completion Plan

## Purpose

This file is the executable delivery plan for completing the Mercado Pago Checkout Pro flow.

It continues the initial sandbox integration from `waves/wave-payment.md` and turns it into a real purchase path:

- the buyer fills only email and WhatsApp
- the application creates one pending order from the session cart
- Mercado Pago Checkout Pro receives an `external_reference` that points back to that order
- the buyer is redirected to Mercado Pago
- Mercado Pago webhooks update the local order after the payment is confirmed
- admin fulfillment remains manual after payment completion

The implementation must stay simple, secure, testable, and aligned with `project.md`, `project-front.md`, `decisions.md`, and `decisions-front.md`.

---

## Canonical Inputs

Execute this plan together with:

- `src/.codex/docs/project.md`
- `src/.codex/docs/project-front.md`
- `src/.codex/docs/decisions.md`
- `src/.codex/docs/decisions-front.md`
- `src/.codex/docs/tasks.md`
- `src/.codex/docs/tasks-front.md`
- `src/.codex/docs/waves/wave-payment.md`

Mercado Pago references checked for this plan:

- Checkout Pro payment notifications: https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/payment-notifications
- Checkout Pro webhooks: https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/additional-content/notifications/webhooks
- Checkout Pro rejection reasons and duplicate-payment risk: https://www.mercadopago.com.br/developers/pt/docs/checkout-pro/how-tos/improve-payment-approval/reasons-for-rejection
- Payment status examples: https://www.mercadopago.com.br/developers/pt/docs/checkout-api-payments/how-tos/integrate-3ds

If any conflict appears:

1. documented architecture and technical decisions win over convenience
2. security and payment correctness win over UI shortcuts
3. local order state must be updated from verified server-side payment data, not from browser return URLs
4. this file must be updated before expanding the payment scope

---

## Mercado Pago Findings For This Wave

Checkout Pro webhook notifications for payments are configured through the Payments event/topic and are sent when a payment is created or changes state, including pending, rejected, and approved states.

Mercado Pago expects the webhook endpoint to answer with HTTP 200 or 201 within 22 seconds. After acknowledging the notification, the application should fetch the complete payment data by the received payment ID.

Webhook authenticity should be validated with the `x-signature` header, `x-request-id`, the `data.id` query parameter, and the webhook secret generated in Mercado Pago integrations. The signature is an HMAC SHA-256 comparison over Mercado Pago's documented manifest format.

For this project, the local order enum must be project-owned and intentionally simpler than Mercado Pago's payment statuses:

- `pending`: local order exists and is waiting for payment confirmation
- `completed`: Mercado Pago payment was verified as approved/accredited
- `error`: payment failed, was rejected, was cancelled, or could not be safely completed

The Mercado Pago status should still be stored separately in the payment record for audit/debugging. The local order status should not try to mirror every Mercado Pago status.

---

## Execution Rules

- Keep scope limited to the active task block.
- Use Docker commands through `ecommerce-app-1` when containers are available.
- Keep Livewire components thin; payment and order orchestration belongs in Actions.
- Keep Mercado Pago SDK and HTTP details isolated behind Payments gateways/clients.
- Never trust browser return URLs as proof of payment.
- Do not expose private Mercado Pago credentials to Blade, Livewire browser events, or JavaScript.
- Use `external_reference` to connect Mercado Pago preferences/payments to local orders.
- Make order creation and stock decrement transactional.
- Prevent duplicate checkout submissions at the browser and server level.
- Make webhook processing idempotent; repeated Mercado Pago retries must not create duplicate payments, duplicate orders, or double-decrement/restock inventory.
- Store money as integer cents locally.
- Add or update tests for every delivered capability.
- Run code review at the end of every implementation wave using the project `code-review` skill.
- Close each completed implementation wave with wave-scoped commits following `.codex/skills/wave-git-close/SKILL.md`.

---

## Status And Payment Mapping

Local order status enum:

```php
App\Modules\Orders\Enums\OrderStatus
```

Values:

- `pending`
- `error`
- `completed`

Recommended Mercado Pago mapping:

- `approved` with `status_detail=accredited` -> `completed`
- `pending`, `in_process`, `in_mediation` -> keep `pending`
- `rejected`, `cancelled`, `canceled`, `refunded`, `charged_back` -> `error`
- unknown or malformed status -> keep `pending`, log context, and do not fulfill

The exact Mercado Pago `status` and `status_detail` must be saved on the payment record. The order enum is a business state, not a copy of Mercado Pago's API.

---

## Localhost And Test Environment

For sandbox webhook tests, the app needs a public HTTPS URL. Use ngrok or an equivalent tunnel and configure:

- `APP_URL=https://...ngrok-free.app`
- Mercado Pago webhook URL: `https://...ngrok-free.app/webhooks/mercado-pago`
- seller test user credentials in `.env`
- buyer test user account in the hosted Mercado Pago checkout
- Mercado Pago sandbox card/test payment data

Add config keys for:

- `MERCADO_PAGO_WEBHOOK_SECRET`
- optional `MERCADO_PAGO_WEBHOOK_TOLERANCE_SECONDS`

Keep `.env.example` updated without real secrets.

---

## Delivery Waves

### Wave P0 - Payment Domain Foundation

Create the local order/payment state needed before redirecting buyers to Mercado Pago.

### Wave P1 - Checkout Redirect Flow

Replace the current "Generate Mercado Pago button" test UX with a secure "Concluir compra" flow that creates one pending order and redirects to Checkout Pro.

### Wave P2 - Mercado Pago Webhook Processing

Receive, validate, store, and process Mercado Pago payment notifications.

### Wave P3 - Return Pages, Admin Visibility, And Buyer Trust

Make the post-payment and admin experience clear without pretending the browser return confirms payment.

### Wave P4 - Payment Wave Review, Hardening, And Git Close

Run project-aware code review, validation, and wave-scoped commits.

---

## Task Index

### Wave P0

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| P00 | Current Payment Flow Recheck | wave-payment | done |
| P01 | Order Status Enum And Migration | P00 | done |
| P02 | Payment Persistence Model | P01 | done |
| P03 | Pending Order Checkout Action | P01, P02 | done |

### Wave P1

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| P10 | Preference Creation From Pending Order | P03 | done |
| P11 | Checkout Submit Redirect UX | P10 | done |
| P12 | Duplicate Submission Protection | P10, P11 | done |

### Wave P2

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| P20 | Mercado Pago Webhook Route And Signature Verification | P10 | done |
| P21 | Payment Fetch Gateway | P20 | done |
| P22 | Idempotent Payment Status Processor | P21 | done |
| P23 | Webhook Test And Local Tunnel Documentation | P20, P22 | done |

### Wave P3

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| P30 | Return Page Status Messaging | P22 | done |
| P31 | Admin Order Payment Visibility | P22 | done |
| P32 | Buyer Trust Copy And Localization | P30, P31 | done |

### Wave P4

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| P40 | Payment Wave Code Review | P00-P32 | done |
| P41 | Payment Wave Validation | P40 | done |
| P42 | Payment Wave Git Close | P41 | done |

---

## Detailed Tasks

## P00 - Current Payment Flow Recheck

### Goal

Re-read the current checkout, order, payment, route, config, and test code before implementation starts.

### Write Scope

- none unless this document needs correction

### Deliverables

- current flow confirmed against `wave-payment.md`
- exact impacted files listed before edits
- risky shared files identified early:
  - `routes/web.php`
  - `config/services.php`
  - `.env.example`
  - checkout Livewire component and view
  - order/payment migrations and models

### Acceptance Criteria

- no implementation starts from stale assumptions
- any drift from this plan is documented before code changes

### Validation

- read-only inspection

---

## P01 - Order Status Enum And Migration

### Goal

Replace string order status constants with a project-owned enum that supports the payment flow.

### Write Scope

- `app/Modules/Orders/Enums/**`
- `app/Modules/Orders/Models/Order.php`
- order migrations or safe follow-up migration
- order/admin tests and factories if present

### Deliverables

- `OrderStatus` enum with `pending`, `error`, and `completed`
- `Order` casts `status` to the enum
- existing order creation uses `OrderStatus::Pending`
- existing admin/order displays still work
- migration strategy preserves existing data, including old `pending_fulfillment` rows if they exist

### Acceptance Criteria

- local order status names are not coupled to Mercado Pago internal names
- tests prove a newly created order starts as pending
- admin order listings do not break when status is an enum

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Order`
- `docker exec ecommerce-app-1 php artisan migrate --pretend`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P02 - Payment Persistence Model

### Goal

Store Mercado Pago payment/preference state separately from the order while keeping the order status simple.

### Write Scope

- `database/migrations/**`
- `app/Modules/Payments/Models/**`
- `app/Modules/Payments/Enums/**` only if useful
- `app/Modules/Payments/DTOs/**`
- payment tests/factories if present

### Deliverables

- payment table linked to `orders`
- fields for local order relation, Mercado Pago preference ID, Mercado Pago payment ID, external reference, status, status detail, amount in cents, raw safe metadata, and timestamps
- uniqueness constraints for `external_reference`, preference ID when present, and Mercado Pago payment ID when present
- no sensitive credential or card data storage

### Acceptance Criteria

- each Mercado Pago preference/payment can be traced to one local order
- duplicate webhook retries can update the same payment row safely
- payment data stores audit context without storing private buyer card data

### Validation

- `docker exec ecommerce-app-1 php artisan migrate --pretend`
- `docker exec ecommerce-app-1 php artisan test --filter=Payment`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P03 - Pending Order Checkout Action

### Goal

Create one pending local order from the current cart before asking Mercado Pago for a preference.

### Write Scope

- `app/Modules/Orders/**`
- `app/Modules/Payments/Actions/**`
- `app/Modules/Payments/DTOs/**`
- checkout/order/payment tests

### Deliverables

- an explicit Action for starting a payment checkout from email, WhatsApp, and the session cart
- transactional order creation and stock decrement
- payment record creation with a stable `external_reference`
- clear behavior when cart is empty, stock is insufficient, or contact data is invalid

### Acceptance Criteria

- one successful checkout start creates exactly one pending order
- product stock is decremented once when the pending order is created
- cart clearing happens only after the application has enough state to resume via order/payment reference
- failed preference creation does not leave ambiguous buyer-facing state; either mark the order error or keep a recoverable pending state with clear logs

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Checkout`
- `docker exec ecommerce-app-1 php artisan test --filter=Order`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P10 - Preference Creation From Pending Order

### Goal

Create Mercado Pago preferences from the pending order instead of directly from the raw cart.

### Write Scope

- `app/Modules/Payments/Actions/**`
- `app/Modules/Payments/DTOs/**`
- `app/Modules/Payments/MercadoPago/**`
- payment tests

### Deliverables

- `external_reference` uses the local payment/order reference
- preference request includes `notification_url` only if the project chooses per-preference webhook configuration
- preference result is persisted on the payment row
- cents-to-BRL conversion remains explicit and tested

### Acceptance Criteria

- Mercado Pago checkout can be correlated back to the local order without session state
- missing Mercado Pago credentials fail clearly before redirect
- no private key reaches the frontend

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=CreateCheckoutPreference`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P11 - Checkout Submit Redirect UX

### Goal

Change the checkout from a test button flow into a buyer-friendly final purchase action.

### Write Scope

- `app/Livewire/Storefront/Checkout.php`
- `resources/views/livewire/storefront/checkout.blade.php`
- localization files
- Livewire checkout tests

### Deliverables

- button copy becomes `Concluir compra` in `pt-BR` and an equivalent concise English copy
- submit validates email and WhatsApp
- submit starts the pending order/payment flow
- when preference creation succeeds, the buyer is redirected to the Mercado Pago hosted checkout URL
- Wallet Brick test rendering is removed unless there is a clear product decision to keep it
- loading and error states are clear and accessible

### Acceptance Criteria

- buyer fills the form once and is sent to Mercado Pago without an extra generated-button step
- invalid fields show validation errors without creating an order
- payment configuration errors show a safe user-facing message
- JavaScript is not required for business-critical order/payment state

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Checkout`
- `docker exec ecommerce-app-1 php artisan test --filter=Livewire`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P12 - Duplicate Submission Protection

### Goal

Prevent accidental or malicious duplicate checkout attempts.

### Write Scope

- checkout Livewire component/view
- payment/order Actions
- payment/order persistence constraints
- tests

### Deliverables

- browser-level disabled/loading state during checkout submission
- server-side idempotency guard using session checkout attempt or a persisted pending payment reference
- database uniqueness constraints remain the final protection
- logs identify duplicate submission attempts without leaking sensitive data

### Acceptance Criteria

- double-clicking the button does not create two orders
- rapid repeated requests do not double-decrement stock
- Mercado Pago duplicate-payment risk is reduced by avoiding immediate duplicate preferences with identical data

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Checkout`
- `docker exec ecommerce-app-1 php artisan test --filter=Payment`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P20 - Mercado Pago Webhook Route And Signature Verification

### Goal

Expose a secure webhook endpoint for Mercado Pago payment notifications.

### Write Scope

- `routes/web.php` or a dedicated routes file if the app already supports one
- `app/Http/Controllers/**` or `app/Modules/Payments/Http/**` if introduced consistently
- `app/Modules/Payments/MercadoPago/**`
- `config/services.php`
- `.env.example`
- webhook tests

### Deliverables

- `POST /webhooks/mercado-pago`
- CSRF exception only for this webhook route
- signature verifier for `x-signature`, `x-request-id`, `data.id`, timestamp, and webhook secret
- safe rejection of missing or invalid signatures
- accepted verified notifications return 200 or 201 within Mercado Pago's timeout expectation

### Acceptance Criteria

- forged webhooks cannot update orders
- test mode works with Mercado Pago sandbox and an HTTPS tunnel
- route does not require buyer session state

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=MercadoPagoWebhook`
- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan config:clear`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P21 - Payment Fetch Gateway

### Goal

Fetch full payment details from Mercado Pago after receiving a verified webhook.

### Write Scope

- `app/Modules/Payments/Contracts/**`
- `app/Modules/Payments/MercadoPago/**`
- `app/Providers/AppServiceProvider.php`
- payment gateway tests

### Deliverables

- contract for retrieving a payment by Mercado Pago payment ID
- Mercado Pago SDK or HTTP implementation isolated from Actions
- DTO for normalized payment details
- clear handling for API failures, missing payment IDs, and unexpected payloads

### Acceptance Criteria

- order status changes are based on server-fetched Mercado Pago payment details
- Actions do not depend directly on SDK classes
- failures are logged and leave orders unfulfilled

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Payment`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P22 - Idempotent Payment Status Processor

### Goal

Update payment and order status safely from Mercado Pago payment details.

### Write Scope

- `app/Modules/Payments/Actions/**`
- `app/Modules/Orders/Actions/**` if a transition Action is needed
- `app/Modules/Payments/Jobs/**` if async processing is used
- payment/order tests

### Deliverables

- idempotent processor for Mercado Pago payment updates
- approved/accredited payments mark the order `completed`
- pending/in-process payments keep the order `pending`
- rejected/cancelled/refunded/charged-back payments mark the order `error`
- stock is not decremented twice
- if the implementation restores stock on `error`, it must do so exactly once and under a transaction

### Acceptance Criteria

- repeated webhook delivery produces the same final state
- out-of-order pending updates cannot downgrade a completed order
- browser return URLs cannot mark an order completed
- manual admin fulfillment starts only after `completed`

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Payment`
- `docker exec ecommerce-app-1 php artisan test --filter=Order`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P23 - Webhook Test And Local Tunnel Documentation

### Goal

Document the exact sandbox testing path for local development.

### Write Scope

- `.codex/docs/waves/**` only when closing the wave
- `.env.example`
- payment setup notes if a dedicated docs file exists or is created

### Deliverables

- documented ngrok/tunnel setup
- documented Mercado Pago webhook app configuration
- documented sandbox seller/buyer distinction
- documented test purchase checklist

### Acceptance Criteria

- a developer can test the webhook locally without guessing environment variables
- no real credentials are committed

### Validation

- documentation review

---

## P30 - Return Page Status Messaging

### Goal

Make Mercado Pago return pages useful while keeping webhook confirmation as the source of truth.

### Write Scope

- `routes/web.php`
- `resources/views/storefront/mercado-pago-return.blade.php`
- Livewire component if replacing the static view is useful
- localization files
- return page tests

### Deliverables

- success page says payment is being confirmed when webhook processing may still be pending
- pending and failure pages guide the buyer without exposing raw debug data
- optional lookup by safe order/payment reference when available

### Acceptance Criteria

- page copy does not promise fulfillment before verified payment completion
- raw query parameters are not dumped as the primary buyer experience
- support path is clear

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=MercadoPagoReturn`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P31 - Admin Order Payment Visibility

### Goal

Help admins understand payment state before manual fulfillment.

### Write Scope

- admin order Livewire/views
- order/payment queries
- localization files
- admin tests

### Deliverables

- admin order list/detail shows local order status
- order detail shows Mercado Pago payment ID, status, status detail, and update time when present
- completed orders are visually distinct from pending/error orders

### Acceptance Criteria

- admin can tell when payment is verified
- admin is not asked to fulfill an unconfirmed payment
- no sensitive payment credentials or card data are shown

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=AdminOrders`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P32 - Buyer Trust Copy And Localization

### Goal

Make the final checkout feel secure, modern, and easy to understand.

### Write Scope

- checkout and return localization files
- checkout Blade view
- support/help copy if reused by checkout

### Deliverables

- concise trust copy near the final button
- clear mention that payment is processed by Mercado Pago
- clear mention that fulfillment is manual after payment confirmation
- `pt-BR` and `en` keys for new copy

### Acceptance Criteria

- copy is honest and does not overpromise instant delivery
- copy supports confidence without adding extra fields or steps
- mobile checkout remains easy to scan

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Checkout`
- manual mobile/desktop UI check when a browser is available

---

## P40 - Payment Wave Code Review

### Goal

Run project-aware review before considering the payment wave complete.

### Write Scope

- none by default
- small fixes only if review findings are clear, local, and safe

### Deliverables

- review using `.codex/skills/code-review/SKILL.md`
- findings ordered by severity
- specific attention to payment security, idempotency, order boundaries, Livewire thinness, and MVP scope

### Acceptance Criteria

- no critical payment correctness or security finding remains unresolved
- any deferred finding is documented with rationale

### Validation

- review output

---

## P41 - Payment Wave Validation

### Goal

Run final validation for the completed payment implementation.

### Write Scope

- none unless validation exposes a required fix

### Deliverables

- focused tests for checkout, orders, payments, webhooks, admin orders, and return pages
- full test suite when focused tests pass
- route and config checks
- Pint check

### Acceptance Criteria

- relevant focused tests pass
- full suite passes or any unrelated failure is documented clearly
- route list includes the webhook and checkout routes
- config can be cleared successfully

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Checkout`
- `docker exec ecommerce-app-1 php artisan test --filter=Payment`
- `docker exec ecommerce-app-1 php artisan test --filter=MercadoPagoWebhook`
- `docker exec ecommerce-app-1 php artisan test --filter=AdminOrders`
- `docker exec ecommerce-app-1 php artisan test`
- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan config:clear`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## P42 - Payment Wave Git Close

### Goal

Close the completed wave with safe, reviewable commits.

### Write Scope

- git index and branch only

### Deliverables

- branch confirmed as wave-related
- branch confirmed as not `master`
- changes grouped into coherent commits
- branch pushed after final branch re-check

### Acceptance Criteria

- commits respect wave boundaries
- no unrelated local changes are included
- push completes from a non-master branch

### Validation

- follow `.codex/skills/wave-git-close/SKILL.md`
