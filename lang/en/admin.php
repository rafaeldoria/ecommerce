<?php

return [
    'brand' => [
        'name' => 'GR-Shop Admin',
    ],
    'metadata' => [
        'default_title' => 'Admin | GR-Shop',
    ],
    'navigation' => [
        'primary' => 'Primary admin navigation',
        'dashboard' => 'Dashboard',
        'games' => 'Games',
        'rarities' => 'Rarities',
        'products' => 'Products',
        'orders' => 'Orders',
        'logout' => 'Logout',
    ],
    'auth' => [
        'login_title' => 'Admin login',
        'eyebrow' => 'Operational access',
        'summary' => 'Use the existing admin backend credentials to enter the web panel and manage the storefront operation.',
        'panel_title' => 'Admin panel',
        'panel_text' => 'Access games, rarities, products, and order follow-up from one separate interface.',
        'security_title' => 'Security',
        'security_text' => 'Only admin users can authenticate here. Public storefront navigation stays completely separate.',
        'login_label' => 'Username or email',
        'login_placeholder' => 'admin or admin@example.com',
        'password_label' => 'Password',
        'password_placeholder' => 'Enter your password',
        'submit' => 'Enter admin panel',
        'throttled' => 'Too many attempts. Please wait a minute and try again.',
    ],
    'dashboard' => [
        'title' => 'Admin dashboard',
        'summary' => 'This admin surface is intentionally simpler than the storefront, but it now exposes the operational data needed to run catalog and order follow-up.',
    ],
    'games' => [
        'title' => 'Games',
        'summary' => 'Live list of games currently registered in the catalog.',
    ],
    'rarities' => [
        'title' => 'Rarities',
        'summary' => 'Live list of rarity labels available for product assignment.',
    ],
    'products' => [
        'title' => 'Products',
        'summary' => 'Visible inventory overview with image, game, rarity, and stock information.',
    ],
    'orders' => [
        'title' => 'Orders',
        'summary' => 'Read created orders and open details for manual buyer follow-up.',
        'detail_title' => 'Order detail',
        'detail_summary' => 'This view keeps buyer contact and ordered items readable for the manual fulfillment workflow.',
        'total_label' => 'Order total',
        'contact_block_title' => 'Buyer contact',
        'items_block_title' => 'Ordered items',
        'whatsapp_label' => 'WhatsApp',
        'quantity_label' => 'Qty: :count',
    ],
    'tables' => [
        'id' => 'ID',
        'name' => 'Name',
        'product' => 'Product',
        'game' => 'Game',
        'rarity' => 'Rarity',
        'stock' => 'Stock',
        'order' => 'Order',
        'contact' => 'Contact',
        'status' => 'Status',
        'items' => 'Items',
    ],
    'shared' => [
        'empty_state' => 'Nothing to show yet in this module.',
    ],
];
