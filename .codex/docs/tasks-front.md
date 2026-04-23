# Tasks - Frontend Execution Plan

## Purpose

This file is the executable delivery plan for the frontend implementation of GR-Shop.

It is written to support agentic execution with Codex or multiple parallel agents. Each task block is designed to be:

- small enough to review
- explicit about scope and ownership
- safe to execute independently when dependencies are satisfied
- aligned with `project-front.md`, `decisions-front.md`, and the existing backend documents already present in the repository

This plan assumes a **Livewire-first frontend implementation** over the current Laravel backend.

It must preserve the current backend truth for catalog, cart, orders, and admin authentication while adding:

- a premium public storefront
- a separate admin frontend
- a localization-ready web foundation

---

## Canonical Inputs

This file must be executed together with the following source documents:

- `src/.codex/docs/project.md`
- `src/.codex/docs/decisions.md`
- `src/.codex/docs/cart.md`
- `src/.codex/docs/tasks.md`
- `src/.codex/docs/tasks-admin.md`
- `src/.codex/docs/project-front.md`
- `src/.codex/docs/decisions-front.md`

This file also assumes the repository already contains:

- backend catalog read endpoints and queries
- backend cart actions and session-backed cart behavior
- backend order creation flow with minimal buyer contact data
- admin authentication and admin catalog/order APIs

If any conflict appears:

1. documented architecture and technical decisions win over convenience
2. existing backend truth wins over speculative frontend assumptions
3. the frontend must not fake unsupported product capabilities
4. this file must be updated before scope is expanded

---

## Execution Rules

- Keep scope limited to the current task block.
- When Docker containers are running, execute Laravel and PHP project commands through the app container instead of the raw host shell.
- Treat `ecommerce-app-1` as the default command target for `php artisan`, PHPUnit, and Pint unless the environment changes.
- Keep the frontend Livewire-first and Blade-first in this phase.
- Keep business logic in backend Actions and module boundaries, not in Livewire components.
- Treat the current session-backed cart as the storefront source of truth.
- Treat product images as backend-owned assets exposed through `image_url`, initially stored through Laravel public storage.
- Keep public storefront routes and admin frontend routes separate.
- Use `pt-BR` as the primary launch locale and keep `en` ready from the beginning.
- Keep the visual base dark and non-white.
- Use teal and amber as the primary brand anchors unless a documented brand revision replaces them.
- Keep the public website visually stronger than the admin frontend.
- Use Laravel localization files for UI copy.
- Prefer reusable Blade components and partials over duplicated markup.
- Add or update tests for each delivered capability.
- Run only the validations relevant to the active task block before marking it complete.
- Do not implement unsupported payment UX or fake automation.
- Do not introduce React or Vue in this phase.

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
- If a task uncovers a cross-cutting frontend architecture change, stop and update this file before continuing.
- Shared files with high collision risk should be changed by a single coordination task:
  - `routes/web.php`
  - `resources/views/**` layout root files
  - `resources/css/app.css`
  - localization files shared across multiple screens
  - top-level frontend boot files
- Prefer creating isolated page components, views, and partials over repeatedly editing the same large template.
- If two tasks need the same shared file, the shared-file task must land first.
- Storefront shell and localization foundation should land before page-specific content tasks.
- Admin layout foundation should land before admin CRUD screen tasks.

---

## Standard Validation Commands

Adjust only if the repository tooling changes.

Run the standard validations through the app container when it is available:

- `docker exec ecommerce-app-1 php artisan test`
- `docker exec ecommerce-app-1 php artisan test --filter=Livewire`
- `docker exec ecommerce-app-1 php artisan test --filter=Frontend`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan config:clear`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

Manual verification must also be used when relevant for:

- public navigation
- cart behavior
- checkout behavior
- locale switching
- mobile responsiveness
- admin/public isolation

---

## Delivery Waves

The frontend should be executed in waves. Tasks in the same wave may run in parallel when their dependencies allow it.

### Wave F0 - Frontend Foundation

Establish route, layout, Livewire, localization, and frontend architecture baselines.

### Wave F1 - Storefront Shell And Brand System

Build the public shell, visual direction, and reusable storefront sections.

### Wave F2 - Catalog And Product Discovery

Build catalog browsing and product-detail experiences around current backend capabilities.

### Wave F3 - Cart And Checkout Experience

Build the storefront cart and checkout flows on top of the current session-backed backend behavior.

### Wave F4 - Content, Localization, And Trust

Finish supporting content, translations, metadata, and trust-building sections.

### Wave F5 - Admin Frontend

Build the separate admin web interface on top of the existing admin authentication and APIs.

### Wave F6 - Hardening, QA, And Frontend Readiness

Polish boundaries, run verification, and close the main frontend MVP gaps before implementation sign-off.

---

## Task Index

### Wave F0

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| F00 | Frontend Structure Audit And Route Baseline | A32 | todo |
| F01 | Livewire Frontend Architecture Skeleton | F00 | todo |
| F02 | Localization And Shared Copy Foundation | F00 | todo |

### Wave F1

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| F10 | Storefront Layout Shell And Navigation | F01, F02 | todo |
| F11 | Brand Tokens And Non-White Visual Language | F10 | todo |
| F12 | Home Page Hero And Trust Sections | F10, F11 | todo |

### Wave F2

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| F20 | Catalog Listing Experience | F10, F11 | todo |
| F21 | Product Detail Experience | F20 | todo |
| F22 | Storefront Discovery Hardening | F20, F21 | todo |

### Wave F3

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| F30 | Add-To-Cart And Mini-Cart Experience | F20, F21 | todo |
| F31 | Cart Page Experience | F30 | todo |
| F32 | Direct-Buy And Checkout Flow | F31 | todo |
| F33 | Checkout Confirmation And Flow Hardening | F32 | todo |

### Wave F4

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| F40 | About Contact FAQ Placeholder Content | F10, F02 | todo |
| F41 | Testimonial Placeholders And Trust Copy | F12, F40 | todo |
| F42 | Metadata Localization And Content QA | F12, F22, F33, F40, F41 | todo |

### Wave F5

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| F50 | Admin Login And Admin Shell | F01, F02 | todo |
| F51 | Admin Catalog Screens | F50 | todo |
| F52 | Admin Orders Screens | F50 | todo |
| F53 | Admin Frontend Hardening | F51, F52 | todo |

### Wave F6

| ID | Task | Depends On | Status |
| --- | --- | --- | --- |
| F60 | Responsive QA And Browser Flow Verification | F22, F33, F42, F53 | todo |
| F61 | Frontend Accessibility And Boundary Review | F60 | todo |
| F62 | Frontend Readiness And Repository-Level Verification | F61 | todo |

---

## Detailed Tasks

## F00 - Frontend Structure Audit And Route Baseline

### Goal

Confirm the repository is ready to receive the frontend implementation without breaking the current backend boundaries.

### Write Scope

- frontend documentation if needed
- `routes/web.php`
- route provider or boot configuration only if required
- high-level frontend scaffolding files

### Deliverables

- current web entrypoints are audited
- storefront route map is defined
- admin web route map is defined
- public and admin web boundaries are explicit
- current API-backed dependencies for cart, checkout, and admin flows are documented in implementation notes if needed

### Acceptance Criteria

- another engineer can identify where storefront routes and admin routes belong
- route decisions do not conflict with the session-backed cart flow
- the frontend baseline does not blur public and admin boundaries

### Out Of Scope

- full page implementation
- visual polish
- content writing

### Validation

- `docker exec ecommerce-app-1 php artisan route:list`
- targeted route inspection for public and admin boundaries

---

## F01 - Livewire Frontend Architecture Skeleton

### Goal

Create the minimum Blade and Livewire structure required to implement the storefront and admin frontend cleanly.

### Write Scope

- `app/Livewire/**` or equivalent Livewire entry structure
- `resources/views/**`
- shared frontend view boot files
- route wiring required for Livewire pages

### Deliverables

- storefront page structure exists
- admin page structure exists
- base layouts exist for public and admin surfaces
- reusable component and partial conventions are explicit
- Livewire page boundaries are clear and thin

### Acceptance Criteria

- page ownership is obvious
- Livewire is used for orchestration, not business logic
- public and admin layouts can evolve independently

### Out Of Scope

- final page content
- full visual language
- admin CRUD completion

### Validation

- `docker exec ecommerce-app-1 php artisan route:list`
- relevant Livewire or feature tests

---

## F02 - Localization And Shared Copy Foundation

### Goal

Create the localization baseline required for `pt-BR` launch and `en` support.

### Write Scope

- `lang/**`
- shared view files that consume localization keys
- metadata helpers or shared copy structures if needed

### Deliverables

- `pt-BR` and `en` locale structures exist
- shared copy namespaces exist for storefront, admin, and shared UI
- navigation, actions, states, and core checkout copy are localization-ready

### Acceptance Criteria

- new public and admin screens can use stable translation namespaces
- copy is not trapped as hard-coded text in large templates
- locale expansion does not require structural rewrites

### Out Of Scope

- fully translated marketing-quality copy for every future page
- SEO strategy beyond the MVP baseline

### Validation

- targeted feature or component tests for localized rendering
- manual locale-switch verification

---

## F10 - Storefront Layout Shell And Navigation

### Goal

Build the main public website shell and navigational structure.

### Write Scope

- public layouts
- header
- footer
- navigation
- mobile menu
- shared storefront partials and components

### Deliverables

- public layout shell exists
- header includes brand slot, key nav links, cart entry, and locale affordance
- footer includes support-oriented links and trust context
- mobile navigation works cleanly on modern smartphones

### Acceptance Criteria

- public navigation is usable on mobile and desktop
- cart and checkout entry points are easy to find
- admin links are not mixed into storefront navigation

### Out Of Scope

- full home page merchandising
- product listing logic
- admin layout

### Validation

- manual browser verification on mobile and desktop widths
- route verification for linked pages

---

## F11 - Brand Tokens And Non-White Visual Language

### Goal

Establish the storefront visual foundation for GR-Shop.

### Write Scope

- `resources/css/app.css`
- shared visual tokens
- base typography and section rhythm files
- reusable visual utility partials if needed

### Deliverables

- teal and amber brand anchors are defined
- dark non-white surfaces are defined
- base typography direction is defined
- spacing, radius, and shadow treatment are standardized
- logo slot and GR-Shop logotype treatment are reserved

### Acceptance Criteria

- the site no longer depends on Laravel starter visuals
- the system feels intentional and reusable
- the visual baseline can scale across storefront pages consistently

### Out Of Scope

- final logo artwork
- exhaustive multi-brand theming
- dark/light theme toggle

### Validation

- manual visual review across main storefront templates
- targeted CSS or snapshot verification if available

---

## F12 - Home Page Hero And Trust Sections

### Goal

Build the homepage as the main conversion-oriented introduction to GR-Shop.

### Write Scope

- home page Livewire or Blade page
- trust sections
- hero section
- featured content sections

### Deliverables

- homepage hero communicates what the store sells
- trust sections explain the manual purchase flow cleanly
- homepage highlights games, featured products, or curated sections
- homepage reinforces credibility without overstating capabilities

### Acceptance Criteria

- a first-time visitor can understand the offer quickly
- the page feels premium and game-adjacent
- the page remains usable on modern smartphones

### Out Of Scope

- full SEO strategy
- dynamic CMS
- real testimonial sourcing

### Validation

- manual browser verification
- targeted page feature tests where appropriate

---

## F20 - Catalog Listing Experience

### Goal

Build the public catalog browsing page aligned with the current backend catalog model.

### Write Scope

- catalog page components
- catalog views
- filtering UI
- catalog data orchestration

### Deliverables

- catalog listing page exists
- filtering aligns with current backend-supported dimensions
- product cards are clear and mobile-usable
- product cards render the backend `image_url` and keep a stable visual fallback for missing or failed images
- empty, loading, and no-result states are handled cleanly

### Acceptance Criteria

- users can browse products without confusion
- catalog images are visible in the ecommerce experience using the URL returned by the backend
- filters do not imply unsupported catalog dimensions
- the page works on mobile and desktop

### Out Of Scope

- advanced search engine behavior
- recommendations engine
- unsupported category models

### Validation

- relevant feature or Livewire tests
- manual verification across mobile and desktop widths

---

## F21 - Product Detail Experience

### Goal

Build the product detail page that supports both browsing confidence and purchase intent.

### Write Scope

- product detail page components
- detail-page view partials
- add-to-cart and buy-now triggers

### Deliverables

- product detail page exists
- product image, title, price, game, rarity, and availability context are visible
- product image uses the backend `image_url` generated from Laravel storage or another backend-approved product image source
- add-to-cart entry is visible
- buy-now entry is visible
- trust and fulfillment messaging are concise and honest

### Acceptance Criteria

- the page gives enough confidence to continue to cart or checkout
- product image display works consistently from storage-backed image URLs
- unsupported features are not implied
- detail-page structure remains reusable for future frontend growth

### Out Of Scope

- 3D preview implementation
- review engine
- related-products engine beyond simple curated logic

### Validation

- targeted page tests
- manual product-flow verification

---

## F22 - Storefront Discovery Hardening

### Goal

Harden public browsing behavior, edge states, and consistency between the home, catalog, and product detail pages.

### Write Scope

- storefront page states
- shared card and section components
- route-level metadata and content adjustments if needed

### Deliverables

- consistent product-card language across storefront pages
- stable empty and unavailable states
- browsing flow remains coherent from home to catalog to product detail
- metadata baseline for discovery pages is in place

### Acceptance Criteria

- discovery pages feel like one coherent storefront
- no unsupported promises appear in browsing copy
- public browsing remains stable under missing or sparse data

### Out Of Scope

- campaign system
- advanced personalization

### Validation

- storefront feature tests
- manual cross-page QA

---

## F30 - Add-To-Cart And Mini-Cart Experience

### Goal

Connect storefront product actions to the current backend-managed session cart.

### Write Scope

- add-to-cart UI
- mini-cart or cart summary UI
- cart state indicators in header or shared layout
- cart interaction components

### Deliverables

- add-to-cart works from supported entry points
- header or mini-cart reflects backend-backed cart state
- quantity feedback and expected cart messaging are visible
- the UI respects session-based cart ownership

### Acceptance Criteria

- cart behavior remains aligned with backend truth
- repeated adds behave clearly for the user
- cart feedback works on mobile and desktop

### Out Of Scope

- client-owned persistent cart
- cross-device cart sync
- coupon logic

### Validation

- relevant cart feature or Livewire tests
- manual session-cart verification

---

## F31 - Cart Page Experience

### Goal

Build the public cart page over the existing session-backed cart flow.

### Write Scope

- cart page components
- quantity update UI
- remove-item UI
- summary UI

### Deliverables

- cart page exists
- cart items can be updated or removed
- subtotal or equivalent summary is shown clearly
- empty-cart state is handled
- clear checkout entry is present

### Acceptance Criteria

- cart page matches backend cart state
- cart edits are understandable to the user
- mobile cart flow remains easy to operate

### Out Of Scope

- taxes
- shipping
- discounts
- saved items

### Validation

- cart feature tests
- manual browser verification for quantity update and remove flows

---

## F32 - Direct-Buy And Checkout Flow

### Goal

Implement the direct-buy and minimal checkout experience required by the current MVP.

### Write Scope

- checkout page
- buy-now entry logic
- checkout form components
- order submission orchestration

### Deliverables

- direct-buy path leads users toward checkout cleanly
- checkout page collects only `email` and `whatsapp`
- order submission aligns with the current backend order flow
- unsupported payment UI is not introduced

### Acceptance Criteria

- users can reach checkout from cart and from direct buy
- form fields match MVP backend expectations
- the flow remains clear and trustworthy on mobile

### Out Of Scope

- payment capture UI
- shipping address flows
- coupon redemption
- customer account creation

### Validation

- checkout feature tests
- manual buy-now and cart-to-checkout verification

---

## F33 - Checkout Confirmation And Flow Hardening

### Goal

Finalize the post-checkout confirmation experience and harden cart-to-order flow messaging.

### Write Scope

- confirmation page or confirmation section
- post-order messaging
- fallback states for expected checkout issues

### Deliverables

- order confirmation explains manual follow-up clearly
- confirmation does not imply immediate automated payment or fulfillment
- expected checkout failures are rendered clearly
- post-order cart behavior remains consistent with backend rules

### Acceptance Criteria

- users understand what happens after order submission
- confirmation copy feels trustworthy and calm
- session-cart clearing behavior is reflected correctly in the UI

### Out Of Scope

- payment status tracking
- advanced order history
- self-service post-purchase area

### Validation

- order flow feature tests
- manual checkout completion verification

---

## F40 - About Contact FAQ Placeholder Content

### Goal

Create the supporting informational pages required for the MVP storefront.

### Write Scope

- About page
- Contact page
- FAQ or help page
- localized content files for those pages

### Deliverables

- About page exists
- Contact page exists
- FAQ or help page exists
- placeholder content is concise, natural, and editable

### Acceptance Criteria

- supporting pages strengthen trust without feeling spammy
- content is honest about the current business model
- pages are localization-ready

### Out Of Scope

- legal-document completeness beyond MVP needs
- live support integrations
- corporate history pages

### Validation

- manual content review in both locales
- route verification

---

## F41 - Testimonial Placeholders And Trust Copy

### Goal

Add controlled placeholder trust content for the public storefront.

### Write Scope

- storefront trust sections
- testimonial content sources
- localized placeholder copy

### Deliverables

- at most 3 placeholder testimonials exist
- testimonials sound natural and restrained
- trust copy explains process and support clearly

### Acceptance Criteria

- trust content does not feel fabricated in an exaggerated way
- copy avoids unsupported promises
- the placeholder nature of this content remains easy to revise later

### Out Of Scope

- real review integrations
- ratings systems
- public review submissions

### Validation

- manual copy review
- localized rendering verification

---

## F42 - Metadata Localization And Content QA

### Goal

Finalize the content and metadata baseline for the storefront.

### Write Scope

- page metadata helpers
- localized metadata files
- public page content adjustments

### Deliverables

- public pages have titles and concise descriptions
- locale-aware metadata exists where appropriate
- public copy remains consistent across storefront flows

### Acceptance Criteria

- metadata baseline exists for key public pages
- locale switching does not break content consistency
- the storefront reads as one coherent brand surface

### Out Of Scope

- advanced SEO automation
- content marketing infrastructure

### Validation

- manual metadata inspection
- manual locale-switch QA

---

## F50 - Admin Login And Admin Shell

### Goal

Build the separate admin web shell over the existing admin authentication boundary.

### Write Scope

- admin layout
- admin login screen
- admin dashboard shell
- admin navigation

### Deliverables

- `/admin/login` screen exists
- admin shell exists behind auth
- admin dashboard exists with navigation shortcuts
- admin layout is distinct from the public storefront

### Acceptance Criteria

- admin users can enter the admin web surface clearly
- the admin shell is functional and simple
- public users are not exposed to admin navigation in the storefront

### Out Of Scope

- full admin screen implementation
- fine-grained admin personalization

### Validation

- admin auth feature tests
- manual admin/public boundary verification

---

## F51 - Admin Catalog Screens

### Goal

Build the admin UI screens for managing catalog entities.

### Write Scope

- admin games screens
- admin rarities screens
- admin products screens
- shared admin form and list components

### Deliverables

- admin can list, create, edit, and remove games where allowed
- admin can list, create, edit, and remove rarities where allowed
- admin can list, create, edit, and remove products
- product create/edit forms include image upload support aligned with the backend Laravel storage flow
- product create requires an image upload, and product edit shows the current image with an option to replace it
- admin product list/detail screens show the stored product image or a clear fallback state
- admin forms and tables remain operational and readable

### Acceptance Criteria

- admin catalog work is possible without using raw API tools
- forms remain aligned with existing backend capabilities
- product image upload sends a file payload accepted by the backend instead of asking admins to paste a raw image URL
- after create or update, the admin UI uses the returned `image_url` and the public ecommerce pages can display the image
- image validation errors from the backend are shown clearly in the product form
- admin screen design remains intentionally simpler than storefront design

### Out Of Scope

- bulk operations unless clearly required
- advanced reporting
- admin role matrix expansion

### Validation

- admin feature or Livewire tests
- manual CRUD verification
- manual product image upload, preview, replacement, and storefront display verification

---

## F52 - Admin Orders Screens

### Goal

Build the admin order list and order detail experiences for manual fulfillment follow-up.

### Write Scope

- admin orders list
- admin order detail page
- shared admin table and detail components

### Deliverables

- admin orders list exists
- admin order detail exists
- buyer email, WhatsApp, and order items are readable
- the operational path for manual follow-up is clear

### Acceptance Criteria

- admins can inspect created orders without ambiguity
- order detail supports the current manual fulfillment workflow
- admin order screens remain separate from public purchase history concepts

### Out Of Scope

- public customer order history
- fulfillment automation
- payment status engine

### Validation

- admin order feature tests
- manual orders navigation verification

---

## F53 - Admin Frontend Hardening

### Goal

Stabilize the admin frontend and confirm the admin web surface behaves as a coherent operational tool.

### Write Scope

- admin shared components
- admin UX adjustments
- boundary and fallback states

### Deliverables

- admin layouts, forms, and tables are consistent
- expected empty and error states are clear
- admin routing and auth transitions are stable

### Acceptance Criteria

- admin UI feels cohesive
- admin/public separation remains strong
- operational tasks can be completed without visual confusion

### Out Of Scope

- advanced admin analytics
- deep role customization

### Validation

- admin feature tests
- manual auth and navigation QA

---

## F60 - Responsive QA And Browser Flow Verification

### Goal

Verify the main frontend flows across modern responsive breakpoints and browser behavior expectations.

### Write Scope

- QA notes
- minor frontend fixes discovered during verification

### Deliverables

- public flows are checked on modern smartphone widths
- admin flows are checked on practical responsive widths
- major browser-flow issues are fixed or documented

### Acceptance Criteria

- the main storefront flow works end-to-end on modern mobile widths
- the admin flow remains usable
- critical responsive regressions are resolved

### Out Of Scope

- exhaustive legacy-browser support
- old-device optimization

### Validation

- manual browser QA
- task-relevant automated tests

---

## F61 - Frontend Accessibility And Boundary Review

### Goal

Review the frontend for practical accessibility and public/admin boundary discipline.

### Write Scope

- minor frontend fixes
- accessibility adjustments
- navigation and focus-state fixes

### Deliverables

- semantic landmarks are present on key pages
- visible focus states exist
- critical interactions are keyboard-usable
- public and admin boundaries remain structurally separate

### Acceptance Criteria

- major accessibility gaps in core flows are closed
- keyboard use is viable for main navigation and checkout actions
- admin links do not leak into the storefront shell

### Out Of Scope

- full formal accessibility certification
- enterprise accessibility tooling rollout

### Validation

- manual keyboard verification
- targeted automated tests where available

---

## F62 - Frontend Readiness And Repository-Level Verification

### Goal

Confirm the frontend MVP is ready for implementation sign-off at the repository level.

### Write Scope

- final QA notes
- small stabilizing fixes
- documentation updates if needed

### Deliverables

- key public flows are verified
- key admin flows are verified
- localization baseline is verified
- route and component structure are coherent
- repository-level validation passes for the delivered frontend scope

### Acceptance Criteria

- users can browse catalog, inspect products, use cart, and complete minimal checkout
- admin can authenticate and operate core catalog and order screens
- no critical contradiction exists between frontend behavior and backend truth

### Out Of Scope

- future wave planning beyond the frontend MVP
- payment platform expansion
- customer accounts

### Validation

- `docker exec ecommerce-app-1 php artisan test`
- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`
- manual end-to-end browser verification for storefront and admin flows
