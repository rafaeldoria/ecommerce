# Wave Payment 01 - Prova De Ambiente Mercado Pago

## Objetivo Da Wave

Esta wave removeu a configuracao ambigua `sandbox` do checkout Mercado Pago e separou duas decisoes que antes estavam misturadas:

- ambiente Laravel: `local`, `testing`, `production`;
- modo das credenciais Mercado Pago: `test` ou `production`;
- estrategia de URL do Checkout Pro: `init_point` ou `sandbox_init_point`.

A decisao inicial implementada e usar credenciais de teste em desenvolvimento local e selecionar `init_point` por padrao. `sandbox_init_point` continua disponivel, mas somente por configuracao explicita para prova controlada.

## O Que Foi Feito

- Substituido `MERCADO_PAGO_ENV=sandbox` por `MERCADO_PAGO_MODE=test|production`.
- Adicionado `MERCADO_PAGO_CHECKOUT_URL_STRATEGY=init_point|sandbox_init_point`.
- Atualizado `.env.example` sem segredos reais.
- Atualizado `config/services.php` para expor `credential_mode` e `checkout_url_strategy`.
- Ajustado o gateway Mercado Pago para configurar o SDK como:
  - `MercadoPagoConfig::LOCAL` quando `MERCADO_PAGO_MODE=test`;
  - `MercadoPagoConfig::SERVER` quando `MERCADO_PAGO_MODE=production`.
- Extraida a escolha de URL para uma classe pequena e testavel.
- Criado um wrapper pequeno para o client de preference do SDK, permitindo testes sem chamada real de rede.
- Criados testes que cobrem:
  - modo `test` usando runtime local do SDK;
  - modo `production` usando runtime server do SDK;
  - `init_point` como estrategia padrao;
  - `sandbox_init_point` apenas quando configurado;
  - rejeicao de valores invalidos para modo e estrategia.
- Executada uma prova real com as credenciais locais:
  - preference criada com sucesso;
  - Mercado Pago retornou `init_point` com host `www.mercadopago.com.br`;
  - Mercado Pago retornou `sandbox_init_point` com host `sandbox.mercadopago.com.br`;
  - estrategia configurada no app: `init_point`.

## Desenho Do Que Foi Feito

```text
/checkout
  |
  v
CreateCheckoutPreferenceAction
  |
  v
CheckoutPreferenceGateway
  |
  v
MercadoPagoCheckoutPreferenceGateway
  |-- le services.mercado_pago.credential_mode
  |-- configura MercadoPagoConfig LOCAL ou SERVER
  |-- monta payload com MercadoPagoPreferenceRequestFactory
  |-- cria preference com MercadoPagoPreferenceClient
  |-- escolhe URL com MercadoPagoCheckoutUrlResolver
  |
  v
CheckoutPreferenceResult
  |
  v
Wallet Brick recebe preferenceId e publicKey
```

Fluxo de configuracao:

```text
.env
  MERCADO_PAGO_MODE=test
  MERCADO_PAGO_CHECKOUT_URL_STRATEGY=init_point
      |
      v
config/services.php
      |
      v
Gateway Mercado Pago
```

## Implementacao Por Classe E Funcao

### `config/services.php`

- `services.mercado_pago.credential_mode`: le `MERCADO_PAGO_MODE` e usa `test` como padrao.
- `services.mercado_pago.checkout_url_strategy`: le `MERCADO_PAGO_CHECKOUT_URL_STRATEGY` e usa `init_point` como padrao.

### `MercadoPagoCheckoutPreferenceGateway`

- `create(CheckoutPreferenceData $data)`: valida credenciais, configura o SDK conforme o modo das credenciais, cria a preference e retorna `preferenceId`, `publicKey` e a URL selecionada.
- `credentialMode()`: normaliza e valida `test|production`. Valores como `sandbox` agora falham explicitamente em vez de controlar comportamento de forma ambigua.

### `MercadoPagoCheckoutUrlResolver`

- `resolve(object $preference)`: escolhe a URL de checkout conforme a estrategia configurada.
- `strategy()`: normaliza e valida `init_point|sandbox_init_point`.
- `stringOrNull(mixed $value)`: evita usar campos vazios ou nao-string vindos do SDK.

### `MercadoPagoPreferenceClient`

- `create(array $request)`: encapsula a chamada real ao `PreferenceClient` do SDK Mercado Pago. Isso mantem o gateway simples e permite testes com fake sem rede.

### `MercadoPagoCheckoutEnvironmentTest`

- Garante que as combinacoes de modo e estrategia funcionam sem depender da API real.
- Garante que configuracoes invalidas falham cedo com `PaymentConfigurationMissing`.

## O Que Deve Ser Testado No Navegador

1. Conferir `.env` local:

```dotenv
MERCADO_PAGO_ACCESS_TOKEN=TEST-...
MERCADO_PAGO_PUBLIC_KEY=TEST-...
MERCADO_PAGO_MODE=test
MERCADO_PAGO_CHECKOUT_URL_STRATEGY=init_point
```

2. Limpar config, se necessario:

```bash
docker exec ecommerce-app-1 php artisan config:clear
```

3. Abrir uma janela anonima/incognito.
4. Entrar no site local.
5. Adicionar um produto disponivel ao carrinho.
6. Abrir `/checkout`.
7. Preencher somente email e WhatsApp.
8. Gerar o botao Mercado Pago.
9. Clicar no Wallet Brick/checkout Mercado Pago.
10. Confirmar que a pagina hospedada do Mercado Pago abre sem loop.
11. Entrar com a conta de comprador de teste, nao com a conta vendedora.

Resultado esperado:

- O checkout abre no host `www.mercadopago.com.br`.
- O usuario nao fica preso em loop de sandbox.
- O app ainda nao cria pedido/pagamento local nesta wave.
- Retornos do navegador ainda nao confirmam pagamento, isso fica para waves futuras.

## Teste Opcional Para Comparar `sandbox_init_point`

Somente se quiser reproduzir a duvida do ambiente:

1. Alterar temporariamente:

```dotenv
MERCADO_PAGO_CHECKOUT_URL_STRATEGY=sandbox_init_point
```

2. Rodar:

```bash
docker exec ecommerce-app-1 php artisan config:clear
```

3. Repetir o checkout em janela anonima.

Resultado esperado para decisao da wave:

- Se houver loop ou comportamento instavel, voltar para `init_point`.
- Se funcionar sem loop, documentar o resultado antes de manter `sandbox_init_point`.

## Validacao Executada

```bash
docker exec ecommerce-app-1 php artisan test tests/Feature/Payments
```

Resultado:

- 10 testes passaram.
- 29 assertions passaram.

Prova real segura com credenciais locais:

- preference criada: sim;
- `init_point`: retornado com host `www.mercadopago.com.br`;
- `sandbox_init_point`: retornado com host `sandbox.mercadopago.com.br`;
- estrategia ativa: `init_point`.

## Limitacoes Conhecidas

- A prova automatica confirma configuracao e retorno dos campos da API, mas o loop relatado so pode ser confirmado no navegador com login de comprador de teste.
- Ainda nao existe persistencia local de pedido/pagamento no fluxo Mercado Pago.
- Ainda nao existe webhook nem validacao de assinatura.
- Ainda nao existe atualizacao idempotente de status de pagamento.
