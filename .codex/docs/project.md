# Project Overview

## Project Name
Game Items E-commerce

## Objective
Build a simple e-commerce platform focused on selling in-game items, initially for Dota 2 and Counter-Strike.

The goal is to validate a lean business model with fast delivery and simple user interaction, prioritizing speed over complexity in the first version (MVP).

---

## MVP Scope

The MVP includes the minimum backend and product capabilities required to operate the business with manual fulfillment.

### Customer Flow
- User accesses the website
- User browses products
- Products are organized by:
  - Game
  - Rarity
- User adds items to the cart
- User reaches checkout
- Checkout only collects the minimum buyer data required at this stage:
  - Email
  - WhatsApp number
- Order is created after this minimal data is provided

### Admin Flow
- Admin manages games
- Admin manages rarities
- Admin manages products
- Admin manages product stock quantity
- Admin views created orders
- Admin uses buyer contact data to support manual fulfillment

### Order, Inventory, and Delivery
- Orders are **not automatically fulfilled**
- Delivery is handled manually by an admin after order creation
- Stock is managed by admins through product quantity
- Stock is decremented when an order is created
- System sends purchase details to the internal team

### Checkout Scope For MVP
- Checkout is only a minimal handoff step in this phase
- It is limited to collecting:
  - Email
  - WhatsApp number
- It does not define a full checkout domain yet

### Payments
- Supported methods for the business direction:
  - PIX
  - Credit Card
- Payment processing will be implemented **later**
- Payment flow is intentionally deferred from the MVP implementation

---

## Catalog Rules For MVP

- A product belongs to a `game`
- A product belongs to a `rarity`
- `rarity` is the only product classification dimension besides `game` in the MVP
- A separate business `category` is not needed in the MVP at this moment
- Product stock is represented by `quantity`
- Stock accuracy is the responsibility of admins in this phase

---

## Admin Capabilities In MVP

Admin users can:
- Manage games
- Manage rarities
- Manage products
- Manage product quantity
- View orders
- View buyer contact data required for manual fulfillment

---

## Future Scope (Post-MVP)

- Automated delivery of in-game items after payment confirmation
- Full payment gateway integration
- Outbox pattern implementation for reliability
- Stock reservation improvements
- Stock automation
- Improved order tracking
- Notification system
- Expanded checkout design

---

## Core Domains

- Catalog
- Cart
- Orders
- Payments
- Admin

---

## Main Entities

### Product
- `id`
- `name`
- `url_img`
- `quantity`
- `price` stored as integer in cents
- `game_id`
- `rarity_id`
- `created_at`
- `updated_at`
- `deleted_at` for soft delete

### Rarity
- `id`
- `name`
- `created_at`
- `updated_at`
- `deleted_at` for soft delete

### Game
- `id`
- `name`
- `created_at`
- `updated_at`
- `deleted_at` for soft delete

### Cart
- Represents the products selected by the buyer before order creation

### Order
- Stores the buyer contact data required by the MVP:
  - `email`
  - `whatsapp`
- Stores the purchased items required for manual fulfillment

### Payment
- Exists as a future-oriented domain boundary for asynchronous payment work

---

## Tech Stack

- Backend: Laravel 13
- Frontend: Livewire
- Database: PostgreSQL
- Infrastructure: Docker
- Architecture: Modular Monolith by Domain with Actions (Use Cases)

---

## Architecture Overview

The system follows a **modular monolith architecture organized by domain**.

Each module (Catalog, Cart, Orders, Payments, Admin) contains its own:
- Actions (use cases)
- Models
- Queries
- Policies
- Integrations

### Key Principles

- Controllers and Livewire components are thin
- Business logic lives in Actions
- Models handle persistence only
- Asynchronous processes are handled via Jobs/Events when needed
- External integrations are isolated via gateways

---

## Non-Goals (for MVP)

- No microservices
- No automated delivery
- No full payment gateway integration
- No outbox implementation in this phase
- No advanced stock automation
- No complex checkout design
- No marketplace/multi-seller support

---

## Success Criteria

- User can browse products by game and rarity
- User can add items to cart
- User can reach a minimal checkout step
- User can create an order by providing email and WhatsApp number
- Admin can manage catalog data and stock quantity
- Admin receives enough order information to perform manual fulfillment reliably

---

## Notes

- The system prioritizes simplicity and speed of delivery
- The MVP intentionally keeps checkout minimal and operational
- The architecture is designed to support future evolution without early overengineering
