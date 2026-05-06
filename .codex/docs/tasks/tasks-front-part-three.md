# Tasks - Frontend Part Three Execution Plan

## Purpose

This document defines the third focused frontend delivery slice for GR-Shop.

Part One made the storefront visible and the admin surface reachable. Part Two added browser-based CRUD for catalog resources. Part Three must now harden the MVP flows that were exposed by real usage:

- storefront catalog pagination
- admin list pagination
- product-to-cart behavior
- cart badge and cart page visibility
- clickable admin dashboard stats
- dedicated admin edit pages
- visible language selection
- consistent currency spacing
- long product-name resilience in admin tables

The goal is not to expand the MVP with new commerce capabilities. The goal is to make the existing MVP easier to operate and less fragile in normal browser use.

---

## Canonical Inputs

Use this file together with:

- `src/.codex/docs/project-front.md`
- `src/.codex/docs/decisions-front.md`
- `src/.codex/docs/tasks-front.md`
- `src/.codex/docs/tasks-front-part-one.md`
- `src/.codex/docs/tasks-front-part-two.md`
- `src/.codex/docs/tasks-admin.md`
- `src/.codex/docs/cart.md`

If any conflict appears:

1. frontend architecture and documented decisions win
2. backend Actions remain the source of business behavior
3. the session-backed cart remains the storefront cart source of truth
4. Livewire components orchestrate UI and user flow, but must not duplicate domain rules
5. admin changes must stay inside the authenticated admin route boundary

---

## Current State Check

The current frontend MVP has usable screens, but several behaviors are still incomplete or fragile.

What works:

- `/catalog` renders products for the selected game
- `/products/{product}` renders a product detail page
- `/cart` exists as a storefront route
- cart backend Actions already exist:
  - `AddToCartAction`
  - `GetCurrentCartAction`
  - `UpdateCartItemAction`
  - `RemoveFromCartAction`
- `/admin`, `/admin/games`, `/admin/rarities`, `/admin/products`, and `/admin/orders` exist behind `auth` and `admin` middleware
- games, rarities, and products can be created and edited from admin pages
- localization files already exist for `en` and `pt_BR`

What is missing or needs adjustment:

- catalog loads all selected-game products instead of limiting to 9 per page
- admin resource lists load all rows instead of limiting to 10 per page
- the product detail `go_to_cart` action only links to the cart page and does not add the product
- the storefront header does not signal how many items are in the cart
- the cart page does not show the current cart items
- admin dashboard stat cards show counts but are not clickable shortcuts
- admin edit actions open inline forms at the top of the current list page instead of redirecting to dedicated edit pages
- the header has no visible language selector
- currency output can render without a normal visual space between the currency symbol and value
- very long product names can stretch admin product rows and break action button layout

---

## Delivery Summary

Part Three is a refinement and flow-completion wave.

Public storefront decisions for this part:

- catalog pagination must show a maximum of 9 products per page
- selected game must remain in the query string while paginating
- product detail `go_to_cart` must add quantity `1` of the current product to the session cart, then redirect to `/cart`
- cart header indicator must reflect the server-owned session cart
- cart page must render current cart items from backend cart state
- language selector must show two flag options:
  - United States for `en`
  - Brazil for `pt-BR`
- if the locale switch infrastructure can be completed safely within this part, the selected language must be persisted and respected by pages
- if deeper locale work is required, show the selector as a visible prepared UI and document the remaining locale activation work
- product prices must render with a normal space after the currency symbol, for example `R$ 1,399.00`

Admin decisions for this part:

- admin list pagination must show 10 rows per page
- dashboard stat cards must become clickable shortcuts to their matching admin modules
- edit actions must redirect to dedicated edit pages:
  - `/admin/games/{game}/edit`
  - `/admin/rarities/{rarity}/edit`
  - `/admin/products/{product}/edit`
- create may remain on the index page unless implementation naturally extracts create pages
- long product names must be visually constrained so action buttons remain usable
- table layout must remain keyboard-accessible and usable on narrow screens

---

## Delivery Waves

### Wave P3.0 - Pagination Baseline

#### P300 - Storefront Catalog Pagination

Goal:

- limit catalog product rendering to 9 products per page without losing game selection

Deliverables:

- `Catalog` uses Livewire/Laravel pagination instead of loading all products into a plain array
- products per catalog page set to `9`
- selected `game` query string persists through pagination links
- product count UI distinguishes between total available products and current page when needed
- empty state still works for games with no available products

Acceptance criteria:

- `/catalog?game=dota-2` shows at most 9 products
- pagination links keep `game=dota-2`
- switching games resets to a valid first page
- catalog cards keep the same visual pattern and image fallback behavior
- no unsupported client-side product state is introduced

#### P301 - Admin List Pagination

Goal:

- limit admin operational lists to 10 rows per page

Deliverables:

- games list paginated at 10 rows per page
- rarities list paginated at 10 rows per page
- products list paginated at 10 rows per page
- orders list paginated at 10 rows per page
- existing create/edit/delete feedback remains clear after pagination changes

Acceptance criteria:

- `/admin/games`, `/admin/rarities`, `/admin/products`, and `/admin/orders` render at most 10 rows per page
- pagination controls are visible when more than 10 records exist
- CRUD mutations refresh the current page safely
- empty states remain intentional
- admin route protection is unchanged

### Wave P3.1 - Storefront Cart Completion

#### P310 - Product Detail Add-To-Cart Redirect

Goal:

- make the product detail primary cart action actually add the product before navigating to the cart page

Deliverables:

- product detail page calls `AddToCartAction` with the current product and quantity `1`
- successful add redirects to `route('storefront.cart')`
- invalid or unavailable products rely on existing backend/domain failure behavior
- localized success or status copy is added if the cart page or redirect uses it

Acceptance criteria:

- clicking `go_to_cart` on a product detail page adds that product to the session cart
- the browser lands on `/cart`
- repeated clicks consolidate quantity according to existing cart Action behavior
- the implementation does not manually mutate session cart structure outside the cart Action

#### P311 - Storefront Cart Indicator

Goal:

- signal cart state in the top navigation.

Deliverables:

- header Cart link displays a count/badge derived from `GetCurrentCartAction`
- badge updates after add-to-cart redirects and regular page loads
- empty cart state shows no misleading quantity
- badge copy/aria label is localized

Acceptance criteria:

- after adding one product, the Cart menu clearly signals that the cart contains an item
- item count reflects total quantity, not only unique product rows, unless a documented decision chooses row count
- header remains responsive on mobile and desktop

#### P312 - Cart Page Item List

Goal:

- make `/cart` show the current session-backed cart contents to the client

Deliverables:

- cart page reads current cart items through `GetCurrentCartAction`
- cart page renders product name, image fallback, quantity, unit price, line total, and cart total
- remove item action calls `RemoveFromCartAction`
- quantity update calls `UpdateCartItemAction` if quantity controls are added in this part
- checkout entry remains visible when cart is not empty
- empty cart state points back to catalog

Acceptance criteria:

- clicking the Cart menu shows the products previously added to the cart
- removing an item updates the rendered cart
- invalid quantities are rejected through existing cart rules
- totals are formatted consistently with storefront currency formatting
- checkout is not presented as available for an empty cart

### Wave P3.2 - Admin Navigation And Dedicated Edit Pages

#### P320 - Clickable Admin Dashboard Cards

Goal:

- turn dashboard stats into useful navigation shortcuts

Deliverables:

- Games card links to `admin.games.index`
- Rarities card links to `admin.rarities.index`
- Products card links to `admin.products.index`
- Orders card links to `admin.orders.index`
- cards have visible hover/focus states and accessible labels

Acceptance criteria:

- each dashboard card is clickable by mouse and keyboard
- count text remains visible and scannable
- dashboard visual style remains admin-focused and restrained

#### P321 - Dedicated Admin Edit Routes

Goal:

- establish route-backed edit pages for admin catalog records

Deliverables:

- routes added:
  - `GET /admin/games/{game}/edit`
  - `GET /admin/rarities/{rarity}/edit`
  - `GET /admin/products/{product}/edit`
- Livewire page components created or existing components refactored with clear index/edit responsibilities
- edit buttons on index pages become links to the matching edit route
- cancel/back action returns to the resource index
- authorization middleware remains `auth` and `admin`

Acceptance criteria:

- clicking edit for a game opens `/admin/games/{game}/edit`
- clicking edit for a rarity opens `/admin/rarities/{rarity}/edit`
- clicking edit for a product opens `/admin/products/{product}/edit`
- edit forms save through existing update Actions
- index pages no longer open edit forms at the top of the list

#### P322 - Admin Edit Page UX Hardening

Goal:

- make dedicated edit pages stable enough for daily admin work

Deliverables:

- edit pages use clear page title, resource identifier, form, save, and cancel/back controls
- validation errors render next to the relevant fields
- success feedback is visible after update, either on the edit page or after redirect back to index
- product edit keeps current image display and optional replacement upload
- delete confirmation behavior remains on index pages unless a separate documented decision moves it

Acceptance criteria:

- edit pages are usable on mobile and desktop
- save buttons have loading/disabled states
- cancel does not mutate data
- product image replacement remains backend-storage-owned

### Wave P3.3 - Localization Selector And Formatting Polish

#### P330 - Language Selector Readiness

Goal:

- add a visible language selector and verify whether full language switching is already safe to activate

Deliverables:

- audit current locale middleware/configuration/session support
- add header language selector with two flag options:
  - United States / `en`
  - Brazil / `pt-BR`
- if safe, add a locale switch route or Livewire action that persists the locale in session and updates `app()->getLocale()`
- if not safe, render the selector as prepared UI and document the missing backend/middleware work
- selected language state is visually clear when locale switching is active

Acceptance criteria:

- header shows both language options
- if locale switching is active, choosing a language changes localized UI copy on subsequent pages
- if locale switching is not active, the selector does not pretend the site changed language
- selector remains accessible by keyboard and screen readers

#### P331 - Currency Spacing Standard

Goal:

- ensure storefront prices include a normal readable space between currency symbol and amount

Deliverables:

- shared formatting approach for storefront prices, replacing ad hoc `Number::currency` output where needed
- catalog cards show `R$ 1,399.00` style spacing
- product detail and cart totals use the same spacing rule
- admin price display is reviewed and adjusted only where it is user-facing formatted currency

Acceptance criteria:

- no storefront product price renders as `R$1,399.00`
- catalog, product detail, and cart page use the same currency presentation
- formatting remains localization-aware enough not to block future `en` and `pt-BR` polish

### Wave P3.4 - Admin Table Resilience

#### P340 - Long Product Name Guard

Goal:

- prevent long product names from breaking admin product row actions

Deliverables:

- admin product table constrains the product-name column width
- product names are truncated or wrapped with a deliberate max-width strategy
- full product name remains available through `title`, accessible label, or detail/edit page
- row action buttons keep stable width and alignment

Acceptance criteria:

- a product with a very long name does not push edit/delete buttons out of alignment
- table remains usable on narrow screens
- truncation does not hide the product identity completely
- the solution avoids changing backend product-name validation unless a separate domain decision is made

### Wave P3.5 - Tests And Acceptance

#### P350 - Frontend Part Three Tests

Goal:

- prove the new pagination, cart, admin navigation, edit-route, language-selector, and formatting behavior.

Deliverables:

- catalog pagination feature/Livewire tests
- admin pagination tests for games, rarities, products, and orders
- add-to-cart redirect test from product detail
- cart page rendering test for current session cart items
- header cart indicator test
- dashboard card link tests
- dedicated admin edit page tests
- currency spacing assertion for storefront prices
- long product-name layout behavior covered by markup/class assertions where practical

Acceptance criteria:

- tests cover successful customer and admin paths
- tests prove route protection for new admin edit pages
- tests do not depend on remote images or external services
- existing Part One and Part Two behavior remains covered

#### P351 - Manual Browser QA

Goal:

- verify the final Part Three behavior in a browser-like flow

Manual scenarios:

- create more than 9 available products for one game and verify catalog pagination
- create more than 10 records in each admin list and verify pagination
- open a product detail page, click `go_to_cart`, and verify `/cart` contains the product
- verify the Cart menu signals the item count after add-to-cart
- remove or update cart items and verify totals
- click each admin dashboard stat card and verify the target page
- click edit for a game, rarity, and product and verify dedicated edit pages
- use a product with a very long name and verify admin product action buttons remain intact
- verify the language selector appears in the header
- if locale switching is active, switch between `en` and `pt-BR` and verify copy changes
- verify prices render with a space after `R$`

---

## Out Of Scope

- adding payment gateway integration
- adding automated delivery
- adding product variants
- adding wishlist behavior
- adding search or advanced filters beyond existing game selection
- replacing Blade/Livewire with React or Vue
- replacing admin CRUD with Filament or another admin package
- implementing a full translation management system
- changing backend cart storage away from the current session-backed cart

---

## Suggested Write Scope

- `routes/web.php`
- `app/Livewire/Storefront/Catalog.php`
- `app/Livewire/Storefront/ProductShow.php`
- `app/Livewire/Storefront/Cart.php`
- `resources/views/components/layouts/storefront.blade.php`
- `resources/views/components/storefront/product-card.blade.php`
- `resources/views/livewire/storefront/catalog.blade.php`
- `resources/views/livewire/storefront/product-show.blade.php`
- `resources/views/livewire/storefront/cart.blade.php`
- `app/Livewire/Admin/Dashboard.php`
- `app/Livewire/Admin/Games.php`
- `app/Livewire/Admin/Rarities.php`
- `app/Livewire/Admin/Products.php`
- `app/Livewire/Admin/Orders/Index.php`
- new admin edit Livewire components if introduced
- `resources/views/livewire/admin/dashboard.blade.php`
- `resources/views/livewire/admin/games.blade.php`
- `resources/views/livewire/admin/rarities.blade.php`
- `resources/views/livewire/admin/products.blade.php`
- `resources/views/livewire/admin/orders/index.blade.php`
- new admin edit Blade views if introduced
- `lang/en/storefront.php`
- `lang/pt_BR/storefront.php`
- `lang/en/admin.php`
- `lang/pt_BR/admin.php`
- `lang/en/shared.php`
- `lang/pt_BR/shared.php`
- `tests/Feature/Frontend/**`
- `tests/Feature/Admin/**`

Avoid changing backend domain Actions unless implementation reveals a real bug in the existing domain behavior.

---

## Validation

Run through the app container when available:

- `docker exec ecommerce-app-1 php artisan route:list`
- `docker exec ecommerce-app-1 php artisan test --filter=Frontend`
- `docker exec ecommerce-app-1 php artisan test --filter=Admin`
- `docker exec ecommerce-app-1 php artisan test --filter=Cart`
- `docker exec ecommerce-app-1 php artisan test --filter=Livewire`
- `docker exec ecommerce-app-1 vendor/bin/pint --test`

Manual verification:

- catalog displays at most 9 products per page
- admin lists display at most 10 records per page
- product detail add-to-cart redirects to `/cart` with the product in the cart
- Cart menu indicates current cart quantity
- cart page lists current cart items and handles empty state
- dashboard cards navigate to matching admin modules
- admin edit buttons open dedicated edit routes
- language selector appears with United States and Brazil options
- prices show a normal space after the currency symbol
- long product names do not break admin product action buttons
