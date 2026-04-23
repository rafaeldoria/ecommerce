# Frontend Project Overview

## Project Name
GR-Shop

## Objective
Build a trustworthy, mobile-first storefront for selling game-related items while preserving the current MVP business model of manual fulfillment.

The frontend must help the business look credible, fast, and easy to understand without introducing a large SPA architecture in the first phase.

It must also define a separate admin frontend surface for staff operations, intentionally simpler than the public website and clearly isolated from it.

---

## Frontend Scope

The frontend scope covers the website experience that sits on top of the existing Laravel backend domains and APIs.

It includes:

- the public storefront experience
- the admin web interface
- localization-ready UI copy
- responsive behavior for modern smartphones, tablets, and desktop
- a visual direction strong enough to become the future brand basis for GR-Shop

It does not replace the backend source of truth for catalog, cart, orders, or admin authentication.

---

## Public Storefront Goal

The public storefront must help buyers:

- discover products quickly
- understand what the store sells without confusion
- trust the store enough to continue to checkout
- add items to the cart or buy directly
- provide the minimum contact data required for the current manual purchase flow

The storefront should feel premium and game-adjacent, but still operational and easy to use.

The intended style for this phase is:

- premium store first
- not a generic marketplace clone
- visually strong without becoming noisy
- dark, non-white visual base
- brand-led use of teal and amber as the main color anchors

---

## Admin Frontend Goal

The admin frontend must provide a clean operational interface for staff to manage the MVP business flow.

It must remain fully separate from the public storefront in navigation, layout, tone, and route boundary.

The admin frontend should optimize for:

- fast login
- direct access to operational data
- simple CRUD screens
- low visual complexity
- stable workflows for catalog and order follow-up

It is not intended to become a branded marketing experience.

---

## Public MVP Pages And Flows

The public frontend MVP should include the following pages and customer paths.

### Core Pages

- Home page
- Catalog listing page
- Product detail page
- Cart page
- Checkout page
- About page
- Contact page
- FAQ or purchase-help page

### Main Customer Flow

- User lands on the home page
- User explores highlighted games, products, and trust signals
- User browses the catalog
- User filters products by the backend-supported catalog dimensions
- User opens a product detail page
- User adds the product to the cart or uses direct buy
- User reaches checkout
- User provides:
  - email
  - WhatsApp number
- User submits the order
- User sees a confirmation message explaining that the team will contact them after purchase confirmation

### Cart And Direct-Buy Expectations

- The cart experience must support the current backend session-based flow
- The website must expose a clear cart entry point
- The user must be able to update quantity and remove items
- The user must also have a visible buy-now path that jumps directly toward checkout

---

## Admin MVP Pages And Flows

The admin frontend MVP should include the following pages and operational paths.

### Core Pages

- Admin login
- Admin dashboard
- Games CRUD screens
- Rarities CRUD screens
- Products CRUD screens
- Orders list
- Order detail

### Main Admin Flow

- Admin reaches `/admin/login`
- Admin authenticates with the existing backend auth boundary
- Admin lands on a simple dashboard with shortcuts to main modules
- Admin manages games, rarities, and products
- Admin opens created orders
- Admin reads buyer email, WhatsApp, and order items
- Admin uses this data for manual fulfillment follow-up

---

## Checkout Scope For Frontend MVP

The checkout UI remains intentionally minimal in this phase.

It must:

- collect only the fields already required by the backend MVP:
  - email
  - WhatsApp number
- make the process feel deliberate and trustworthy
- confirm what happens next in plain language

It must not:

- present a fake integrated payment gateway
- imply that payment capture is already automated
- promise instant or automatic delivery

The confirmation step should explicitly explain that the team will contact the buyer after purchase confirmation.

---

## Trust And Content Requirements

The storefront should include trust-building content, but it must remain honest about the current manual operational model.

### Required Trust Elements

- short explanation of how the purchase flow works
- contact entry points
- concise About content
- concise FAQ or help content
- clear mentions of support and follow-up
- at most 3 placeholder testimonials

### Testimonial Rules

- testimonials are temporary placeholders only
- they must sound natural and spontaneous
- they must avoid exaggerated promises
- they must not claim unsupported capabilities such as instant automated delivery
- the docs should clearly treat them as internal placeholder content for implementation

---

## Localization Direction

The frontend launch language is:

- `pt-BR`

The first additional supported language planned from the beginning is:

- `en`

The website should therefore be documented and implemented with a localization-ready structure instead of hard-coded single-language copy.

This applies to:

- navigation
- buttons
- headings
- trust sections
- cart and checkout copy
- admin UI copy
- metadata

---

## Responsive Direction

The frontend must be designed mobile-first.

Primary target:

- modern smartphones

Secondary targets:

- tablets
- desktop and large desktop

The expectation is not to optimize for very old phone models or outdated browser constraints.

The layout should remain usable and visually strong on:

- narrow mobile widths
- one-hand touch interactions
- stacked card layouts
- desktop catalog browsing

---

## Design Direction

The visual system should establish a recognizable brand base for GR-Shop even before the logo exists.

### Brand Direction

- primary colors: teal and amber
- dark, non-white surfaces
- premium-game-adjacent visual language
- stronger contrast than a neutral SaaS layout
- enough consistency to later support social posts and brand materials

### Logo Placeholder Direction

- there is no final logo yet
- the UI should reserve a flexible logo slot
- use the name `GR-Shop` as the current logotype
- the future logo must be able to drop into the layout without requiring structural redesign

---

## Design References

The following references should guide study and mood, not direct imitation.

### User-Provided References

- `cs.money`
- `scorpion-shop.com.br`

### Additional Reference Patterns

- DMarket for high-density inventory framing and trust metrics:
  - https://dmarket.com/
- SkinBaron for marketplace utility framing and savings language:
  - https://skinbaron.de/en
- Skinport 3D viewer/features for product-inspection inspiration and richer product presentation:
  - https://3d.skinport.com/features

These references should inform:

- trust placement
- product-card clarity
- catalog density choices
- premium gaming atmosphere
- detail-page merchandising patterns

They must not lead to copied branding, copied layouts, or copied content.

---

## Relationship To Current Backend

The frontend must respect the current backend reality.

Important constraints already true today:

- catalog listing exists
- cart is backend-managed and session-based
- order creation depends on the current session cart
- admin auth and admin APIs already exist
- payment processing is still deferred

This means the frontend should be designed around the existing backend boundaries instead of pretending the application is already a headless commerce platform.

---

## Future-Friendly Direction

The project remains Livewire-first for this phase, but the frontend must be shaped so that future extraction to React or Vue is not unnecessarily expensive.

That means the frontend should favor:

- thin UI entry components
- stable route and data boundaries
- server-owned business logic
- API-friendly contracts where appropriate
- minimal coupling between domain rules and view-only behavior

The goal is not to implement React or Vue now.
The goal is to avoid making that future move painful.

---

## Non-Goals For This Phase

- final logo creation
- full design system package for every future scenario
- dark/light mode toggle
- real testimonials
- payment gateway UI
- customer account area
- wishlist
- marketplace seller features
- React implementation now
- Vue implementation now

---

## Success Criteria

- public users can understand the store quickly on mobile and desktop
- the storefront feels trustworthy and visually intentional
- catalog, cart, direct-buy, and checkout flows are all clearly represented
- checkout communicates the current manual follow-up model honestly
- the website is prepared for `pt-BR` and `en`
- the admin frontend is fully separate from the public web experience
- the visual system gives GR-Shop a recognizable non-white brand direction

---

## Notes

- The public storefront should feel premium before it feels large.
- The admin frontend should feel operational before it feels branded.
- Manual fulfillment is a product truth and must be reflected clearly in the copy and flow.
- The frontend should improve trust and conversion without inventing unsupported capabilities.
