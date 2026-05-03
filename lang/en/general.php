<?php

return [
    'app_name' => 'Game Items E-commerce',
    'architecture' => 'Modular monolith by domain with Actions as use cases.',
    'validation_baseline' => 'Each significant feature should cover a happy path and a failure or business-rule path.',
    'api' => [
        'admin' => [
            'auth' => [
                'logged_in' => 'Admin authenticated successfully.',
                'logged_out' => 'Admin logged out successfully.',
                'profile_retrieved' => 'Admin profile retrieved successfully.',
            ],
            'games' => [
                'listed' => 'Games retrieved successfully.',
                'created' => 'Game created successfully.',
                'retrieved' => 'Game retrieved successfully.',
                'updated' => 'Game updated successfully.',
                'deleted' => 'Game deleted successfully.',
            ],
            'rarities' => [
                'listed' => 'Rarities retrieved successfully.',
                'created' => 'Rarity created successfully.',
                'retrieved' => 'Rarity retrieved successfully.',
                'updated' => 'Rarity updated successfully.',
                'deleted' => 'Rarity deleted successfully.',
            ],
            'products' => [
                'listed' => 'Products retrieved successfully.',
                'created' => 'Product created successfully.',
                'retrieved' => 'Product retrieved successfully.',
                'updated' => 'Product updated successfully.',
                'deleted' => 'Product deleted successfully.',
            ],
            'orders' => [
                'listed' => 'Orders retrieved successfully.',
                'retrieved' => 'Order retrieved successfully.',
            ],
        ],
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
            'forbidden' => 'You are not allowed to access this resource.',
            'unauthenticated' => 'Authentication is required to access this resource.',
            'resource_not_found' => 'The requested resource was not found.',
            'validation_failed' => 'The given data was invalid.',
        ],
    ],
    'errors' => [
        'empty_cart' => 'Cart is empty.',
        'game_in_use' => 'The selected game cannot be deleted because products still reference it.',
        'rarity_in_use' => 'The selected rarity cannot be deleted because products still reference it.',
        'invalid_admin_credentials' => 'Admin credentials are invalid.',
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
        'payment_configuration_missing' => 'Configure Mercado Pago test credentials before starting checkout.',
        'payment_configuration_invalid' => 'Mercado Pago payment environment configuration is invalid.',
        'payment_preference_failed' => 'Mercado Pago checkout could not be started. Please try again shortly.',
        'product_image_storage_failed' => 'Product image could not be stored.',
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
