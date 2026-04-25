# Tasks - Frontend Part One Execution Plan

## Purpose

This document reframes the current frontend effort into a focused first delivery slice.

It exists because `tasks-admin.md` confirms the admin backend and admin API baseline, but it does not prove that the admin web experience is complete. The same gap existed on the public side: foundational routes and layouts were present, while the real storefront experience was still too close to placeholder screens.

Frontend Part One therefore prioritizes immediate visual and operational impact:

- visible catalog inventory
- game switching in the storefront
- a real footer and stronger page finish
- functional admin web entry
- navigable admin operational screens

---

## Canonical Inputs

Use this file together with:

- `src/.codex/docs/project-front.md`
- `src/.codex/docs/decisions-front.md`
- `src/.codex/docs/tasks-front.md`
- `src/.codex/docs/tasks-admin.md`

If any conflict appears:

1. frontend architecture and documented decisions win
2. backend truth wins over speculative UI behavior
3. this file wins over the assumption that the full frontend MVP is already complete

---

## Delivery Summary

This first part replaces the idea of “frontend MVP complete” with a smaller, reviewable scope that makes the site feel alive and makes the admin surface actually reachable.

Public decisions for this part:

- keep `GET /catalog` as the canonical catalog route
- use query-string game selection:
  - `/catalog`
  - `/catalog?game=dota-2`
  - `/catalog?game=cs2`
- keep the header short:
  - `Home`
  - `Catalog`
  - `Cart`
- move `About`, `Contact`, and `FAQ` into the footer

Admin decisions for this part:

- `/admin/login` must work in the browser
- frontend login must reuse the existing backend admin authentication rules
- admin shell must expose real navigation and explicit logout
- admin modules must render live operational data instead of placeholder copy

---

## Delivery Waves

### Wave P1.0 - Reality Check And Reframe

#### P100 - Admin And Frontend Gap Audit

Goal:

- make the distinction between backend completion and frontend completion explicit

Deliverables:

- document that `tasks-admin.md` covers admin backend/API scope
- document that the initial frontend state was still placeholder-heavy

Acceptance criteria:

- another engineer can quickly identify what was already true in the backend and what still needed web implementation

#### P101 - Part One IA Baseline

Goal:

- lock the information architecture for the first meaningful public/admin pass

Deliverables:

- storefront header reduced to commerce-first navigation
- institutional links moved to the footer
- admin route entry centered on `/admin/login`

Acceptance criteria:

- navigation feels commercially focused and no longer split between too many top-level priorities

### Wave P1.1 - Storefront Visibility First

#### P110 - Catalog Visibility And Game Switching

Goal:

- make the catalog useful immediately

Deliverables:

- visible game chips/tabs on the catalog page
- server-rendered item grid
- selected game persisted through the query string

Acceptance criteria:

- the user can switch between Dota 2 and CS2 without confusion
- the first viewport already shows inventory-related content

#### P111 - Product Card System

Goal:

- standardize how items appear in the storefront

Deliverables:

- reusable product card pattern with image, name, game, rarity, price, and CTA
- stable image fallback behavior

Acceptance criteria:

- cards remain scannable on mobile and desktop
- broken image URLs do not collapse the layout

#### P112 - Demo Catalog Data For Visual QA

Goal:

- remove the need to review the storefront against empty local data

Deliverables:

- at least 5 Dota 2 products seeded locally
- at least 5 CS2 products seeded locally
- valid placeholder images for local review

Acceptance criteria:

- a fresh local environment can review the storefront without relying on a manually prepared catalog

### Wave P1.2 - Footer And Trust Layout

#### P120 - Footer Reconstruction

Goal:

- give the site a real bottom section and stronger trust framing

Deliverables:

- multi-column footer with:
  - About
  - Quick links
  - Support
  - Contact
- footer anchored to the true page end

Acceptance criteria:

- the footer no longer reads like a temporary line of text
- short pages still end correctly with the footer at the bottom

#### P121 - Header Cleanup

Goal:

- reduce competition in the top navigation

Deliverables:

- remove `About`, `Contact`, and `FAQ` from the header
- keep those pages accessible from the footer

Acceptance criteria:

- the header stays focused on discovery and purchase flow

### Wave P1.3 - Admin Entry And Operational Access

#### P130 - Admin Login Activation

Goal:

- make the browser login route genuinely usable

Deliverables:

- functional `/admin/login`
- existing backend admin credential rules reused as the source of truth
- validation feedback for bad credentials

Acceptance criteria:

- a valid admin can sign in from the browser and land on the admin dashboard
- a non-admin cannot access the admin surface

#### P131 - Admin Shell Readiness

Goal:

- make the admin area feel like a separate operational tool

Deliverables:

- clear admin shell
- post-login navigation
- explicit logout

Acceptance criteria:

- admin users understand that they are in a separate authenticated surface

#### P132 - Admin CRUD Visibility Baseline

Goal:

- replace placeholder module pages with real operational visibility

Deliverables:

- games list
- rarities list
- products list
- orders list
- order detail

Acceptance criteria:

- the admin surface becomes navigable and useful even before full form-based CRUD is expanded

### Wave P1.4 - Hardening And Acceptance

#### P140 - Frontend Part One QA

Goal:

- verify the three user-visible complaints are solved in a browser-visible way

Acceptance criteria:

- catalog shows real items
- footer sits at the page end and carries institutional links
- admin login works and protected admin routes remain protected

---

## Validation

Run through the app container when available:

- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan test --filter=Frontend`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

Manual scenarios required for acceptance:

- catalog renders live products instead of placeholder copy
- switching between `dota-2` and `cs2` updates the rendered product list
- empty catalog state remains intentional and readable
- footer appears at the end of home, catalog, and product pages
- header no longer carries `About`, `Contact`, and `FAQ`
- `/admin/login` authenticates a valid admin user
- non-admin users do not enter the admin area
- `/admin/*` pages stay protected
- local seed data provides at least 5 products per main game for visual QA

---

## Defaults And Assumptions

- This file is intentionally a first-part delivery plan, not a replacement for the full future frontend scope.
- The storefront remains Blade-first and Livewire-first.
- Business logic stays in backend Actions and domain boundaries.
- Admin authentication rules remain backend-owned; the web UI only orchestrates entry.
- Footer content may use placeholder business data for now, but it must read as deliberate and trustworthy.
