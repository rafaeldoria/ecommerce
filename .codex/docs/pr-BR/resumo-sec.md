# Resumo Do PR 18 - Hardening De Seguranca

## Visao Geral

O PR 18 implementa uma wave de hardening para reduzir riscos nos pontos mais sensiveis do MVP: endpoints publicos de mutacao, checkout com Mercado Pago, webhook de pagamento, tokens da API admin, proxies confiaveis e defaults de producao.

O objetivo nao e mudar o produto ou expandir o escopo do MVP. O checkout continua minimo, com email e WhatsApp; o fulfillment continua manual; e o estoque continua sendo decrementado na criacao do pedido. A mudanca principal e adicionar controles de abuso, validacao e operacao ao redor desses fluxos.

## O Que O PR Faz

- adiciona rate limits para mutacoes publicas de carrinho, criacao publica de pedidos e webhooks do Mercado Pago;
- valida hosts permitidos antes de redirecionar compradores para URLs de checkout do Mercado Pago;
- limita o tamanho de payloads de webhook antes de gravar journal;
- registra webhooks com dados minimizados e sem headers sensiveis;
- valida assinatura e timestamp dos webhooks do Mercado Pago;
- evita reprocessar webhooks assinados que ja terminaram de forma idempotente;
- permite retry real quando um webhook assinado falha por erro transitorio de processamento;
- adiciona sanitizacao centralizada para payloads e snapshots do Mercado Pago;
- adiciona comando para limpar journals antigos de webhook;
- emite tokens Sanctum da API admin com prefixo e expiracao;
- pagina a listagem admin de pedidos na API;
- configura proxies confiaveis via `TRUSTED_PROXIES`;
- documenta configuracoes de producao, MFA esperado, retencao de webhooks e uso do Docker Compose apenas para desenvolvimento local.

## Comentarios Do Review

Dois comentarios do review foram tratados:

- A deduplicacao de webhook nao deve bloquear retries depois de uma falha 500. A regra agora so considera duplicado um webhook anterior com assinatura valida que terminou como `processed`, `ignored` ou `duplicate`.
- O throttle de webhook nao deve usar apenas `x-request-id`, porque esse header e controlado pelo cliente. O limiter agora usa o IP da requisicao como chave, impedindo bypass simples por rotacao de request id.

## Alteracoes Finais Aplicadas

- `HandleMercadoPagoWebhookAction` restringe o short-circuit de duplicidade a journals idempotentemente finalizados.
- `AppServiceProvider` aplica o limiter `mercado-pago-webhooks` por IP.
- `MercadoPagoWebhookTest` cobre retry depois de falha transitoria e throttle mesmo com `x-request-id` rotativo.

## Validacao

Nao rodei a suite Docker nesta finalizacao porque o Docker caiu no ambiente, conforme orientado. A checagem `git diff --check` passou.

Antes desta finalizacao, o PR ja documentava uma validacao completa da wave com `php artisan test`, `git diff --check` e `pint --test`, com pendencias de estilo pre-existentes fora do escopo da wave.

## Leitura Resumida

Este PR deixa o MVP mais defensivo sem mudar o fluxo de negocio. Ele reduz abuso em endpoints publicos, endurece o boundary de pagamento com Mercado Pago, diminui dados sensiveis persistidos, limita tokens privilegiados e torna as expectativas de producao mais explicitas.
