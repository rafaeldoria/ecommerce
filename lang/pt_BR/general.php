<?php

return [
    'app_name' => 'Game Items E-commerce',
    'architecture' => 'Monolito modular por dominio com Actions como casos de uso.',
    'validation_baseline' => 'Cada funcionalidade significativa deve cobrir um caminho feliz e um caminho de falha ou regra de negocio.',
    'api' => [
        'catalog' => [
            'products_listed' => 'Produtos do catalogo recuperados com sucesso.',
        ],
        'cart' => [
            'retrieved' => 'Carrinho recuperado com sucesso.',
            'item_added' => 'Item do carrinho adicionado com sucesso.',
            'item_updated' => 'Item do carrinho atualizado com sucesso.',
            'item_removed' => 'Item do carrinho removido com sucesso.',
        ],
        'orders' => [
            'created' => 'Pedido criado com sucesso.',
        ],
        'errors' => [
            'resource_not_found' => 'O recurso solicitado nao foi encontrado.',
        ],
    ],
    'errors' => [
        'empty_cart' => 'O carrinho esta vazio.',
        'invalid_cart_quantity' => 'A quantidade do carrinho deve ser maior que zero.',
        'invalid_product_reference' => 'O produto selecionado nao existe.',
        'invalid_game_reference' => 'O jogo selecionado nao existe.',
        'invalid_rarity_reference' => 'A raridade selecionada nao existe.',
        'invalid_product_price' => 'O preco do produto deve ser zero ou maior.',
        'invalid_product_quantity' => 'A quantidade do produto deve ser zero ou maior.',
        'cart_product_unavailable' => 'Um produto do carrinho nao esta mais disponivel.',
        'insufficient_stock' => 'Estoque insuficiente para o produto [:product_id].',
        'invalid_order_email' => 'O email do pedido deve ser valido.',
        'invalid_order_whatsapp' => 'O numero de WhatsApp deve conter entre 10 e 15 digitos.',
        'payment_processing_deferred' => 'O processamento de pagamento esta intencionalmente adiado neste MVP.',
    ],
    'notifications' => [
        'internal_order_created' => [
            'subject' => 'Novo pedido #:order_id aguardando fulfill manual',
            'intro' => 'Um novo pedido foi criado e esta pronto para revisao interna.',
            'order_status' => 'Status atual: :status',
            'buyer_email' => 'Email do comprador: :email',
            'buyer_whatsapp' => 'WhatsApp do comprador: :whatsapp',
            'order_total' => 'Total do pedido em centavos: :amount',
            'item_line' => 'Item :product | quantidade: :quantity | preco unitario em centavos: :price',
        ],
    ],
    'logs' => [
        'internal_order_notification_fallback' => 'Fallback de notificacao interna do pedido registrado.',
        'queued_order_notification_failed' => 'A notificacao interna enfileirada do pedido falhou.',
    ],
];
