# Technical Decisions

## Purpose

This document defines the non-negotiable technical decisions and implementation rules for the project.

Its goal is to keep the codebase consistent, readable, testable, and easy to evolve while preserving delivery speed.

This document is the main technical source of truth for the project and absorbs the key rules that would otherwise be spread across multiple smaller standards documents.

---

## 1. Architecture Decision

The project follows a **Modular Monolith by Domain with Actions (Use Cases)**.

It is not pure MVC and it is not a full Hexagonal Architecture.

The application is organized by business domains such as:

- Catalog
- Cart
- Orders
- Payments
- Admin

### Core Rule

Business logic must be organized around explicit use cases.

- Controllers and Livewire components stay thin
- Actions orchestrate use cases
- DTOs carry structured application data
- Models handle persistence, relationships, casts, and simple scopes
- DomainServices hold reusable domain rules only when truly needed
- External integrations are isolated behind Gateways / Clients / Adaptors
- Asynchronous side effects are handled with Events / Jobs when appropriate

### Module Boundaries

- Modules must be cohesive and isolated
- Circular dependencies between modules are not allowed
- Cross-module access must happen through explicit Actions, domain contracts, or intentionally exposed module services
- Arbitrary deep coupling to internal classes from another module is not allowed

---

## 2. Framework Philosophy

The project must stay **close to Laravel defaults** whenever possible.

Custom abstractions should be introduced only when there is a clear and repeated need.

### Rules

- Prefer Laravel built-in features before creating custom infrastructure
- Prefer Form Requests or Livewire validation for input validation
- Prefer Policies or Gates for authorization
- Prefer Laravel queues, events, logging, localization, resources, and testing tools before adding external layers
- Do not introduce architectural complexity too early

---

## 3. Namespace And Module Structure

All domain modules must live under:

- `App\Modules\`

### Namespace Pattern

Use the following general pattern:

- `App\Modules\{Module}\{Layer}\ClassName`

Examples:

- `App\Modules\Catalog\Actions\CreateProductAction`
- `App\Modules\Catalog\DTOs\CreateProductData`
- `App\Modules\Orders\Queries\ListAdminOrdersQuery`
- `App\Modules\Orders\Repositories\OrderRepository`
- `App\Modules\Catalog\DomainServices\ReserveProductStockService`

### Module Structure Rule

A module may contain only the folders it actually needs. It must not be forced to include boilerplate directories with no clear responsibility.

Possible module folders include:

- `Actions`
- `Models`
- `DTOs`
- `Queries`
- `Repositories`
- `Policies`
- `DomainServices`
- `Events`
- `Listeners`
- `Jobs`
- `Gateways`

### Structure Rules

- Organize by domain and responsibility, not by vague technical dumping grounds
- Do not create a generic root `Services` folder
- Do not create vague folders such as `Helpers`, `Common`, or `Shared` as default destinations
- Controllers remain part of the entry layer and must not become business-layer classes

---

## 4. Actions

Actions are the main application use-case boundary.

### Responsibilities

Actions should:

- represent a specific business use case
- orchestrate the flow of a use case
- coordinate models, DTOs, repositories, queries, domain services, and events when needed
- keep intent explicit through naming

Examples:

- `CreateProductAction`
- `UpdateProductAction`
- `CreateOrderAction`
- `CapturePaymentAction`

### Actions Must Not

- handle raw HTTP concerns directly
- receive the full Request object as business input
- return HTTP response objects
- become generic utility classes

### Input Rule

For non-trivial write operations, Actions should receive DTOs by default.

Very small and simple Actions may receive scalar arguments only when a DTO would add no real value.

---

## 5. DTO Usage

The project standardizes on **custom project-owned DTO classes**.

No DTO package is required at this stage.

### DTOs Should Be Used When

- an Action receives structured business input
- creating or updating aggregate-like entities such as `Product`
- input needs stronger typed grouping than a raw array
- data crosses the boundary between the entry layer and the application/domain layer

Examples:

- `CreateProductData`
- `UpdateProductData`
- `CreateOrderData`

### DTO Rules

- DTOs must be framework-agnostic
- DTOs must not receive the Request object
- DTOs must not perform persistence
- DTOs must not return HTTP responses
- DTOs should be explicit, typed, and small enough to reflect a real use case

### Construction Flow

- validation happens first in the entry layer
- validated input is mapped into a DTO
- the Action receives the DTO

---

## 6. Models, Queries, And Repositories

### Eloquent Default

Eloquent is the default data access mechanism for the project.

Simple CRUD and straightforward reads may be handled directly inside Actions using Eloquent.

Examples of acceptable direct Eloquent usage inside an Action:

- `create`
- `update`
- `delete`
- `findOrFail`
- simple relationships
- simple scopes

### Query Rule

Complex read logic must not be written inline repeatedly in controllers or Actions.

Use these layers in order of simplicity:

1. Eloquent direct read for simple cases
2. Eloquent scopes for reusable simple query fragments
3. Query objects in `Queries/` for non-trivial read use cases

### Query Objects

Query objects are responsible for read use cases only.

They should be used for scenarios such as:

- filtering
- sorting
- pagination
- admin listings
- reporting-style reads
- reads that would otherwise become long query chains repeated across the codebase

Query objects must:

- represent a read use case
- encapsulate read composition clearly
- return collections, models, or paginators appropriate to the scenario
- never mutate state

Examples:

- `ListAdminOrdersQuery`
- `SearchCatalogProductsQuery`

### Repository Rule

Repositories are **selective**, not mandatory.

Do not wrap every model with a repository by default.

Use a Repository when:

- persistence or retrieval logic stops being simple
- the same non-trivial access pattern is reused across multiple Actions
- an Action would otherwise carry too much data-access orchestration
- custom loading or persistence rules need one explicit home

Examples:

- `OrderRepository` may be introduced if order loading and persistence become non-trivial across multiple flows

### Repository Rules

- Repositories must have explicit domain names
- Repositories must not become generic base abstractions for the whole application
- Repositories do not replace Query objects for read-focused use cases automatically
- If a simple Action can remain readable with direct Eloquent usage, keep it simple

---

## 7. DomainServices

DomainServices are **strictly limited reusable domain rules**.

They are not the default destination for business logic.

### Use DomainServices Only When

- the same domain logic is used by multiple Actions
- the logic belongs to the domain, not to HTTP or infrastructure
- leaving the logic inside one Action would create duplication or obscure intent

Examples:

- `ReserveProductStockService`
- `CalculateOrderTotalsService`

### DomainServices Must Not

- become generic service buckets
- handle request validation
- return HTTP responses
- replace Actions as the main use-case boundary
- become a dumping ground for “logic extracted from controllers”

### Guiding Principle

Extracting everything to service classes is not the goal.

Use a DomainService only when it improves clarity and reuse at the domain level.

---

## 8. Validation And Authorization

### Validation

- Validation happens at the entry layer
- Prefer Form Requests for HTTP endpoints
- Prefer Livewire validation for Livewire interactions
- Actions receive validated data, typically via DTOs

### Authorization

- Authorization happens at the entry layer
- Prefer Policies or Gates
- Actions should assume authorization has already been checked unless the use case requires an additional explicit domain guard

---

## 9. API Response And Error Standard

The API must use a consistent response format.

### Success Responses

For non-`204` success responses, use:

```json
{
  "data": {},
  "message": "optional",
  "meta": {}
}
```

Rules:

- `data` is required for non-`204` success responses
- `message` is optional
- `meta` is optional
- `204 No Content` must not include a response body

Example:

```json
{
  "data": {
    "id": 123,
    "name": "Dragonclaw Hook",
    "price": 159900,
    "game": "Dota 2",
    "rarity": "Immortal"
  },
  "meta": {
    "request_id": "uuid",
    "timestamp": "2026-04-16T14:30:00Z"
  }
}
```

### Error Responses

Errors must follow **RFC 9457 problem details** with content type:

- `application/problem+json`

Standard shape:

```json
{
  "type": "/problems/resource-not-found",
  "title": "Resource not found",
  "status": 404,
  "detail": "Order not found.",
  "code": "ORDER_NOT_FOUND",
  "instance": "/api/orders/999",
  "errors": {},
  "meta": {}
}
```

Rules:

- `type`, `title`, `status`, and `detail` follow the RFC structure
- `code`, `errors`, and `meta` are project-specific extensions
- `type` must use a stable URI or stable project path format
- `instance` should map to the request path when available
- validation errors may include `errors`
- `meta` should only be included when it carries useful metadata

### Error Handling Rules

- Errors must be rendered centrally through the exception handler
- Actions must not build HTTP error payloads directly
- Controllers stay thin and only return standardized success responses
- Laravel API Resources or a thin response transformer layer should be used for success payload transformation when helpful

### HTTP Status Usage

- `200` for success
- `201` for created resources
- `204` for successful deletes with no body
- `400` for malformed requests when applicable
- `401` for unauthorized
- `403` for forbidden
- `404` for not found
- `409` for business-rule conflicts
- `422` for validation errors
- `500` for unexpected internal errors

### Avoid

- different success formats per endpoint
- `success: true/false` payloads
- raw exception output
- business logic in controllers
- unnecessary JSON:API-level complexity

---

## 10. Exceptions And Logging

### Exceptions

- Use clear custom exceptions when they improve readability and handling
- Avoid generic exceptions for known business conditions
- Prefer explicit business exception names

### Logging

- Logging must be useful for debugging and operations
- Sensitive data must not be logged
- Logs should support tracing failure points in Actions, Jobs, and integrations

---

## 11. Language And Localization

- Default language: English
- Support: `pt_BR`
- Do not hardcode user-facing text when localization is expected
- Validation and API messages should follow a predictable localization path

---

## 12. Testing Strategy

The project uses **PHPUnit**.

The testing strategy must stay simple, fast, and behavior-focused.

### Core Principles

- Test behavior over implementation details
- Prefer simple tests with clear assertions
- Avoid over-mocking
- Every implemented feature must have meaningful test coverage

### Required Test Layers

#### Action Tests

Action tests are required.

They should cover:

- the main happy path
- the most important failure path or business-rule path

They should avoid:

- HTTP concerns
- unnecessary framework-level assertions

#### Feature Tests

Feature or API tests are required for exposed HTTP flows.

They should assert:

- status code
- response shape
- validation behavior
- authorization behavior

#### Query Tests

Query tests are required only for non-trivial Query objects.

They should assert:

- filters
- sorting
- pagination when applicable

#### Integration Tests

Integration tests are optional and should focus on true external boundaries such as:

- payment gateways
- third-party APIs
- external messaging channels

### Testing Rules

- Prefer factories for test setup
- Prefer database-backed tests when behavior depends on persistence
- Mock only true external boundaries
- Do not mock Laravel internals unnecessarily
- Avoid large and slow tests without clear value
- Tests without meaningful assertions are not acceptable

### Minimum Rule

For each significant feature or Action, implement at least:

- one happy-path test
- one important failure, validation, or business-rule test

---

## 13. PHP And Coding Standards

- Follow PSR-12
- Enforce style with Laravel Pint
- Prefer early returns over unnecessary `else`
- Avoid duplication when it reduces clarity and maintainability risk
- Keep methods focused and reasonably small
- Prefer explicit names over short unclear names

---

## 14. Patterns Allowed In This Project

These patterns are allowed when they have a clear responsibility:

- Action
- DTO
- Query Object
- Repository
- DomainService
- Strategy
- Factory
- Observer / Events
- Gateway

These patterns are not mandatory everywhere.

Use them only when they improve clarity, consistency, or reuse.

---

## 15. What To Avoid

- fat controllers
- business logic in models
- generic `Services` folders
- premature abstraction
- repositories for every model by default
- long repeated query chains in multiple places
- HTTP-aware business logic
- leaking framework request objects into Actions

---

## 16. Summary

- Clarity over complexity
- Simplicity first
- Laravel defaults first
- Actions are the primary use-case boundary
- DTOs are the default structured input boundary for non-trivial writes
- Query objects handle complex reads
- Repositories are selective
- DomainServices are limited to reusable domain logic
- API responses must stay consistent
- Tests must prove business behavior
