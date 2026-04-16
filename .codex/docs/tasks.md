# Tasks - MVP Execution Plan

## Purpose

This file is the executable delivery plan for the MVP of the game items e-commerce project.

It is written to support agentic execution with Codex or multiple parallel agents. Each task block is designed to be:

- small enough to review
- explicit about scope and ownership
- safe to execute independently when dependencies are satisfied
- aligned with `project.md` and `decisions.md` as the canonical product and technical references

This plan assumes a **backend-first implementation**, while preserving clean extension points for Livewire UI and future payment automation.

---

## Canonical Inputs

This file must be executed together with the following source documents:

- `src/.codex/docs/project.md`
- `src/.codex/docs/decisions.md`
- the architecture and stack direction already defined in those documents:
  - modular monolith by domain
  - Actions as explicit use cases
  - Laravel 13 + Livewire + PostgreSQL

If any conflict appears:

1. architecture and technical decisions win over convenience
2. MVP scope wins over speculative future needs
3. this file must be updated before expanding scope

---

## Execution Rules

- Keep scope limited to the current task block.
- Prefer Laravel defaults before creating custom abstractions.
- Do not create a generic `Services` directory.
- Keep controllers and Livewire components thin.
- Put business flow in Actions and reusable domain rules in clearly named domain services only when truly needed.
- Keep Eloquent models focused on persistence, relationships, casts, and simple scopes.
- Use Queries or Repositories only when complexity justifies them.
- Validation belongs to the entry layer or input DTO boundary.
- Authorization belongs to Policies or Gates at the entry layer.
- Async work must use Events, Jobs, or queued notifications when appropriate.
- Use English as the default language and keep `pt_BR` ready.
- Add or update tests for every delivered capability.
- Run only the validations relevant to the task block before marking it complete.
- Do not partially implement deferred features just to "prepare" them.

---

## Task Status Model

Each task should move through the following states:

- `todo`: not started
- `doing`: currently owned by one agent
- `blocked`: cannot continue because a dependency or decision is missing
- `review`: implementation complete, awaiting verification
- `done`: accepted and validated

Only one agent should own a task in `doing` at a time.

---

## Coordination Rules For Multiple Agents

Use these rules to reduce overlap when work happens asynchronously:

- One task block, one owner.
- An agent may only edit files inside the write scope defined in its task unless the task explicitly allows otherwise.
- If a task uncovers a cross-cutting architectural change, stop and update this file before continuing.
- Shared files with high collision risk should be changed by a single coordination task:
  - `composer.json`
  - `bootstrap/app.php`
  - `config/*.php`
  - `routes/*.php`
  - top-level environment files
- Prefer creating independent module files over editing shared framework files when possible.
- If two tasks need the same shared file, the shared-file task must land first.

---

## Standard Validation Commands

Adjust only if the repository tooling changes.

- `php artisan test`
- `php artisan test --filter=...`
- `php artisan migrate --pretend`
- `php artisan route:list`
- `php artisan config:clear`
- `vendor/bin/pint --test`

If Pint is not used:

- `vendor/bin/phpcs`

---

## Delivery Waves

The project should be executed in waves. Tasks in the same wave may run in parallel when their dependencies allow it.

### Wave 0 - Foundation

Establish the repository baseline, architecture skeleton, and development rules.

### Wave 1 - Core Domains

Implement Catalog, Cart, and Orders foundations.

### Wave 2 - Entry Layer And Operations

Expose API endpoints, internal notifications, localization discipline, and quality guardrails.

### Wave 3 - Deferred Readiness

Prepare async and payment boundaries without implementing full gateway flows.

---

## Task Index

### Wave 0

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| T00 | Repository foundation audit | none | done |
| T01 | Modular architecture skeleton | T00 | done |
| T02 | Shared conventions and quality guardrails | T00 | done |

### Wave 1

| ID | Task | Depends On |
| --- | --- | --- |
| T10 | Catalog data model and actions | T01, T02 |
| T11 | Cart persistence strategy and actions | T01, T02, T10 |
| T12 | Orders domain and order creation flow | T10, T11 |

### Wave 2

| ID | Task | Depends On |
| --- | --- | --- |
| T20 | API entry layer for MVP flows | T10, T11, T12 |
| T21 | Internal notification flow for manual fulfillment | T12 |
| T22 | Localization and message discipline | T20 |
| T23 | Logging, exceptions, and repository-level verification | T20, T21, T22 |

### Wave 3

| ID | Task | Depends On |
| --- | --- | --- |
| T30 | Payment boundary placeholder | T12 |
| T31 | Async readiness conventions | T21, T30 |

---

## Detailed Tasks

## T00 - Repository Foundation Audit

### Goal

Confirm the Laravel repository is ready for implementation without changing business scope.

### Write Scope

- environment configuration files when required
- setup documentation
- testing and code-style configuration files

### Deliverables

- Laravel application boots correctly
- PostgreSQL connection is verified
- Docker workflow is verified
- `.env.example` and local setup assumptions are reviewed
- code style tooling is confirmed
- PHPUnit baseline is confirmed
- setup and verification commands are documented if missing

### Acceptance Criteria

- a new developer or agent can boot the project using documented steps
- tests can run
- style checks can run
- database connection assumptions are explicit

### Out Of Scope

- domain implementation
- business migrations
- frontend screens

### Validation

- `php artisan test`
- `php artisan about`
- `vendor/bin/pint --test`

---

## T01 - Modular Architecture Skeleton

### Goal

Create the minimum project structure that enforces the approved modular-by-domain architecture.

### Write Scope

- `app/Modules/**`
- autoload or namespace configuration if needed
- architecture notes in project docs if needed

### Deliverables

- `app/Modules` exists
- initial domain modules exist:
  - `Catalog`
  - `Cart`
  - `Orders`
  - `Payments`
  - `Admin`
- each module contains only the subdirectories it actually needs, selected from:
  - `Actions`
  - `Models`
  - `Queries`
  - `Policies`
  - `DTOs`
  - `Events`
  - `Listeners`
  - `Gateways`
- names and namespaces are coherent
- no generic `Services` folder is introduced

### Acceptance Criteria

- the structure clearly reflects domain ownership
- shared framework files are touched only if required
- no circular module assumptions are introduced

### Out Of Scope

- full feature implementation
- default repositories everywhere
- speculative infrastructure layers

### Validation

- `php artisan test`
- `vendor/bin/pint --test`

---

## T02 - Shared Conventions And Quality Guardrails

### Goal

Lock in coding and delivery conventions needed before feature work scales.

### Write Scope

- repository documentation
- code-style configuration
- testing conventions
- localization baseline files

### Deliverables

- explicit guidance for Actions, Policies, DTOs, and Queries
- testing expectations documented
- language and localization baseline clarified
- logging and sensitive-data rules clarified if not already present

### Acceptance Criteria

- the team has enough written guidance to avoid architectural drift
- feature tasks can proceed without inventing new local conventions

### Out Of Scope

- implementation of business features
- broad refactors unrelated to delivery standards

### Validation

- documentation review
- `vendor/bin/pint --test`

---

## T10 - Catalog Data Model And Actions

### Goal

Implement the Catalog module foundation for games, rarities, and products.

### Write Scope

- `app/Modules/Catalog/**`
- related migrations, factories, and tests
- module-specific language files if needed

### Deliverables

- migrations for:
  - games
  - rarities
  - products
- Eloquent models for:
  - `Game`
  - `Rarity`
  - `Product`
- relationships, casts, and simple scopes only
- Actions:
  - `CreateProductAction`
  - `UpdateProductAction`
- product relationships remain limited to `game` and `rarity` for the MVP
- Query objects only if filtering or listing complexity justifies them
- factories for core catalog entities
- tests for the main happy path and relevant invalid cases

### Acceptance Criteria

- schema migrates cleanly
- product creation and update happen through Actions
- catalog classification uses `game` and `rarity`, without introducing `category`
- controllers or models do not absorb business orchestration
- the Catalog module is usable by Cart and Orders

### Out Of Scope

- admin UI
- storefront UI
- advanced search
- stock automation

### Validation

- `php artisan migrate --pretend`
- `php artisan test --filter=Catalog`
- `vendor/bin/pint --test`

---

## T11 - Cart Persistence Strategy And Actions

### Goal

Implement the cart backend needed by the MVP and make the persistence decision explicit.

### Write Scope

- `app/Modules/Cart/**`
- cart-related routes or requests only if the task explicitly includes them
- cart tests

### Deliverables

- cart persistence strategy selected and documented:
  - session-based
  - database-backed
- cart item structure defined
- Actions:
  - `AddToCartAction`
  - `RemoveFromCartAction`
  - `UpdateCartItemAction`
- validation rules for product identity and quantity
- tests for happy path and edge cases

### Acceptance Criteria

- items can be added, removed, and updated
- invalid product references fail clearly
- invalid quantities fail clearly
- Cart can feed order creation without leaking HTTP concerns

### Out Of Scope

- coupon system
- shipping
- multi-cart support
- frontend cart screen

### Validation

- `php artisan test --filter=Cart`
- `vendor/bin/pint --test`

---

## T12 - Orders Domain And Order Creation Flow

### Goal

Allow the MVP to create an order from cart data and customer contact input.

### Write Scope

- `app/Modules/Orders/**`
- related migrations, factories, events, and tests

### Deliverables

- migrations for:
  - orders
  - order items if required by the design
- order-related models
- explicit MVP order status lifecycle
- `CreateOrderAction`
- `OrderCreated` event
- storage for:
  - email
  - WhatsApp number
- transaction boundaries only where consistency requires them
- tests for successful order creation and failure paths

### Acceptance Criteria

- valid input creates an order from current cart state
- order items persist correctly
- initial status is explicit and correct
- `OrderCreated` is dispatched
- the flow is compatible with manual fulfillment

### Out Of Scope

- automatic delivery
- payment capture
- advanced orchestration

### Validation

- `php artisan migrate --pretend`
- `php artisan test --filter=Order`
- `vendor/bin/pint --test`

---

## T20 - API Entry Layer For MVP Flows

### Goal

Expose the approved MVP backend flows through thin HTTP endpoints.

### Write Scope

- API controllers
- Form Requests
- Policies or Gates
- API routes
- endpoint feature tests

### Deliverables

- thin controllers for catalog, cart, and order flows
- Form Requests where applicable
- Policies or Gates where applicable
- routes for:
  - catalog read
  - cart mutation
  - order creation
- consistent JSON response approach
- error handling pattern for expected API failures
- feature tests for main endpoints

### Acceptance Criteria

- controllers remain orchestration-light
- Actions remain the business entry point
- validation and authorization stay in the entry layer
- route organization is readable

### Out Of Scope

- frontend integration
- complex authentication beyond MVP needs
- public API versioning unless truly required

### Validation

- `php artisan route:list`
- `php artisan test --filter=Api`
- `vendor/bin/pint --test`

---

## T21 - Internal Notification Flow For Manual Fulfillment

### Goal

Make newly created orders visible to the internal team so manual fulfillment can happen reliably.

### Write Scope

- order notification classes
- event listeners or queued jobs
- configuration needed for the chosen notification channel
- related tests

### Deliverables

- one explicit internal notification strategy for MVP
- order information delivered through a maintainable backend-safe channel, such as:
  - email
  - queued notification
  - log-only fallback if intentionally chosen
- future WhatsApp automation identified as deferred
- tests where practical

### Acceptance Criteria

- internal staff can receive the data needed to fulfill an order manually
- sensitive data is handled responsibly
- the notification path fits the current MVP maturity

### Out Of Scope

- direct WhatsApp automation
- customer notification center
- omnichannel workflows

### Validation

- `php artisan test --filter=Notification`
- `vendor/bin/pint --test`

---

## T22 - Localization And Message Discipline

### Goal

Keep backend messages consistent, localizable, and aligned with English-first support plus `pt_BR` readiness.

### Write Scope

- `lang/**`
- validation and exception message sources
- user-facing backend response strings

### Deliverables

- default language is confirmed as English
- `pt_BR` structure exists or is ready for extension
- new user-facing strings are not hardcoded where localization should apply
- validation and exception messages are reviewed for consistency

### Acceptance Criteria

- backend responses have a clear localization path
- message keys or language strategy are consistent enough for future UI work

### Out Of Scope

- full frontend translation
- complete translation of deferred features

### Validation

- targeted test run for affected areas
- manual review of new language files

---

## T23 - Logging, Exceptions, And Repository-Level Verification

### Goal

Finish the MVP backend foundation with maintainability and operational clarity.

### Write Scope

- exception classes
- logging-related configuration or usage
- repository-level docs for quality checks
- tests touching cross-cutting behavior

### Deliverables

- clear exceptions where they improve maintainability
- logging usage aligned with sensitive-data rules
- repository-level verification commands documented and runnable
- most important business flows covered by tests
- no duplication introduced by previous tasks without review

### Acceptance Criteria

- failures are understandable
- logs help debugging without leaking sensitive information
- the repository can be checked consistently before merge

### Out Of Scope

- enterprise observability stack
- metrics platform
- tracing platform

### Validation

- `php artisan test`
- `vendor/bin/pint --test`

---

## T30 - Payment Boundary Placeholder

### Goal

Prepare a clean boundary for future payment work without pretending the integration already exists.

### Write Scope

- `app/Modules/Payments/**`
- payment migrations or models only if justified
- payment DTOs, enums, Actions, and contracts
- documentation notes for deferred work

### Deliverables

- payment status representation for the future lifecycle
- payment-related DTOs if useful
- placeholder Action:
  - `CapturePaymentAction`
- gateway boundary or contract only if it clearly reduces future rework
- documentation note stating what remains intentionally deferred

### Acceptance Criteria

- payment code does not overcomplicate the MVP
- async-ready direction is preserved
- no fake full integration is introduced

### Out Of Scope

- real PIX integration
- real credit card integration
- webhook processing
- retries
- outbox implementation

### Validation

- `php artisan test --filter=Payment`
- `vendor/bin/pint --test`

---

## T31 - Async Readiness Conventions

### Goal

Prepare the codebase for future asynchronous growth without adding distributed-system complexity too early.

### Write Scope

- queue configuration
- event listeners or jobs
- background processing documentation
- async-related tests

### Deliverables

- queue configuration reviewed
- at least one concrete Event + Listener or Job flow is working
- async boundaries for Orders and Payments are documented
- failure-safe logging for background processing is in place

### Acceptance Criteria

- the project can evolve into async flows cleanly
- background-processing conventions are explicit
- current MVP remains simple

### Out Of Scope

- full outbox implementation
- production-grade retry orchestration
- advanced payment workflow

### Validation

- `php artisan test --filter=Queue`
- `vendor/bin/pint --test`

---

## Parallel Execution Matrix

Use this matrix to split work safely across multiple agents.

### Phase A

- Agent 1: T00
- Agent 2: T02

T01 should start after T00 confirms the repository baseline.

### Phase B

- Agent 1: T10
- Agent 2: T11 planning can begin after T10 defines product identity assumptions, but implementation should wait for T10 review

### Phase C

- Agent 1: T12
- Agent 2: T30 can begin design-only work after T12 defines order lifecycle assumptions

### Phase D

- Agent 1: T20
- Agent 2: T21
- Agent 3: T22

T23 should consolidate after T20, T21, and T22 are in review.

### Phase E

- Agent 1: T31
- Agent 2: repository-wide verification and cleanup under T23 if still open

---

## Suggested Ownership Boundaries

These boundaries are recommendations to reduce merge conflicts.

- Catalog owner:
  - `app/Modules/Catalog/**`
  - catalog migrations
  - catalog factories
  - catalog tests
- Cart owner:
  - `app/Modules/Cart/**`
  - cart tests
- Orders owner:
  - `app/Modules/Orders/**`
  - order migrations
  - order factories
  - order tests
- Payments owner:
  - `app/Modules/Payments/**`
  - payment tests
- API owner:
  - `app/Http/Controllers/Api/**`
  - `app/Http/Requests/**`
  - `routes/**`
  - endpoint tests
- Cross-cutting owner:
  - `config/**`
  - `lang/**`
  - repository docs
  - shared quality tooling

---

## Definition Of Ready

A task is ready to start only when:

- dependencies are marked `done` or the task explicitly allows design work before dependency completion
- write scope is clear
- validation approach is clear
- acceptance criteria are specific enough to review

---

## Definition Of Done

A task is done only when:

- deliverables are implemented
- acceptance criteria are verified
- relevant tests pass
- style checks pass for touched code
- deferred items remain deferred and documented
- no architectural rule was broken to â€œsave timeâ€

---

## Final Review Checklist

Before calling the MVP backend foundation complete, verify:

- backend-first scope was respected
- modular-by-domain architecture was respected
- no fat controllers or fat Livewire components were introduced
- no generic `Services` folder was introduced
- Actions are explicit and use-case oriented
- Eloquent models are not carrying cross-cutting business orchestration
- tests exist for delivered capabilities
- route organization is understandable
- localization path exists for backend messages
- deferred work is documented rather than half-implemented

---

## Explicitly Deferred After This Plan

These items remain outside the current execution plan:

- Livewire storefront implementation
- admin UI screens
- automated in-game item delivery
- real payment gateway integrations
- outbox implementation
- advanced stock automation
- marketplace or multi-seller support
- microservices extraction

---

## Notes For Agent Execution

- Complete one task block at a time.
- Do not silently expand scope.
- Prefer small, reviewable diffs.
- Update this file if a structural decision changes execution order.
- Follow repository-specific instructions such as `AGENTS.md` if present.
- When in doubt, choose the simplest implementation that respects the architecture.
