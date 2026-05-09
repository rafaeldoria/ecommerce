# Wave Security - Security Review Findings

## Scope

This review focused on security risks in the current Laravel/Livewire ecommerce project, using:

- `.codex/docs/project.md`
- `.codex/docs/project-front.md`
- `.codex/docs/decisions.md`
- `.codex/docs/decisions-front.md`
- the implemented routes, admin auth, cart/order, payment, webhook, upload, config, Docker, and tests.

Severity scale:

- `0` critical / most dangerous
- `1` high
- `2` medium
- `3` low
- `4` informational

No critical `0` issue was confirmed from the current code review.

## Findings

### [1] Public order creation can reserve stock and notify the team without payment abuse controls

Where:

- `routes/api.php:16-27`
- `app/Modules/Orders/Actions/CreateOrderAction.php:38-89`
- `app/Modules/Orders/Listeners/NotifyInternalTeamOfCreatedOrder.php:17-24`

What is the problem:

The public session API exposes `POST /api/orders` without authentication or a route throttle. That action creates an order, stores buyer contact data, decrements product stock, clears the cart, and dispatches the internal order notification.

This matches the original manual-fulfillment MVP, but after Mercado Pago checkout was introduced it becomes an abuse path parallel to the payment flow. A bot can create many unpaid `pending_fulfillment` orders, consume visible stock, and spam the internal order mailbox.

Risk:

- Inventory denial of service.
- Fraudulent operational noise for admins.
- Internal notification abuse.
- Buyer PII pollution in the database.

Recommended fix:

- Put public cart/order/checkout mutation endpoints behind rate limits.
- Consider disabling the old manual `POST /api/orders` path once payment checkout is the intended path.
- If manual orders must remain, create them as `pending_payment` or a clearly separate manual-review status.
- Add abuse controls such as per-session limits, per-IP limits, and optional contact verification before stock is decremented.

### [1] All proxies are trusted, allowing spoofed client IPs at the app boundary

Where:

- `bootstrap/app.php:35-40`
- `tests/Feature/Security/TrustedProxyTest.php`

What is the problem:

The app trusts forwarded headers from `*`. If the app is ever reachable directly, an attacker can spoof `X-Forwarded-For` and influence `request()->ip()`.

This matters because rate limiting and audit decisions depend on the client IP. The project already has tests confirming forwarded IPs are trusted.

Local/ngrok note:

This is expected to be more permissive during local webhook testing. Ngrok terminates the public HTTPS tunnel and forwards the request to the local app, so the app needs to understand forwarded host/proto/client headers for realistic webhook and URL behavior. The security problem is not "ngrok exists"; the problem is carrying a trust-all proxy setting into production or any environment where the app can receive direct untrusted traffic.

Risk:

- Brute-force and abuse throttles that rely on IP can be bypassed or polluted.
- Security logs/audit trails can record attacker-controlled IPs.
- HTTPS-aware URL/cookie behavior can be influenced by untrusted forwarded headers in a bad deployment.

Recommended fix:

- Configure trusted proxies from `TRUSTED_PROXIES`.
- Allow `*` only for local/test webhook work where ngrok is intentionally used.
- Do not trust `*` in production.
- Document the local ngrok profile separately from the production proxy profile.
- Add a production-oriented test that untrusted forwarded headers are ignored when the remote address is not a trusted proxy.

### [1] Mercado Pago webhook replay window is disabled by default

Where:

- `config/services.php:49-50`
- `app/Modules/Payments/MercadoPago/MercadoPagoWebhookSignatureVerifier.php:60-68`
- `app/Modules/Payments/MercadoPago/MercadoPagoWebhookSignatureVerifier.php:119-123`

What is the problem:

The signature verifier supports timestamp tolerance, but the default value is `0`, which means timestamp freshness is not enforced. A valid signed webhook can be replayed later and still pass signature validation.

The payment update action is mostly state-idempotent, which lowers direct payment-state risk. The remaining risk is still real: replayed requests can trigger provider fetches, create repeated webhook journal rows, and keep old signed messages valid indefinitely.

Risk:

- Replay amplification against the webhook endpoint.
- Repeated provider API calls.
- Webhook journal growth.
- Harder forensic analysis because stale signed traffic is accepted as valid.

Recommended fix:

- Set a non-zero production default, for example 300 seconds.
- Reject non-numeric timestamps when tolerance is enabled.
- Add replay detection using `x_request_id`, `signature_hash`, `provider_payment_id`, or a provider event id with an appropriate unique/indexed constraint.

### [2] Webhook endpoint journals every request before throttling or dedupe

Where:

- `routes/web.php:46-47`
- `app/Modules/Payments/Actions/HandleMercadoPagoWebhookAction.php:26-40`
- `database/migrations/2026_05_04_130000_create_mercado_pago_webhook_requests_table.php:18-31`

What is the problem:

Every webhook request is inserted into `mercado_pago_webhook_requests` before signature validation. The endpoint has no route throttle, request-size guard, dedupe check, or cleanup policy in code.

Keeping a webhook journal is good for operations, but unauthenticated public endpoints need storage-abuse protection. Invalid requests can still write headers, query, payload, and signature fields to the database.

Risk:

- Database growth from invalid webhook floods.
- Increased queue/web request pressure.
- Log/journal tables become noisy and harder to investigate.

Recommended fix:

- Add a webhook-specific throttle.
- Add request-size limits at the web server and Laravel boundary.
- Add a retention/cleanup job for old webhook journal rows.
- Add dedupe keys where Mercado Pago provides stable identifiers.

### [2] Raw payment provider snapshots and webhook payloads are retained without minimization

Where:

- `app/Modules/Payments/Actions/CreateCheckoutPreferenceAction.php:77-87`
- `app/Modules/Payments/Actions/ProcessMercadoPagoPaymentUpdateAction.php:82-89`
- `app/Modules/Payments/Actions/HandleMercadoPagoWebhookAction.php:26-40`
- `database/migrations/2026_05_04_130000_create_mercado_pago_webhook_requests_table.php:23-31`

What is the problem:

The app stores raw provider snapshots and raw webhook payloads. The admin UI intentionally avoids showing sensitive snapshot data, but the database still retains whatever the provider returns.

Payment provider responses can include payer, card, device, risk, or other sensitive metadata depending on the provider payload. The project does not yet define a redaction allowlist, retention period, or LGPD/GDPR deletion policy for this data.

Risk:

- Sensitive payment metadata exposure if the database or backups are accessed.
- Larger compliance scope than necessary.
- Harder credential/PII rotation and deletion story.

Recommended fix:

- Store only fields required for reconciliation and fulfillment.
- Redact or allowlist provider snapshot fields before persistence.
- Define retention for webhook journals and raw provider data.
- Keep the admin UI sanitized, but treat database minimization as a separate control.

### [2] Admin API tokens do not expire by default

Where:

- `app/Http/Controllers/Api/Admin/AuthController.php:30-35`
- `config/sanctum.php:55-70`
- `tests/Feature/Api/Admin/AuthApiTest.php`

What is the problem:

Admin API login issues a Sanctum plain text token. Sanctum expiration is `null`, and the token prefix defaults to an empty string.

The API is admin-only and role checked, which is good. The remaining issue is token lifecycle: a leaked admin token stays useful until explicit logout or manual revocation. Logout revokes only the current token, which is reasonable for multi-device use but increases the importance of expiration and token inventory.

Risk:

- Long-lived privilege after token leakage.
- Harder incident response.
- Secret scanning is less effective without a token prefix.

Recommended fix:

- Set a finite Sanctum token expiration for admin API tokens.
- Use a `SANCTUM_TOKEN_PREFIX`.
- Consider token abilities/scopes for admin resources.
- Add admin token revocation/rotation procedures.

### [2] Admin authentication is single-factor for privileged payment and PII access

Where:

- `app/Modules/Admin/Actions/AuthenticateAdminAction.php`
- `app/Livewire/Admin/Login.php`
- `app/Http/Controllers/Api/Admin/AuthController.php`

What is the problem:

Admin login uses username/email plus password only. The admin area can manage products, stock, and buyer contact data, and can view payment verification state.

Password hashing and role checks are implemented, and login throttling exists. The missing control is a second factor or step-up control for a privileged operations panel.

Risk:

- A stolen or reused admin password gives direct access to buyer PII and catalog/stock operations.
- Payment/manual-fulfillment decisions are exposed to a single credential factor.

Recommended fix:

- Add MFA before production or before real buyer/payment data is processed.
- At minimum, document an operational requirement for strong unique admin passwords and limited admin accounts.

Local implementation note:

MFA can be implemented locally. The simplest useful option is TOTP using an authenticator app, backed by a per-admin encrypted secret and recovery codes. For local development only, a lower-friction alternative is a fixed or logged one-time code behind an explicit `local`/`testing` guard, but that should not be reused for production. The production-ready direction should be TOTP first, with SMS/email codes treated as later integrations because they add provider, delivery, and abuse concerns.

### [3] Admin orders API returns all orders and buyer PII without pagination

Where:

- `app/Http/Controllers/Api/Admin/OrderController.php:18-23`
- `app/Modules/Orders/Queries/ListAdminOrdersQuery.php:11-16`

What is the problem:

The admin API order listing returns all orders with buyer email and WhatsApp. The web admin list is paginated, but the API path uses the unpaginated query.

This endpoint is protected by `auth:sanctum` and `admin`, so it is not public. The risk is blast radius: any compromised admin token can extract the entire order/contact dataset in one call.

Risk:

- Larger PII exposure during token compromise.
- Performance degradation as order volume grows.
- No practical data minimization for list views.

Recommended fix:

- Paginate the admin API order list.
- Consider returning less PII in list responses and keeping full contact data for the detail endpoint.
- Add audit logging for order detail access when production PII exists.

### [3] Checkout redirect URL is trusted from provider response without host allowlist

Where:

- `app/Modules/Payments/MercadoPago/MercadoPagoCheckoutUrlResolver.php:13-19`
- `app/Livewire/Storefront/Checkout.php:79-82`

What is the problem:

The checkout flow redirects to the `init_point` or `sandbox_init_point` returned by the provider SDK. There is no explicit host allowlist before `redirect()->away()`.

Because the value comes from Mercado Pago rather than a user-controlled request, this is not a direct open redirect. It is still worth hardening because payment redirects are highly sensitive trust moments.

Risk:

- If a bad configuration, test double, SDK issue, or compromised provider response returns an unexpected URL, buyers can be sent to an untrusted payment page.

Recommended fix:

- Allowlist expected Mercado Pago checkout hosts before redirecting.
- Fail closed if the checkout URL host is empty or not trusted.

### [3] Production cookie/session hardening is not encoded in defaults

Where:

- `config/session.php:50`
- `config/session.php:172-185`
- `.env.example:1-5`

What is the problem:

Session encryption defaults to `false`, and secure cookies depend entirely on `SESSION_SECURE_COOKIE`. The example environment is explicitly local and debug-enabled, which is fine for development, but there is no production example or guardrail showing secure cookie/session values.

Risk:

- A production deployment can accidentally ship without `SESSION_SECURE_COOKIE=true`.
- Session rows are not encrypted at rest when `SESSION_ENCRYPT=false`.

Recommended fix:

- Add a production environment checklist or `.env.production.example`.
- Require `APP_DEBUG=false`, HTTPS `APP_URL`, `SESSION_SECURE_COOKIE=true`, and preferably `SESSION_ENCRYPT=true` for production.
- Validate these settings during deployment.

### [3] Docker Compose exposes development services broadly

Where:

- `../docker/docker-compose.yml:21-30`
- `../docker/docker-compose.yml:32-48`
- `../docker/docker-compose.yml:50-60`

What is the problem:

The compose file publishes the web, Vite, and PostgreSQL ports and mounts the source tree into containers. This is normal for local development, but it should not be treated as a production container/security model.

Risk:

- Database or Vite exposure if the compose stack is run on a shared or public host.
- Source tree write access from containers.
- No production hardening such as read-only mounts, non-root users, or network isolation.

Recommended fix:

- Document this compose stack as local-development only.
- Use a separate production deployment definition with private DB networking, no Vite service, no bind-mounted source, and least-privilege container users.

## Positive Controls Observed

- Admin web routes are protected by `auth` and `admin` middleware.
- Admin API routes are protected by `auth:sanctum` and `admin` middleware.
- Passwords use Laravel hashed casts and `Hash::check`.
- Admin login throttling exists for web and API login flows.
- Cart/order/payment pricing is recalculated from server-side product/order data, not trusted from the browser.
- Order payment approval is based on signed webhook processing and provider fetches, not browser return URLs.
- Payment amount and currency are checked before marking a payment approved.
- Product image upload requires image validation, selected MIME types, and a 2 MB size limit.
- Blade output in reviewed templates uses escaped `{{ }}` output for user-visible fields.
- No user-controlled raw SQL query usage was found in the reviewed application code.
- Dependency audit results on 2026-05-09:
  - `docker exec ecommerce-app-1 composer audit --locked`: no security advisories found.
  - `docker exec ecommerce-app-1 npm audit --audit-level=moderate`: 0 vulnerabilities.

## Not Fully Covered In This Pass

- Live production TLS, CDN, WAF, DNS, backup encryption, and cloud bucket policy could not be verified from this repository alone.
- Real Mercado Pago dashboard configuration could not be verified from code.
- Runtime secret rotation policy is not represented in the repository.
- Accessibility was not treated as a security finding unless it affected access control or sensitive data exposure.

## Implementation Notes

Implemented in the `wave-security` branch:

- Added config-backed throttles for public cart mutations, public order creation, and Mercado Pago webhooks.
- Moved trusted proxy behavior behind `TRUSTED_PROXIES`, while keeping local/testing permissive for ngrok-style webhook work.
- Set Mercado Pago webhook signature tolerance to a non-zero default and added stricter timestamp coverage.
- Added webhook request size protection, replay dedupe for verified webhook signatures, and a retention command for webhook journals.
- Added Mercado Pago payload minimization before storing provider snapshots and webhook payloads.
- Added finite admin API token expiry and a token prefix default.
- Paginated the admin orders API list response.
- Added Mercado Pago checkout host allowlisting.
- Added `.env.production.example` and `.codex/docs/security-production.md` with production cookie/session, proxy, MFA, Docker, and operational checklist guidance.
