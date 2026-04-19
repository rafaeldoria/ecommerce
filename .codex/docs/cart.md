# Cart Overview

## Purpose

This document explains how the cart works today in the current MVP implementation.

It describes:

- what the cart does
- what it does not do
- how it is exposed through the API
- how it connects to order creation
- the current technical limitations

---

## Current Model

The cart is a backend-managed, session-based cart.

It is not persisted as a dedicated cart table in the database.
It is not owned by an authenticated user.
It is not stored as the source of truth in the frontend.

Today, the current cart is stored in the Laravel session through `SessionCartStore`.

The implementation uses:

- `CartStore` as the contract
- `SessionCartStore` as the current concrete store
- Cart Actions to add, update, remove, read, and clear cart items

At runtime, the application binds `CartStore` to `SessionCartStore`, so all cart reads and writes go through the session-backed implementation.

---

## How It Works Today

### Storage

The cart is stored in the backend session under the key `cart.items`.

The session is handled by Laravel and identified through the session cookie sent by the client.

This means:

- the backend holds the official cart state
- the frontend must keep sending the same session cookie
- if the session changes, the cart changes with it

### Cart Item Shape

Each cart line currently stores only the minimum data needed for the MVP order flow:

- `product_id`
- `quantity`
- `unit_price`
- `product_name`

This data is enough for:

- showing the current cart
- calculating cart totals
- creating an order from the current cart

### Main Behavior

The cart currently supports these operations:

- add a product
- increase quantity when the same product is added again
- update the quantity of an existing product
- remove a product
- read the current cart
- clear the cart after successful order creation

When a product is added:

- the backend validates that quantity is greater than zero
- the backend confirms that the product still exists and is not soft deleted
- the backend stores product name and current price in the cart line

When an order is created:

- the order flow reads the current cart from the same cart store
- validates that the cart is not empty
- validates line quantities again
- reloads products from the catalog inside a transaction
- validates stock availability
- creates the order and order items
- decrements stock
- clears the cart

---

## Current API Endpoints

The current API exposes the following cart-related endpoints:

- `GET /api/cart`
- `POST /api/cart/items`
- `PATCH /api/cart/items/{product}`
- `DELETE /api/cart/items/{product}`
- `POST /api/orders`

Important detail:

Although these routes live in `routes/api.php`, the cart and order routes are wrapped with the `web` middleware group.

That means the cart flow currently depends on Laravel web behavior, especially session and cookies.

So today:

- catalog listing is stateless
- cart is not stateless
- order creation is not stateless because it depends on the current cart session

---

## What The Cart Does

The cart currently does these things well for the MVP:

- keeps the current shopper selection on the backend
- avoids trusting price data sent by the client
- merges repeated adds for the same product
- exposes a simple JSON payload for cart visualization
- supports the current order creation flow
- stays thin at the controller layer and keeps logic in Actions

---

## What The Cart Does Not Do

The current cart intentionally does not do the following:

- it does not use a dedicated database table for carts
- it does not use a token-based cart identifier
- it does not support authenticated user ownership
- it does not support cart sharing across devices
- it does not guarantee persistence beyond the current session lifecycle
- it does not reserve stock before order creation
- it does not support coupons, discounts, shipping, or taxes
- it does not support checkout pricing rules beyond the stored unit price
- it does not expose a fully stateless API contract
- it does not support payment orchestration

---

## Functional Limits

The current design is simple and valid for the MVP, but it has clear limits:

- the cart is tied to Laravel session state
- the frontend must preserve and resend the session cookie
- non-browser clients need session handling to work correctly
- mobile or third-party consumers would need extra adaptation
- the order flow cannot be independent from the cart store because it reads the current cart implicitly

This is why the current implementation is best described as:

an API-shaped cart flow with web-session behavior behind it.

---

## Why It Was Built This Way

This approach matches the current MVP goals:

- keep the flow simple
- keep the backend as the source of truth
- avoid early complexity
- allow order creation from the current cart without introducing a larger persistence model

It is a good short-term fit for:

- one frontend
- one backend
- fast MVP delivery

---

## Future Evolution Direction

If the project needs a more independent API, the cart will likely evolve in one of these directions:

1. replace session storage with a token-based cart store persisted by database or cache
2. stop depending on a backend-stored cart for order creation and receive order items directly in the order request

Today, neither of those evolutions is implemented yet.

---

## Short Summary

The cart today is a session-based backend cart used by the current MVP purchase flow.

It can add, update, remove, show, and clear items.
It supports order creation.
It does not yet provide a stateless, token-based, or user-owned cart model.
