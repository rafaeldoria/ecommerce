# Tasks - Frontend Part Two Execution Plan

## Purpose

This document defines the second focused frontend delivery slice for GR-Shop.

Part One made the admin surface reachable and useful for reading operational data. During the follow-up audit, the catalog admin web screens were confirmed to be list-only:

- `app/Livewire/Admin/Games.php` renders games but does not create, edit, update, or delete them
- `app/Livewire/Admin/Rarities.php` renders rarities but does not create, edit, update, or delete them
- `app/Livewire/Admin/Products.php` renders products but does not create, edit, update, or delete them
- the Blade views for those modules only contain tables and empty states

The backend admin API and domain Actions already contain the required catalog write behavior. The gap is the admin web CRUD experience.

Frontend Part Two must add simple, reliable CRUD controls for:

- games
- rarities
- products

The goal is not to build a large admin platform. The goal is to make the current MVP operable from the browser by an authenticated admin.

---

## Canonical Inputs

Use this file together with:

- `src/.codex/docs/project-front.md`
- `src/.codex/docs/decisions-front.md`
- `src/.codex/docs/tasks-front.md`
- `src/.codex/docs/tasks-front-part-one.md`
- `src/.codex/docs/tasks-admin.md`

If any conflict appears:

1. frontend architecture and documented decisions win
2. backend Actions remain the source of business behavior
3. Livewire components orchestrate forms and feedback but do not duplicate domain rules
4. admin CRUD must stay inside the authenticated admin route boundary

---

## Current State Check

Admin CRUD is not fully working in the browser today.

What works:

- `/admin/login` is routed to a Livewire component
- `/admin`, `/admin/games`, `/admin/rarities`, `/admin/products`, and `/admin/orders` exist behind `auth` and `admin` middleware
- admin catalog pages can list existing games, rarities, and products
- backend API controllers and Actions support create, update, and delete for games, rarities, and products

What is missing:

- no visible create buttons on games, rarities, or products pages
- no admin web forms for creating catalog records
- no edit controls or update flow
- no delete controls or confirmation flow
- no product image upload flow in the Livewire admin web interface
- no Livewire feature tests proving browser-admin CRUD behavior

---

## Template Research And Selection

The simplest implementation template for this project is a native Livewire CRUD page pattern:

- table/list area
- compact create/edit form shown inline or in a modal-like panel
- row-level edit and delete actions
- confirmation before destructive delete
- localized success and error feedback
- Livewire file upload support for product images

Alternatives considered:

- Filament Resources: very strong for admin CRUD, but it introduces a full admin panel architecture and package surface that is larger than the MVP needs right now.
- Mary UI table/modal components: useful Livewire-oriented UI components, but it also adds a package/design dependency before the project has exhausted its existing Blade/Tailwind patterns.
- Native Livewire Forms and Blade components: smallest change, matches the current architecture, keeps the existing admin shell, and preserves backend Action boundaries.

Selected approach:

- use native Livewire plus existing Blade/Tailwind admin styling
- introduce small reusable admin form/table presentation pieces only when duplication becomes real
- call existing catalog Actions directly from Livewire components or through thin application methods, keeping validation aligned with existing request rules
- avoid adding Filament, Mary UI, or another admin package for this wave

Reference direction:

- Livewire forms use `wire:submit` and component validation
- Livewire uploads use `WithFileUploads` and `wire:model` for file inputs
- delete actions should be explicit and confirmation-based

---

## Delivery Summary

This wave upgrades admin catalog pages from read-only operational visibility to usable CRUD.

Admin decisions for this part:

- keep the existing admin route map:
  - `/admin/games`
  - `/admin/rarities`
  - `/admin/products`
- keep each resource on one page for the MVP
- use table plus create/edit panel instead of separate create/edit routes
- preserve the current admin layout and navigation
- keep all user-facing copy in `lang/en/admin.php`, `lang/pt_BR/admin.php`, and shared translation files
- make product image upload required on create and optional on update, matching the backend rules
- show blocked delete errors when games or rarities are still referenced by products

---

## Delivery Wave

### Wave P2.0 - Admin Catalog CRUD

This is one wave broken into small implementation pieces. Tasks may be done in order by one owner, or split carefully if write scopes do not overlap.

#### P200 - CRUD Reality Check And Form Contract

Goal:

- lock the exact web CRUD contract before editing the admin components

Deliverables:

- confirm the fields needed by games, rarities, and products
- map Livewire validation rules to the existing admin request rules
- confirm create/update/delete Actions that Livewire should call
- confirm delete failure behavior for games and rarities with referenced products

Acceptance criteria:

- another engineer can see that the browser gap is in the Livewire admin layer, not in backend Actions
- product form requirements are explicit:
  - name
  - image on create
  - optional replacement image on update
  - quantity
  - price
  - game
  - rarity

#### P201 - Shared Admin CRUD UI Pattern

Goal:

- establish a consistent lightweight CRUD interaction model for admin catalog pages

Deliverables:

- create button placement for module pages
- edit and delete row actions
- reusable confirmation pattern for delete
- reusable flash/status area for create, update, delete, and blocked delete feedback
- consistent admin input, select, file input, and error-message styling

Acceptance criteria:

- games, rarities, and products can use the same visual CRUD language
- keyboard users can reach create, edit, cancel, save, and delete controls
- destructive actions are visually distinct and confirmation-based

#### P202 - Games CRUD

Goal:

- allow admins to create, edit, update, and delete games from `/admin/games`

Deliverables:

- create game form
- edit game flow
- delete game action with confirmation
- blocked-delete feedback when products reference the game
- localized labels, helper text, buttons, and messages

Acceptance criteria:

- an admin can create a game and see it in the list without leaving the page
- an admin can edit a game name and see the updated value
- an admin can delete an unused game
- an admin receives a clear message when deleting a game is blocked because products reference it
- non-admin users remain blocked by existing middleware

#### P203 - Rarities CRUD

Goal:

- allow admins to create, edit, update, and delete rarities from `/admin/rarities`

Deliverables:

- create rarity form
- edit rarity flow
- delete rarity action with confirmation
- blocked-delete feedback when products reference the rarity
- localized labels, helper text, buttons, and messages

Acceptance criteria:

- an admin can create a rarity and see it in the list without leaving the page
- an admin can edit a rarity name and see the updated value
- an admin can delete an unused rarity
- an admin receives a clear message when deleting a rarity is blocked because products reference it
- validation prevents duplicate active rarity names when the backend rule requires uniqueness

#### P204 - Products CRUD

Goal:

- allow admins to create, edit, update, and delete products from `/admin/products`

Deliverables:

- create product form with:
  - name
  - image upload
  - quantity
  - price
  - game select
  - rarity select
- edit product form with current values pre-filled
- optional replacement image on update
- image preview or stable current-image display
- delete product action with confirmation
- localized labels, helper text, buttons, and messages

Acceptance criteria:

- an admin can create a product with a valid image
- an admin can edit product name, quantity, price, game, rarity, and optionally image
- an admin can delete a product
- the product list refreshes after create, update, and delete
- invalid image type, oversized image, missing game, or missing rarity returns understandable validation feedback
- the product image handling continues to use the backend-owned storage behavior

#### P205 - Admin CRUD Tests

Goal:

- prove the new browser-admin CRUD behavior works through Livewire

Deliverables:

- Livewire feature tests for games create/update/delete
- Livewire feature tests for rarities create/update/delete
- Livewire feature tests for products create/update/delete, including image upload
- authorization/protection coverage for non-admin access where not already covered
- blocked-delete tests for referenced games and rarities

Acceptance criteria:

- tests prove successful CRUD paths
- tests prove validation failures are shown
- tests prove delete blocking for referenced games and rarities
- tests do not depend on external services or remote images

#### P206 - Admin CRUD UX Hardening

Goal:

- make the CRUD pages feel stable enough for daily MVP operation

Deliverables:

- loading states on save/delete actions
- disabled duplicate submit behavior during save/delete
- clear cancel/reset behavior
- empty-state copy that points admins toward creating the first record
- mobile and narrow viewport checks for admin tables/forms
- manual browser verification notes if implementation docs are updated

Acceptance criteria:

- forms reset after successful create
- edit mode can be cancelled without mutating data
- buttons do not jump or resize unexpectedly during loading
- tables remain usable on small screens through horizontal scroll or responsive layout
- success and error states are visible without relying on color alone

---

## Out Of Scope

- replacing the admin shell with Filament or another admin package
- adding bulk actions
- adding product variants
- adding advanced inventory history
- adding rich text descriptions
- adding drag-and-drop image management
- changing public storefront behavior
- changing backend domain rules unless a bug is discovered while implementing the web CRUD

---

## Suggested Write Scope

- `app/Livewire/Admin/Games.php`
- `app/Livewire/Admin/Rarities.php`
- `app/Livewire/Admin/Products.php`
- `resources/views/livewire/admin/games.blade.php`
- `resources/views/livewire/admin/rarities.blade.php`
- `resources/views/livewire/admin/products.blade.php`
- `resources/views/components/admin/**` if reusable admin form/table pieces are introduced
- `lang/en/admin.php`
- `lang/pt_BR/admin.php`
- `lang/en/shared.php`
- `lang/pt_BR/shared.php`
- `tests/Feature/Livewire/Admin/**` or the closest existing Livewire/Admin test namespace

Avoid changing public storefront files unless a shared component already used by admin must be adjusted.

---

## Validation

Run through the app container when available:

- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 php artisan test --filter=Livewire`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

Manual verification:

- sign in at `/admin/login`
- create, edit, update, and delete a game
- create, edit, update, and delete a rarity
- create, edit, update, and delete a product with image upload
- attempt to delete a game referenced by a product
- attempt to delete a rarity referenced by a product
- verify non-admin users cannot reach admin CRUD pages
