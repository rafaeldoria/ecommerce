# Frontend Technical Decisions

## Purpose

This document defines the non-negotiable frontend implementation decisions for the project.

Its goal is to keep the UI architecture consistent, maintainable, localization-ready, and visually intentional while staying compatible with the existing backend-first MVP.

This file is the frontend source of truth for:

- rendering approach
- UI composition
- route boundaries
- state ownership
- localization discipline
- responsive behavior
- accessibility baseline
- storefront and admin separation

---

## 1. Frontend Architecture Decision

The frontend follows a **Laravel Blade + Livewire** approach for entry, rendering, and interactive page behavior.

This project is not a SPA in this phase.

### Core Rule

Use Blade and Livewire as the frontend delivery mechanism while preserving backend-owned business rules and clean module boundaries.

This means:

- Blade handles layout composition and reusable view structure
- Livewire handles page and interaction orchestration where interactivity is needed
- backend Actions remain the home for business use cases
- backend modules remain the home for catalog, cart, orders, payments, and admin domain rules

### This Architecture Must Not Drift Into

- a business-logic-heavy Livewire layer
- ad hoc JavaScript islands containing critical domain rules
- a pseudo-SPA without explicit architectural approval

---

## 2. Business Logic Boundary

Business rules must remain in backend Actions and approved domain boundaries.

### Rules

- Livewire components must stay thin
- Blade templates must stay presentational
- validation must remain in the approved Laravel entry boundary
- business-critical decisions must not exist only in component state or DOM behavior
- price, stock, order, cart, and admin authorization rules must not be reimplemented in the frontend

### Practical Meaning

- frontend can orchestrate user flow
- frontend can format and present state
- frontend must not become the source of truth for business behavior

---

## 3. Component And View Composition

The UI should be composed from explicit page structures and reusable presentation pieces.

### Preferred Composition Model

Use:

- route-backed page components
- Blade layouts
- Blade components
- focused view partials when appropriate

Avoid:

- duplicated page fragments spread across unrelated templates
- large monolithic templates with mixed concerns
- inline one-off UI patterns that should be reusable

### Composition Rule

Page-level intent must remain obvious.

Each page should have:

- a clear route owner
- a clear data contract
- a clear separation between page shell, page content, and reusable sections

---

## 4. Future React/Vue Readiness

The frontend must be implemented in a way that keeps a future React or Vue migration reasonably cheap.

### Readiness Rules

- do not hide business-critical behavior only in DOM interactions
- preserve stable server responses and JSON-usable behavior where appropriate
- keep catalog, cart, checkout, and admin flows aligned with backend module boundaries
- isolate presentation helpers from domain logic
- keep UI state shallow when the server is the real owner
- do not make route contracts depend on fragile template-only assumptions

### Explicit Non-Goal

This project is not implementing React or Vue now.

The requirement is only to avoid unnecessary migration pain later.

---

## 5. Route Boundary Decision

The public storefront and the admin frontend must remain distinct web surfaces.

### Public Storefront Routes

The storefront should standardize around the following route map:

- `/`
- `/catalog`
- `/products/{slug-or-id}`
- `/cart`
- `/checkout`
- `/about`
- `/contact`

The FAQ or purchase-help content may live under:

- `/faq`
- or `/help`

One of these should be chosen during implementation and used consistently.

### Admin Frontend Routes

The admin frontend should standardize around the following route map:

- `/admin/login`
- `/admin`
- `/admin/games`
- `/admin/rarities`
- `/admin/products`
- `/admin/orders`
- `/admin/orders/{id}`

### Boundary Rules

- public layout and admin layout must be separate
- public navigation must not expose admin operational links
- admin UI must not reuse storefront navigation or trust sections
- admin API stays distinct from the public session-cart behavior

---

## 6. State Ownership Decision

The cart remains **server-owned and session-backed** for the MVP.

### Rules

- the backend is the source of truth for cart state
- the frontend must treat the cart as session-dependent
- the frontend must preserve the session-driven flow instead of simulating client-owned cart truth
- checkout continues to depend on the active session cart

### Implications

- cart indicators, cart pages, and checkout entry must reflect backend state
- future token-cart or headless-cart work is deferred
- direct-buy UX must still align with session-cart behavior in the MVP

---

## 7. Localization Decision

All user-facing UI copy must be localization-ready from the start.

### Launch And Expansion

- launch language: `pt-BR`
- first additional language: `en`

### Rules

- use Laravel localization files for copy
- do not hard-code final user-facing copy directly into templates when it should be translatable
- translation keys must be organized for growth, not as one flat dumping ground

### Recommended Translation Namespaces

- `storefront.navigation`
- `storefront.home`
- `storefront.catalog`
- `storefront.product`
- `storefront.cart`
- `storefront.checkout`
- `storefront.content`
- `storefront.metadata`
- `admin.navigation`
- `admin.auth`
- `admin.dashboard`
- `admin.games`
- `admin.rarities`
- `admin.products`
- `admin.orders`
- `shared.actions`
- `shared.states`
- `shared.validation`

---

## 8. SEO And Metadata Baseline

Public storefront pages must ship with a minimum metadata baseline.

### Public Page Rules

- each public page should have a meaningful title
- public pages should have concise meta descriptions
- canonical thinking should be preserved for top-level pages
- Open Graph basics should be supported for the public website

### Admin Rule

- admin pages are excluded from SEO concerns
- admin layout must not be treated as public marketing content

### Priority

Keep metadata simple and clean in the MVP.
Do not over-engineer SEO infrastructure before the core storefront exists.

---

## 9. Accessibility Baseline

The frontend must meet a practical accessibility baseline appropriate for a modern commerce site.

### Rules

- use semantic landmarks
- preserve keyboard accessibility
- provide visible focus states
- maintain sufficient contrast on dark, non-white surfaces
- do not rely on color alone to communicate essential state
- do not create hover-only critical interactions

### Practical Targets

- nav, cart, checkout, and admin actions must be reachable by keyboard
- buttons and links must remain clear on mobile and desktop
- status and validation messaging must remain understandable

---

## 10. Responsive System

The frontend follows a **mobile-first** responsive strategy.

### Rules

- start from modern smartphone layouts
- scale upward to tablet and desktop
- keep touch targets comfortable
- keep navigation usable with one-hand interaction patterns where realistic
- avoid critical UI patterns that require precise hover behavior

### Layout Expectations

- stacks and cards should collapse cleanly on narrow widths
- catalog browsing must remain usable without desktop-only density assumptions
- cart and checkout should remain easy to complete on mobile
- admin screens should remain operational on modern phones, even if they are optimized more heavily for tablet and desktop

---

## 11. Design System Baseline

The project needs a lightweight but deliberate visual system baseline.

### Brand Direction

- primary brand colors: teal and amber
- dark non-white backgrounds
- premium store tone
- visually distinct from generic white SaaS dashboards

### Tokens To Standardize

- brand color roles
- neutral scale
- surface scale
- spacing rhythm
- radius system
- shadow language
- typography direction
- section spacing
- button hierarchy
- card treatment

### Logo Rule

- reserve a logo slot and stable logotype treatment for `GR-Shop`
- do not hard-code a final logo concept into the architecture

---

## 12. Content And Trust Rules

The storefront must build confidence without overstating current product capabilities.

### Rules

- explain the purchase process briefly and clearly
- state manual follow-up honestly
- use short trust sections instead of long corporate filler
- placeholder testimonials are allowed only as temporary internal content
- do not claim instant delivery unless backend operations truly support it
- do not imply a full integrated payment flow if it does not exist yet

### Placeholder Content Ownership

The following content must be treated as editable placeholder content during implementation:

- fake testimonials
- About text
- Contact text
- FAQ or purchase-help text

This content should live in a predictable content layer or localization structure, not be scattered as hard-coded fragments across multiple templates.

---

## 13. Admin UI Rule

The admin interface is intentionally simpler than the public storefront.

### Rules

- prioritize function over branding
- use a dedicated layout and navigation system
- keep the visual language quieter than the public website
- preserve enough consistency that the admin still feels part of the same product
- do not let storefront merchandising patterns leak into admin operations

### Admin Must Optimize For

- speed of use
- low cognitive load
- readable forms
- readable tables and lists
- fast access to orders and catalog entities

---

## 14. Testing And Documentation Rule

Every frontend delivery task must define how it will be verified.

### Required Verification Categories

- browser-flow checks
- responsive checks
- localization checks
- regression checks for cart and checkout
- admin/public boundary checks
- route verification
- Livewire component tests where appropriate

### Minimum Scenarios

- user can browse catalog and reach product detail
- user can add to cart and see backend-backed cart state reflected in UI
- user can use direct buy and reach checkout correctly
- checkout collects only email and WhatsApp for the MVP
- confirmation messaging explains manual next steps clearly
- language switching works for supported locales
- admin auth and admin screens remain isolated from public navigation

---

## 15. Implementation Discipline

The frontend should stay close to Laravel defaults when possible.

### Rules

- prefer framework-native rendering patterns before adding extra frontend tooling
- add JavaScript only when it solves a real interaction need
- do not introduce a heavy design-system abstraction layer too early
- do not build speculative frontend infrastructure for features outside MVP scope
- keep file and naming structure obvious enough that another engineer can map screens to routes quickly

---

## 16. Non-Negotiable Summary

If the implementation needs the shortest possible reading of this document, the rules are:

1. Use Blade + Livewire, not a SPA.
2. Keep business logic in backend Actions and modules.
3. Treat the cart as backend-owned session state.
4. Keep public storefront and admin frontend fully separate.
5. Build mobile-first, localization-ready, non-white UI.
6. Make React/Vue migration easier later without building it now.
