# Mercado Pago Webhook Local Test Notes

Use this checklist for sandbox validation from a local Docker/WSL environment.

1. Start the app normally and expose it through an HTTPS tunnel such as ngrok.
2. Set `APP_URL` to the public HTTPS tunnel URL.
3. Configure the Mercado Pago webhook URL as `https://your-tunnel.example/webhooks/mercado-pago`.
4. Enable the Payments event/topic in the Mercado Pago integration.
5. Set sandbox seller credentials in `.env`: `MERCADO_PAGO_ACCESS_TOKEN`, `MERCADO_PAGO_PUBLIC_KEY`, and `MERCADO_PAGO_WEBHOOK_SECRET`.
6. Use a separate Mercado Pago buyer test user in the hosted Checkout Pro page.
7. Complete a test purchase with Mercado Pago sandbox payment data.
8. Confirm the local payment row received `mercado_pago_payment_id`, `status`, and `status_detail`.
9. Fulfill manually only after the local order status is `completed`.

Never commit real Mercado Pago credentials.
