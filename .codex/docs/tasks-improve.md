# Tasks - Backend Quality Improvement Plan

## Purpose

This file is the executable improvement plan for backend quality refinements identified during the project-aware code review.

It is written to support agentic execution with Codex or multiple parallel agents. Each task block is designed to be:

- small enough to review
- explicit about scope and ownership
- safe to execute independently when dependencies are satisfied
- aligned with `project.md`, `decisions.md`, `tasks.md`, and `tasks-admin.md`

This plan focuses on targeted maintainability improvements, not feature expansion.

It must preserve the current MVP behavior, domain boundaries, public API flows, admin API flows, and manual fulfillment model unless a task explicitly updates the documented API error contract to match `decisions.md`.

---

## Canonical Inputs

This file must be executed together with the following source documents:

- `src/.codex/docs/project.md`
- `src/.codex/docs/decisions.md`
- `src/.codex/docs/tasks.md`
- `src/.codex/docs/tasks-admin.md`

This file is also based on the latest backend code review observations:

- product image handling is making the admin product controller too large
- API error responses do not yet match the RFC 9457 contract defined in `decisions.md`
- public catalog reads mix product search, filter metadata, and response serialization in the controller
- product, order, and cart response serialization is repeated in controllers
- small Cart and Catalog guard or lookup duplication should be rechecked before extracting
- empty module folders should be removed or justified

Official Laravel references for this improvement wave:

- Error handling and centralized exception rendering: https://laravel.com/docs/13.x/errors
- API Resources as a response transformation layer: https://laravel.com/docs/13.x/eloquent-resources
- File storage and uploaded file handling: https://laravel.com/docs/master/filesystem
- Form Request unknown-field hardening: https://laravel.com/docs/13.x/validation

If any conflict appears:

1. architecture and technical decisions win over convenience
2. MVP scope wins over speculative future needs
3. existing tested behavior wins unless a task explicitly updates a documented contract
4. this file must be updated before expanding scope

---

## Execution Rules

- Keep scope limited to the current task block.
- Reanalyze the current code before editing; do not assume the review findings are still complete.
- When Docker containers are running, execute Laravel and PHP project commands through the app container instead of the raw host shell.
- Treat `ecommerce-app-1` as the default command target for `php artisan`, PHPUnit, and Pint commands unless the environment changes.
- Prefer Laravel defaults before creating custom abstractions.
- Do not create a generic `Services`, `Helpers`, `Common`, `Shared`, or `Manager` layer.
- Keep controllers thin and focused on HTTP orchestration.
- Keep business flow in Actions.
- Put reusable domain rules in clearly named domain services only when truly needed.
- Use Laravel API Resources or small focused transformers only when they reduce concrete duplication.
- Keep Eloquent models focused on persistence, relationships, casts, and simple scopes.
- Preserve the MVP catalog model of `game`, `rarity`, and `product`.
- Do not introduce `category`, payment processing, checkout expansion, stock reservation, automated fulfillment, or broad infrastructure patterns.
- Add or update tests for every implemented behavior change.
- Run only the validations relevant to the task block before marking it complete.
- Run full repository validation before marking the improvement wave complete.

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
  - `bootstrap/app.php`
  - `app/Http/Controllers/Api/ApiController.php`
  - `routes/*.php`
  - localization files shared across many endpoints
  - `.codex/docs/collection/ecommerce.postman_collection.json`
- Prefer creating focused module files over repeatedly growing controllers.
- If two tasks need the same shared file, the shared-file task must land first.
- Error-contract work must land before response resource cleanup when both are being executed in the same improvement wave.
- Documentation and Postman examples must be updated only when the implemented API contract actually changes.

---

## Standard Validation Commands

Adjust only if the repository tooling changes.

Run the standard validations through the app container when it is available:

- `docker exec ecommerce-app-1 php artisan test`
- `docker exec ecommerce-app-1 php artisan test --filter=Api`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 php artisan test --filter=AdminProducts`
- `docker exec ecommerce-app-1 php artisan test --filter=Catalog`
- `docker exec ecommerce-app-1 php artisan test --filter=Cart`
- `docker exec ecommerce-app-1 php artisan test --filter=ArchitectureBaseline`
- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan config:clear`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

If Pint is not used:

- `docker exec ecommerce-app-1 vendor/bin/phpcs`

---

## Delivery Waves

The improvement work should be executed in focused waves. Tasks in the same wave may run in parallel only when their write scopes do not overlap.

### Wave I1 - API Contract And Error Handling

Align expected API errors with the RFC 9457 contract already documented in `decisions.md`.

### Wave I2 - Product Admin Controller Slimming

Move product image storage and cleanup details out of the admin product controller without adding a generic service layer.

### Wave I3 - Response Transformation And Read Cohesion

Reduce repeated controller serialization and keep public catalog read concerns cohesive.

### Wave I4 - Small Duplication And Structure Cleanup

Reassess small duplication after the larger cleanup tasks and remove or justify speculative empty module folders.

### Wave I5 - Final Quality Verification

Validate that the improvement wave reduced review findings without changing MVP scope.

---

## Task Index

### Wave I1

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| I10 | RFC 9457 API Error Contract Alignment | A32 | todo |

### Wave I2

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| I20 | Product Image Storage Boundary | I10 if error contract lands first; otherwise A32 | todo |

### Wave I3

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| I30 | Focused API Resource Extraction | I10 | todo |
| I31 | Public Catalog Metadata Query Boundary | I30 | todo |

### Wave I4

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| I40 | Reassess Catalog And Cart Duplication | I20, I30 | todo |
| I41 | Empty Module Directory Cleanup | I40 | todo |

### Wave I5

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| I50 | Improvement Wave Repository Verification | I10, I20, I30, I31, I40, I41 | todo |

---

## Detailed Tasks

## I10 - RFC 9457 API Error Contract Alignment

### Goal

Move expected API and domain exception rendering out of `ApiController::respond()` and into Laravel's centralized exception rendering path.

### Write Scope

- `bootstrap/app.php`
- API response or error support classes if needed
- `app/Http/Controllers/Api/ApiController.php`
- API feature tests
- localization files when error messages or titles need stable keys
- `.codex/docs/collection/ecommerce.postman_collection.json` if response examples are stored there

### Deliverables

- expected API errors render as RFC 9457-style problem details
- error responses use `application/problem+json`
- validation, authentication, authorization, not found, and expected domain failures have stable problem payloads
- domain exceptions are mapped centrally instead of through per-controller wrapper callbacks
- success responses remain in the existing project success envelope
- tests assert the new error shape and content type

### Acceptance Criteria

- controllers no longer need `respond()` wrapping just to translate known domain errors
- Actions still throw explicit domain exceptions and do not build HTTP responses
- success response behavior remains stable
- API and admin tests are updated to assert the documented error contract
- no business rule changes are introduced

### Out Of Scope

- JSON:API migration
- frontend changes
- changing business rules
- changing successful response field names
- replacing all exception classes

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Api`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 php artisan test`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## I20 - Product Image Storage Boundary

### Goal

Remove product image storage and cleanup details from `App\Http\Controllers\Api\Admin\ProductController` without introducing a generic service layer.

### Write Scope

- `app/Modules/Catalog/**`
- `app/Http/Controllers/Api/Admin/ProductController.php`
- product admin tests
- localization files if storage failure messages need adjustment

### Deliverables

- a small, explicitly named Catalog/Admin image storage collaborator exists
- the collaborator stores product images using Laravel's filesystem abstraction
- the collaborator resolves public image URLs
- the collaborator deletes replaced images safely
- create and update flows clean up newly stored images when persistence fails after upload
- existing product create and update behavior remains unchanged from the API client's perspective

### Acceptance Criteria

- `ProductController` is responsible only for HTTP orchestration, DTO mapping, Action invocation, and response creation
- product image storage paths remain constrained to the product image directory
- replacing an image deletes only owned product image files
- failed create or update paths do not leave newly uploaded product images behind
- tests cover successful upload, replacement cleanup, and failure cleanup

### Out Of Scope

- multi-image gallery
- external media service
- image optimization pipeline
- CDN abstraction
- changing stored image URL shape unless required for a safe bug fix

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=AdminProducts`
- `docker exec ecommerce-app-1 php artisan test --filter=Catalog`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## I30 - Focused API Resource Extraction

### Goal

Reduce payload-building duplication in controllers using Laravel API Resources or small focused response transformers.

### Write Scope

- API resources or focused transformer classes
- affected API controllers
- API feature tests
- admin product tests when product payloads move

### Deliverables

- repeated product payload mapping is moved out of controllers
- order and cart payload mapping are rechecked and extracted only when the result is clearer
- resources or transformers keep the existing project success envelope intact
- no JSON:API response format is introduced
- response shapes remain compatible except for error payloads intentionally changed by `I10`

### Acceptance Criteria

- controllers stop owning repeated model-to-array mapping for product responses
- new resources or transformers are named by concrete response purpose
- no broad serialization framework is introduced
- no successful response field names change unintentionally
- tests prove public and admin payload compatibility

### Out Of Scope

- full API versioning
- JSON:API migration
- broad serialization framework
- changing field names
- pagination or sparse fieldsets unless already needed by the current endpoint

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Api`
- `docker exec ecommerce-app-1 php artisan test --filter=AdminProducts`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## I31 - Public Catalog Metadata Query Boundary

### Goal

Move public catalog filter metadata reads out of `CatalogProductController` when doing so makes the controller thinner without adding unnecessary layers.

### Write Scope

- `app/Modules/Catalog/Queries/**`
- `app/Http/Controllers/Api/CatalogProductController.php`
- catalog API tests

### Deliverables

- public catalog product search remains delegated to Catalog query classes
- public catalog filter metadata is delegated to a focused query or read object
- the implementer may use a combined public catalog read object only if reanalysis shows it is simpler than separate queries
- controller no longer performs direct `Game` and `Rarity` metadata queries
- public catalog continues exposing games, rarities, and available products only

### Acceptance Criteria

- controller delegates product search and metadata reads
- public catalog behavior remains unchanged
- no category support is introduced
- no advanced search or sorting is introduced unless already present
- tests prove available product filtering and metadata response shape

### Out Of Scope

- pagination unless needed immediately
- category support
- advanced search
- recommendation logic
- admin catalog read changes

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=CatalogApi`
- `docker exec ecommerce-app-1 php artisan test --filter=Api`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## I40 - Reassess Catalog And Cart Duplication

### Goal

Re-check duplicated guards and product lookup logic after earlier refactors, then extract only duplication that has clear local value.

### Write Scope

- `app/Modules/Catalog/**`
- `app/Modules/Cart/**`
- related Catalog and Cart tests

### Deliverables

- duplicated Catalog product validation and reference checks are reanalyzed
- duplicated Cart product lookup and quantity guard logic are reanalyzed
- a minimal extraction is implemented only when it improves clarity
- if extraction is not clearer, the task result explicitly records that the current duplication is preferable
- no generic abstraction or repository-everywhere pattern is introduced

### Acceptance Criteria

- behavior and tests remain stable
- extracted code, if any, has a concrete domain name and one clear responsibility
- no `Services`, `Helpers`, `Manager`, generic repository, or shared utility bucket is introduced
- current MVP stock and cart behavior remains unchanged

### Out Of Scope

- broad domain service layer
- stock reservation redesign
- checkout redesign
- changing cart persistence strategy
- changing order creation stock decrement behavior

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=Cart`
- `docker exec ecommerce-app-1 php artisan test --filter=Catalog`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## I41 - Empty Module Directory Cleanup

### Goal

Remove or justify module folders that contain only `.gitkeep`, aligning the repository with the decision that modules should contain only folders they actually need.

### Write Scope

- `app/Modules/**/.gitkeep`
- architecture baseline tests if they currently require unused folders
- documentation notes only when an intentionally empty directory must remain

### Deliverables

- empty speculative module folders are removed
- architecture baseline tests reflect actual module structure instead of boilerplate
- any intentionally retained empty folder has a clear current reason
- no implemented class is moved between domains

### Acceptance Criteria

- module structure remains clear and cohesive
- no required namespace disappears
- no future-only folder is kept without justification
- architecture tests pass after cleanup

### Out Of Scope

- moving implemented classes between domains
- renaming modules
- adding new module layers
- changing autoload configuration unless a test proves it is required

### Validation

- `docker exec ecommerce-app-1 php artisan test --filter=ArchitectureBaseline`
- `docker exec ecommerce-app-1 php artisan test`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## I50 - Improvement Wave Repository Verification

### Goal

Verify that the improvement wave reduced the review findings without changing MVP scope.

### Write Scope

- tests
- documentation notes
- `.codex/docs/collection/ecommerce.postman_collection.json` only if API error examples changed

### Deliverables

- all relevant tests pass
- route list remains coherent
- API error and success contracts are documented through tests and examples where applicable
- no payment, category, checkout expansion, stock automation, or fulfillment automation scope creep is introduced
- reviewed controllers are thinner in the hotspots identified by the code review
- no new overengineered abstraction layer exists

### Acceptance Criteria

- full test suite passes
- Pint passes
- route list can be generated successfully
- config can be cleared successfully
- the final review confirms the original findings are either resolved or intentionally deferred with rationale

### Out Of Scope

- frontend implementation
- new product features
- payment gateway work
- automated fulfillment
- customer account expansion

### Validation

- `docker exec ecommerce-app-1 php artisan test`
- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan config:clear`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

---

## Definition Of Done

The improvement wave is done when:

- every completed task has run its listed validation commands
- full repository tests pass
- Pint passes
- API contract changes are reflected in tests and examples when applicable
- controllers are thinner in the reviewed hotspots
- new abstractions are focused, named by domain purpose, and justified by current code
- no MVP scope expansion was introduced
- any deferred review finding has a clear reason documented in the task result

---

## Explicitly Out Of Scope

Do not implement the following as part of this improvement plan:

- payment gateway integration
- payment capture, refund, or reconciliation flows
- automated in-game item delivery
- stock reservation redesign
- advanced stock automation
- category support
- checkout fields beyond `email` and `whatsapp`
- customer account or order lookup expansion
- frontend implementation
- generic service, helper, manager, shared, or repository-everywhere architecture
- JSON:API migration
- broad API versioning
