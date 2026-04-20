# Tasks - Admin Backend Execution Plan

## Purpose

This file is the executable delivery plan for the admin backend expansion of the game items e-commerce project.

It is written to support agentic execution with Codex or multiple parallel agents. Each task block is designed to be:

- small enough to review
- explicit about scope and ownership
- safe to execute independently when dependencies are satisfied
- aligned with `project.md`, `decisions.md`, and the already executed MVP direction

This plan assumes a **backend-first implementation** for admin authentication, admin catalog management APIs, and admin order read APIs.

It must preserve the existing public MVP flow while adding a separate and secure admin boundary.

---

## Canonical Inputs

This file must be executed together with the following source documents:

- `src/.codex/docs/project.md`
- `src/.codex/docs/decisions.md`
- `src/.codex/docs/tasks.md`
- `src/.codex/docs/cart.md`

This file also assumes the repository already contains the completed MVP baseline described in `tasks.md`, including:

- public catalog listing
- session-backed cart flow
- order creation with minimal buyer contact data
- notification and deferred-readiness conventions

If any conflict appears:

1. architecture and technical decisions win over convenience
2. MVP scope and documented catalog rules win over speculative expansion
3. this file must be updated before expanding scope

---

## Execution Rules

- Keep scope limited to the current task block.
- When Docker containers are running, execute Laravel and PHP project commands through the app container instead of the raw host shell.
- Treat `ecommerce-app-1` as the default command target for `php artisan`, PHPUnit, and Pint commands unless the environment changes.
- Preserve the current public API behavior while introducing admin-only APIs.
- Keep public session-based cart and order routes isolated from stateless admin authentication routes.
- Prefer Laravel defaults before creating custom abstractions.
- Use `Laravel Sanctum` as the authentication mechanism for admin API tokens.
- Do not introduce `spatie/laravel-permission`, Fortify, Jetstream, or other broader auth stacks in this phase.
- Do not create a generic `Services` directory.
- Keep controllers thin and focused on HTTP orchestration.
- Put business flow in Actions and reusable domain rules in clearly named domain services only when truly needed.
- Keep Eloquent models focused on persistence, relationships, casts, and simple scopes.
- Use Queries or Repositories only when complexity justifies them.
- Validation belongs to the entry layer or input DTO boundary.
- Authorization belongs to middleware, Policies, or Gates at the entry layer.
- Authentication and authorization failures for admin routes must be rendered as consistent JSON `401` and `403` responses from the API exception entrypoint.
- Return consistent JSON payloads for success, validation failures, authentication failures, authorization failures, and expected domain failures.
- Use English as the default language and keep `pt_BR` ready.
- Add or update tests for every delivered capability.
- Run only the validations relevant to the task block before marking it complete.
- Do not introduce `category` in this phase. The admin catalog remains limited to `game`, `rarity`, and `product`.

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
  - `composer.lock`
  - `bootstrap/app.php`
  - `config/*.php`
  - `routes/*.php`
  - top-level environment files
- Prefer creating independent module files over editing shared framework files when possible.
- If two tasks need the same shared file, the shared-file task must land first.
- Authentication baseline changes must land before admin catalog endpoint work starts.

---

## Standard Validation Commands

Adjust only if the repository tooling changes.

Run the standard validations through the app container when it is available:

- `docker exec ecommerce-app-1 php artisan test`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 php artisan test --filter=Api`
- `docker exec ecommerce-app-1 php artisan migrate --pretend`
- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan config:clear`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

If Pint is not used:

- `docker exec ecommerce-app-1 vendor/bin/phpcs`

---

## Delivery Waves

The admin backend expansion should be executed in waves. Tasks in the same wave may run in parallel when their dependencies allow it.

### Wave A1 - Admin Authentication Foundation

Add the minimum admin identity and security boundary required to expose protected admin APIs safely.

### Wave A2 - Admin Catalog Management API

Expose admin-only CRUD APIs for the MVP catalog entities while preserving the public catalog behavior.

### Wave A3 - Admin Orders Read API

Expose admin-only read APIs for created orders and buyer contact data required for manual fulfillment.

---

## Task Index

### Wave A1

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| A10 | Admin Identity Model And Persistence | T23, T31 | todo |
| A11 | Admin API Authentication Baseline | A10 | todo |
| A12 | Admin Authorization Boundary | A11 | todo |

### Wave A2

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| A20 | Admin Games API | A12 | todo |
| A21 | Admin Rarities API | A12 | todo |
| A22 | Admin Products API | A12, A20, A21 | todo |
| A23 | Admin Catalog API Hardening And Repository-Level Verification | A20, A21, A22 | todo |

### Wave A3

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| A30 | Admin Orders Read API | A12 | todo |
| A31 | Admin Order Detail And Buyer Contact API | A30 | todo |
| A32 | Admin Orders API Hardening And Repository-Level Verification | A30, A31 | todo |

---

## Detailed Tasks

## A10 - Admin Identity Model And Persistence

### Goal

Create the minimum user identity model required for admin authentication without expanding into a full customer-account system.

### Write Scope

- `database/migrations/**`
- `app/Models/User.php`
- `database/factories/UserFactory.php`
- `database/seeders/**`
- auth-related documentation if needed

### Deliverables

- `users` supports `username`
- `users` supports a simple role marker with `admin` and `customer`
- `username` is unique
- existing user persistence stays compatible
- factories support creating admin and customer users explicitly
- a clear bootstrap path exists for provisioning the first admin user

### Acceptance Criteria

- the repository can persist admin users and non-admin users distinctly
- user identity remains simple and compatible with Laravel defaults
- no public registration flow is introduced
- no role-permission package is introduced

### Out Of Scope

- customer self-registration
- password reset flows
- email verification flows
- fine-grained permission matrices

### Validation

- `docker exec ecommerce-app-1 php artisan migrate --pretend`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`

---

## A11 - Sanctum API Authentication Baseline

### Goal

Add the minimum official Laravel token-based authentication flow required for admin API access.

### Write Scope

- `composer.json`
- `composer.lock`
- `config/**`
- `bootstrap/app.php`
- auth-related controllers, requests, and routes
- `app/Models/User.php`
- auth-related tests

### Deliverables

- `Laravel Sanctum` is installed and configured for this repository
- admin login endpoint exists at `POST /api/admin/auth/login`
- admin logout endpoint exists at `POST /api/admin/auth/logout`
- admin self endpoint exists at `GET /api/admin/me`
- login accepts `username` or `email` plus `password`
- login issues a plaintext API token tied to a device name
- login is rate-limited
- logout revokes the current token only

### Acceptance Criteria

- admin can authenticate using `username`
- admin can authenticate using `email`
- invalid credentials are rejected with stable JSON
- non-admin users cannot receive an admin session/token from the admin login flow
- authentication behavior remains isolated from the public session cart flow

### Out Of Scope

- browser session login for admin
- OAuth or social login
- multi-factor authentication
- refresh-token architecture

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan config:clear`

---

## A12 - Admin Authorization Boundary

### Goal

Ensure no customer or anonymous client can access admin endpoints.

### Write Scope

- middleware registration files
- admin middleware classes
- API exception rendering at the application entrypoint or API base controller handling
- admin route groups
- authorization tests

### Deliverables

- admin API routes are grouped under `/api/admin`
- admin routes require `auth:sanctum`
- admin routes require an explicit admin-only authorization boundary
- authentication and authorization failures are normalized at the API exception entrypoint
- unauthenticated requests return consistent JSON `401`
- authenticated non-admin requests return consistent JSON `403`
- the security boundary is enforced before controller business logic executes

### Acceptance Criteria

- anonymous callers cannot access admin routes
- authenticated customers cannot access admin routes
- authenticated admins can reach protected admin routes
- error payloads are consistent with the project API style

### Out Of Scope

- granular permission levels inside admin
- audit logs for admin activity
- IP allowlists or advanced operational security policies

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 php artisan route:list`

---

## A20 - Admin Games API

### Goal

Expose admin-only CRUD endpoints for games with clear validation and deletion rules.

### Write Scope

- `app/Modules/Catalog/**` for game-specific actions or queries
- admin API controllers and requests
- admin API routes
- language files when required
- tests for game admin endpoints

### Deliverables

- admin can list games
- admin can create games
- admin can update games
- admin can delete games
- deletion is blocked when products still reference the game
- game validation uses the entry layer and stable JSON responses

### Acceptance Criteria

- game CRUD is available only to admins
- names are validated appropriately
- delete protection prevents catalog inconsistency when related products exist
- public catalog behavior remains unchanged

### Out Of Scope

- category management
- bulk import
- bulk delete
- pagination only if the current repository needs it immediately

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=AdminGames`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`

---

## A21 - Admin Rarities API

### Goal

Expose admin-only CRUD endpoints for rarities with clear validation and deletion rules.

### Write Scope

- `app/Modules/Catalog/**` for rarity-specific actions or queries
- admin API controllers and requests
- admin API routes
- language files when required
- tests for rarity admin endpoints

### Deliverables

- admin can list rarities
- admin can create rarities
- admin can update rarities
- admin can delete rarities
- deletion is blocked when products still reference the rarity
- rarity validation uses the entry layer and stable JSON responses

### Acceptance Criteria

- rarity CRUD is available only to admins
- names are validated appropriately
- delete protection prevents catalog inconsistency when related products exist
- public catalog behavior remains unchanged

### Out Of Scope

- category management
- bulk import
- bulk delete
- speculative rarity metadata beyond current MVP needs

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=AdminRarities`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`

---

## A22 - Admin Products API

### Goal

Expose admin-only CRUD endpoints for products while preserving the MVP catalog model of `game` + `rarity`.

### Write Scope

- `app/Modules/Catalog/**` for product actions or queries
- admin API controllers and requests
- admin API routes
- language files when required
- tests for product admin endpoints

### Deliverables

- admin can list products
- admin can view a single product
- admin can create products
- admin can update products
- admin can delete products using the repository soft-delete pattern
- admin list responses can include unavailable or zero-quantity products
- product validation covers `price`, `quantity`, `game_id`, and `rarity_id`
- existing product actions are reused where practical instead of duplicated

### Acceptance Criteria

- product CRUD is available only to admins
- invalid game or rarity references are rejected cleanly
- negative price or quantity values are rejected cleanly
- admin list behavior is distinct from the public catalog listing when availability differs
- public catalog listing continues to expose only available products

### Out Of Scope

- category support
- advanced media management
- inventory reservation
- discounting or pricing-rule engines

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=AdminProducts`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 php artisan test --filter=Api`

---

## A23 - Admin API Hardening And Repository-Level Verification

### Goal

Review the full admin backend flow, close consistency gaps, and validate that the new admin work does not regress the public MVP.

### Write Scope

- shared API exception handling
- localization files
- tests
- documentation updates related to the delivered admin backend

### Deliverables

- authentication, authorization, validation, and domain-error payloads are consistent
- admin and public API boundaries are clearly separated
- task documentation reflects the implemented backend admin scope
- repository-level validation has been run for impacted paths

### Acceptance Criteria

- admin feature tests cover success, validation, `401`, and `403` cases
- public API tests remain green
- route registration is explicit and readable
- migration plan is coherent and reviewable
- code style checks pass

### Out Of Scope

- admin frontend implementation
- order-management admin screens
- fulfillment workflow redesign
- payment implementation

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 php artisan test`
- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan migrate --pretend`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## A30 - Admin Orders Read API

### Goal

Expose admin-only read endpoints for created orders required by the MVP manual fulfillment flow.

### Write Scope

- `app/Modules/Orders/**` for order read queries or supporting actions when needed
- admin API controllers and requests
- admin API routes
- language files when required
- tests for admin order endpoints

### Deliverables

- admin can list created orders
- admin can view a single order
- admin order responses return the data required for manual fulfillment
- order read logic is kept out of controllers when it becomes non-trivial

### Acceptance Criteria

- order read endpoints are available only to admins
- the admin order listing does not change public order creation behavior
- responses are stable and aligned with the project API style

### Out Of Scope

- order status workflow redesign
- fulfillment automation
- payment capture or payment reconciliation
- admin write operations for orders in this phase

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=AdminOrders`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`

---

## A31 - Admin Buyer Contact Visibility And Order Detail API

### Goal

Ensure admins can safely view buyer contact data attached to orders for manual fulfillment.

### Write Scope

- `app/Modules/Orders/**`
- admin API controllers and requests
- admin API routes
- language files when required
- tests for order detail and buyer contact visibility

### Deliverables

- admin can view buyer `email`
- admin can view buyer `whatsapp`
- buyer contact data is exposed only inside protected admin order endpoints
- order detail responses remain consistent with the project API style

### Acceptance Criteria

- buyer contact data is not exposed through public API routes
- authenticated admins can view the buyer contact data required by the MVP
- anonymous and non-admin callers cannot access buyer contact data

### Out Of Scope

- customer self-service order lookup
- expanded checkout profile data
- masking rules beyond the current MVP needs

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=AdminOrders`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`

---

## A32 - Admin Orders API Hardening And Repository-Level Verification

### Goal

Review the admin order read flow, close consistency gaps, and validate that buyer contact visibility stays inside the protected admin boundary.

### Write Scope

- shared API exception handling
- localization files
- tests
- documentation updates related to delivered admin order scope

### Deliverables

- order read, buyer contact visibility, authentication, and authorization payloads are consistent
- admin order routes are clearly separated from public routes
- task documentation reflects the implemented admin order scope
- repository-level validation has been run for impacted paths

### Acceptance Criteria

- admin order feature tests cover success, validation when applicable, `401`, and `403` cases
- public API routes do not expose buyer contact data
- route registration remains explicit and readable

### Out Of Scope

- admin order editing
- refund flows
- payment-provider backoffice operations

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=AdminOrders`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 php artisan test`
- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`
