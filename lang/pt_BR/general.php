<?php

return [
    'app_name' => 'Game Items E-commerce',
    'architecture' => 'Monolito modular por dominio com Actions como casos de uso.',
    'validation_baseline' => 'Cada funcionalidade significativa deve cobrir um caminho feliz e um caminho de falha ou regra de negocio.',
    'api' => [
        'admin' => [
            'auth' => [
                'logged_in' => 'Admin autenticado com sucesso.',
                'logged_out' => 'Admin desconectado com sucesso.',
                'profile_retrieved' => 'Perfil do admin recuperado com sucesso.',
            ],
            'games' => [
                'listed' => 'Jogos recuperados com sucesso.',
                'created' => 'Jogo criado com sucesso.',
                'retrieved' => 'Jogo recuperado com sucesso.',
                'updated' => 'Jogo atualizado com sucesso.',
                'deleted' => 'Jogo removido com sucesso.',
            ],
            'rarities' => [
                'listed' => 'Raridades recuperadas com sucesso.',
                'created' => 'Raridade criada com sucesso.',
                'retrieved' => 'Raridade recuperada com sucesso.',
                'updated' => 'Raridade atualizada com sucesso.',
                'deleted' => 'Raridade removida com sucesso.',
            ],
            'products' => [
                'listed' => 'Produtos recuperados com sucesso.',
                'created' => 'Produto criado com sucesso.',
                'retrieved' => 'Produto recuperado com sucesso.',
                'updated' => 'Produto atualizado com sucesso.',
                'deleted' => 'Produto removido com sucesso.',
            ],
            'orders' => [
                'listed' => 'Pedidos recuperados com sucesso.',
                'retrieved' => 'Pedido recuperado com sucesso.',
            ],
        ],
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
            'forbidden' => 'Voce nao tem permissao para acessar este recurso.',
            'unauthenticated' => 'Autenticacao e obrigatoria para acessar este recurso.',
            'resource_not_found' => 'O recurso solicitado nao foi encontrado.',
            'validation_failed' => 'Os dados enviados sao invalidos.',
        ],
    ],
    'errors' => [
        'empty_cart' => 'O carrinho esta vazio.',
        'game_in_use' => 'O jogo selecionado nao pode ser removido porque ainda existem produtos vinculados a ele.',
        'rarity_in_use' => 'A raridade selecionada nao pode ser removida porque ainda existem produtos vinculados a ela.',
        'invalid_admin_credentials' => 'As credenciais de admin sao invalidas.',
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
        'product_image_storage_failed' => 'Nao foi possivel salvar a imagem do produto.',
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
