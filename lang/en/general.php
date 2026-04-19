<?php

return [
    'app_name' => 'Game Items E-commerce',
    'architecture' => 'Modular monolith by domain with Actions as use cases.',
    'validation_baseline' => 'Each significant feature should cover a happy path and a failure or business-rule path.',
    'api' => [
        'catalog' => [
            'products_listed' => 'Catalog products retrieved successfully.',
        ],
        'cart' => [
            'retrieved' => 'Cart retrieved successfully.',
            'item_added' => 'Cart item added successfully.',
            'item_updated' => 'Cart item updated successfully.',
            'item_removed' => 'Cart item removed successfully.',
        ],
        'orders' => [
            'created' => 'Order created successfully.',
        ],
        'errors' => [
            'resource_not_found' => 'The requested resource was not found.',
        ],
    ],
    'errors' => [
        'empty_cart' => 'Cart is empty.',
        'invalid_cart_quantity' => 'Cart quantity must be greater than zero.',
        'invalid_product_reference' => 'The selected product does not exist.',
        'invalid_game_reference' => 'The selected game does not exist.',
        'invalid_rarity_reference' => 'The selected rarity does not exist.',
        'invalid_product_price' => 'Product price must be zero or greater.',
        'invalid_product_quantity' => 'Product quantity must be zero or greater.',
        'cart_product_unavailable' => 'A cart product is no longer available.',
        'insufficient_stock' => 'Insufficient stock for product [:product_id].',
        'invalid_order_email' => 'Order email must be valid.',
        'invalid_order_whatsapp' => 'WhatsApp number must contain between 10 and 15 digits.',
        'payment_processing_deferred' => 'Payment processing is intentionally deferred in this MVP.',
    ],
    'notifications' => [
        'internal_order_created' => [
            'subject' => 'New order #:order_id awaiting manual fulfillment',
            'intro' => 'A new order was created and is ready for internal review.',
            'order_status' => 'Current status: :status',
            'buyer_email' => 'Buyer email: :email',
            'buyer_whatsapp' => 'Buyer WhatsApp: :whatsapp',
            'order_total' => 'Order total in cents: :amount',
            'item_line' => 'Item :product | quantity: :quantity | unit price in cents: :price',
        ],
    ],
    'logs' => [
        'internal_order_notification_fallback' => 'Internal order notification fallback recorded.',
        'queued_order_notification_failed' => 'Queued internal order notification failed.',
    ],
];
