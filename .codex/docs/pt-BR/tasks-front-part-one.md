# Tasks - Plano de Execucao do Frontend Parte Um

## Purpose

Este documento reenquadra o esforco atual de frontend em uma primeira entrega focada.

Ele existe porque `tasks-admin.md` confirma a baseline do backend admin e da API admin, mas isso nao prova que a experiencia web admin esteja pronta. A mesma lacuna existia no lado publico: rotas e layouts de base estavam presentes, enquanto a experiencia real da storefront ainda estava muito proxima de telas placeholder.

Por isso, a Parte Um do frontend prioriza impacto visual e operacional imediato:

- inventario visivel no catalogo
- troca de game na storefront
- footer real e melhor fechamento de pagina
- entrada web admin funcional
- telas admin navegaveis com dados operacionais

---

## Canonical Inputs

Use este arquivo junto com:

- `src/.codex/docs/project-front.md`
- `src/.codex/docs/decisions-front.md`
- `src/.codex/docs/tasks-front.md`
- `src/.codex/docs/tasks-admin.md`

Se houver conflito:

1. arquitetura frontend e decisoes documentadas vencem
2. a verdade do backend vence comportamentos especulativos da UI
3. este arquivo vence a suposicao de que o frontend MVP completo ja foi entregue

---

## Delivery Summary

Esta primeira parte substitui a ideia de “frontend MVP completo” por um escopo menor e revisavel, que faz o site parecer vivo e torna a superficie admin realmente acessivel.

Decisoes publicas desta parte:

- manter `GET /catalog` como rota canonica do catalogo
- usar selecao de game por query string:
  - `/catalog`
  - `/catalog?game=dota-2`
  - `/catalog?game=cs2`
- manter o header curto:
  - `Inicio`
  - `Catalogo`
  - `Carrinho`
- mover `About`, `Contact` e `FAQ` para o footer

Decisoes admin desta parte:

- `/admin/login` precisa funcionar no navegador
- o login frontend deve reaproveitar as regras de autenticacao admin ja existentes no backend
- a shell admin precisa expor navegacao real e logout explicito
- os modulos admin precisam renderizar dados operacionais reais em vez de texto placeholder

---

## Delivery Waves

### Wave P1.0 - Reality Check And Reframe

#### P100 - Auditoria da Lacuna Entre Admin e Frontend

Goal:

- deixar explicita a diferenca entre backend concluido e frontend concluido

Deliverables:

- documentar que `tasks-admin.md` cobre o escopo backend/admin API
- documentar que o estado inicial do frontend ainda era fortemente baseado em placeholders

Acceptance criteria:

- outro engenheiro entende rapidamente o que ja estava pronto no backend e o que ainda faltava no web

#### P101 - Baseline de IA da Parte Um

Goal:

- travar a arquitetura de informacao da primeira entrega realmente util

Deliverables:

- header da storefront reduzido a navegacao comercial
- links institucionais movidos para o footer
- entrada admin centralizada em `/admin/login`

Acceptance criteria:

- a navegacao fica comercialmente focada e deixa de dividir atencao demais no topo

### Wave P1.1 - Visibilidade da Storefront Primeiro

#### P110 - Visibilidade do Catalogo e Troca por Game

Goal:

- tornar o catalogo util de imediato

Deliverables:

- chips/tabs de game visiveis na pagina de catalogo
- grid de itens renderizado no servidor
- game selecionado persistido na query string

Acceptance criteria:

- o usuario consegue trocar entre Dota 2 e CS2 sem ambiguidade
- a primeira dobra da pagina ja mostra conteudo ligado ao inventario

#### P111 - Sistema de Card de Produto

Goal:

- padronizar a exibicao dos itens na storefront

Deliverables:

- padrao reutilizavel de card com imagem, nome, game, raridade, preco e CTA
- fallback estavel para imagem

Acceptance criteria:

- os cards continuam escaneaveis em mobile e desktop
- URLs de imagem quebradas nao desmontam o layout

#### P112 - Massa Demo de Catalogo para QA Visual

Goal:

- remover a necessidade de revisar a storefront com base em banco local vazio

Deliverables:

- pelo menos 5 produtos de Dota 2 gerados localmente
- pelo menos 5 produtos de CS2 gerados localmente
- imagens placeholder validas para revisao local

Acceptance criteria:

- um ambiente local novo consegue revisar a storefront sem depender de catalogo montado manualmente

### Wave P1.2 - Footer e Layout de Confianca

#### P120 - Reconstrucao do Footer

Goal:

- dar ao site um rodape real e uma melhor camada de confianca

Deliverables:

- footer em colunas com:
  - Sobre
  - Links rapidos
  - Atendimento
  - Contato
- footer ancorado no fim real da pagina

Acceptance criteria:

- o footer deixa de parecer uma linha temporaria de texto
- paginas curtas continuam terminando corretamente com o footer no final

#### P121 - Limpeza do Header

Goal:

- reduzir a competicao de prioridades na navegacao superior

Deliverables:

- remover `About`, `Contact` e `FAQ` do header
- manter essas paginas acessiveis pelo footer

Acceptance criteria:

- o header permanece focado em descoberta e fluxo de compra

### Wave P1.3 - Entrada Admin e Acesso Operacional

#### P130 - Ativacao do Login Admin

Goal:

- fazer a rota de login no navegador funcionar de verdade

Deliverables:

- `/admin/login` funcional
- regras de credencial admin ja existentes no backend reaproveitadas como fonte de verdade
- feedback de validacao para credenciais invalidas

Acceptance criteria:

- um admin valido entra pelo navegador e cai no dashboard admin
- um nao-admin nao entra na superficie admin

#### P131 - Prontidao da Shell Admin

Goal:

- fazer a area admin parecer uma ferramenta operacional separada

Deliverables:

- shell admin clara
- navegacao apos login
- logout explicito

Acceptance criteria:

- o usuario admin entende que esta em uma superficie autenticada separada

#### P132 - Baseline de Visibilidade do CRUD Admin

Goal:

- substituir paginas placeholder dos modulos por visibilidade operacional real

Deliverables:

- lista de jogos
- lista de raridades
- lista de produtos
- lista de pedidos
- detalhe do pedido

Acceptance criteria:

- a superficie admin passa a ser navegavel e util mesmo antes da expansao completa de forms CRUD

### Wave P1.4 - Hardening e Aceite

#### P140 - QA da Parte Um do Frontend

Goal:

- verificar que as tres queixas visiveis do usuario foram resolvidas de forma perceptivel no navegador

Acceptance criteria:

- catalogo mostra itens reais
- footer fica no fim da pagina e carrega os links institucionais
- login admin funciona e as rotas protegidas continuam protegidas

---

## Validation

Executar pelo container da app quando disponivel:

- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan test --filter=Frontend`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

Cenarios manuais obrigatorios para aceite:

- catalogo renderiza produtos reais em vez de texto placeholder
- trocar entre `dota-2` e `cs2` atualiza a lista renderizada
- estado vazio do catalogo continua intencional e legivel
- footer aparece no fim das paginas home, catalogo e produto
- header nao carrega mais `About`, `Contact` e `FAQ`
- `/admin/login` autentica um usuario admin valido
- usuarios nao-admin nao entram na area admin
- paginas `/admin/*` continuam protegidas
- seed local garante ao menos 5 produtos por game principal para QA visual

---

## Defaults And Assumptions

- Este arquivo e intencionalmente um plano de primeira entrega, nao um substituto para todo o escopo futuro do frontend.
- A storefront continua Blade-first e Livewire-first.
- A logica de negocio continua em Actions e boundaries de dominio do backend.
- As regras de autenticacao admin permanecem owned pelo backend; a UI web apenas orquestra a entrada.
- O conteudo do footer pode usar dados de negocio placeholder por enquanto, mas precisa soar deliberado e confiavel.
