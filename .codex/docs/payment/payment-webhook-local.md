# Mercado Pago Webhook Local Test Notes

Use this checklist for local Docker/WSL validation with Mercado Pago Webhooks.

1. Start the app normally and expose it through an HTTPS tunnel such as ngrok.
2. Set `APP_URL` to the public HTTPS tunnel URL.
3. Configure the Mercado Pago test-mode webhook URL as `https://your-tunnel.example/webhooks/mercado-pago`.
4. Enable only the Payments event/topic in the Mercado Pago integration.
5. Set test seller credentials in `.env`: `MERCADO_PAGO_ACCESS_TOKEN`, `MERCADO_PAGO_PUBLIC_KEY`, and `MERCADO_PAGO_WEBHOOK_SECRET`.
6. Set `MERCADO_PAGO_NOTIFICATION_URL` only when a per-preference override is intentionally needed. In local test purchases this can help bind the new preference to the tunnel URL; if used, create a fresh preference after changing the value.
7. Clear config when changing environment values: `docker exec ecommerce-app-1 php artisan config:clear`.
8. Use the Mercado Pago Webhooks simulator in "Your integrations" to send a `payment` event with a known Data ID.
9. Confirm Mercado Pago receives `200` for a valid signed simulation.
10. Confirm `mercado_pago_webhook_requests` stores headers, query, payload, `x-request-id`, `x-signature`, signature fields, and `processing_status=verified`.

Operational notes for Wave 04:

- The webhook validates and journals requests only.
- It does not fetch `GET /v1/payments/{id}` yet.
- It does not update local payment/order status yet.
- Test-mode real purchases can deliver signed `payment` webhooks when the test application, webhook secret, and preference notification URL are aligned.
- Mercado Pago can also send legacy Feed/IPN notifications with `topic` and `id`; these should be journaled and ignored with `200` so they do not retry or pollute the webhook delivery panel.

Never commit real Mercado Pago credentials or webhook secrets.
