# Production Security Checklist

Use this checklist before deploying the ecommerce app with real buyer or payment data.

## Required Environment Settings

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` must use HTTPS.
- `SESSION_SECURE_COOKIE=true`
- `SESSION_ENCRYPT=true`
- `DB_SSLMODE=require` when the database supports TLS.
- `TRUSTED_PROXIES` must list only the ingress/load balancer proxy addresses. Do not use `*` in production.
- `SANCTUM_EXPIRATION` must be finite.
- `SANCTUM_TOKEN_PREFIX` must be set for secret scanning.
- `MERCADO_PAGO_WEBHOOK_SIGNATURE_TOLERANCE_SECONDS` should stay non-zero, normally `300`.
- `MERCADO_PAGO_CHECKOUT_ALLOWED_HOSTS` should contain only expected Mercado Pago checkout hosts.

## Admin Access

- Enable MFA before processing real buyer/payment data. The preferred local implementation path is TOTP with encrypted per-admin secrets and recovery codes.
- Keep admin accounts limited to staff who need catalog, stock, order, and payment-verification access.
- Use strong unique passwords and rotate/revoke admin API tokens after staff changes or suspected exposure.

## Runtime Operations

- Run `php artisan payments:prune-mercado-pago-webhooks` on a schedule to enforce webhook journal retention.
- Keep the Docker Compose stack in `../docker/docker-compose.yml` for local development only. It publishes web, Vite, and PostgreSQL ports and bind-mounts source code for developer convenience.
- Production deployment should use private database networking, no Vite service, no bind-mounted source tree, and least-privilege container users.
