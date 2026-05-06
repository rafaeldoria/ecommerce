# Wave Payment 05 - Busca Do Pagamento E Processamento Idempotente

## O Que Foi Implementado

- Webhook valido de Mercado Pago agora busca o pagamento completo via `GET /v1/payments/{id}` antes de alterar estado local.
- O pagamento local e encontrado por `external_reference`.
- Status do Mercado Pago agora atualiza `payments.status`, `provider_payment_id`, `provider_status`, `provider_status_detail` e o snapshot bruto do provedor.
- Pedido local muda para `paid` quando o pagamento vem como `approved/accredited`.
- Mesmo com `approved/accredited`, o pedido so fica `paid` se valor e moeda do provedor baterem com o pagamento local.
- Pagamentos terminais de erro (`rejected`, `cancelled`, `canceled`, `refunded`, `charged_back`) mudam o pedido para `payment_failed`.
- Estoque reservado pelo pedido pendente e restaurado apenas uma vez, marcado em `payments.metadata.stock_restored_at`.
- Checkout reutiliza uma preference pendente quando o carrinho ficou vazio apos iniciar pagamento, mas cria uma nova quando o carrinho atual mudou.

## O Que Voce Deve Testar

1. Iniciar checkout com produto no carrinho e confirmar que o app redireciona para Mercado Pago.
2. Voltar/reenviar o checkout com o carrinho ja limpo pela tentativa anterior e confirmar que a mesma preference e reutilizada.
3. Alterar o carrinho depois de uma tentativa pendente e confirmar que uma nova preference e criada.
4. Usar o simulador de Webhooks do Mercado Pago com evento `payment` e um Data ID valido.
5. Confirmar no banco que o pagamento local recebeu `provider_payment_id`, status/status_detail e snapshot do provedor.
6. Simular status aprovado/acreditado e confirmar que o pedido fica `paid`.
7. Simular um pagamento aprovado com valor divergente e confirmar que o pedido continua pendente para analise.
8. Simular status rejeitado/cancelado/reembolsado e confirmar que o pedido fica `payment_failed` e o estoque volta apenas uma vez.

## Resultado Esperado

- Nenhuma URL de retorno do navegador marca pagamento como aprovado.
- Apenas webhooks assinados e dados buscados no Mercado Pago atualizam pagamento/pedido.
- Pagamento aprovado com valor/moeda divergente nao libera fulfillment.
- Webhooks repetidos nao duplicam restauracao de estoque.
- Carrinho alterado nao reutiliza preference antiga.

## Limitacoes Conhecidas

- O admin ainda nao exibe todos os detalhes de pagamento desta wave; isso fica para a proxima wave.
- Nao ha expiracao automatica de pagamentos pendentes abandonados.
- Fulfillment continua manual mesmo quando o pedido fica `paid`.
