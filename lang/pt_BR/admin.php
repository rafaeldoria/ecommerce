<?php

return [
    'brand' => [
        'name' => 'GR-Shop Admin',
    ],
    'metadata' => [
        'default_title' => 'Admin | GR-Shop',
    ],
    'navigation' => [
        'primary' => 'Navegacao principal do admin',
        'dashboard' => 'Dashboard',
        'games' => 'Jogos',
        'rarities' => 'Raridades',
        'products' => 'Produtos',
        'orders' => 'Pedidos',
        'logout' => 'Sair',
    ],
    'auth' => [
        'login_title' => 'Login admin',
        'eyebrow' => 'Acesso operacional',
        'summary' => 'Use as credenciais admin ja existentes no backend para entrar no painel web e operar a loja.',
        'panel_title' => 'Painel admin',
        'panel_text' => 'Acesse jogos, raridades, produtos e o acompanhamento de pedidos em uma interface separada.',
        'security_title' => 'Seguranca',
        'security_text' => 'Apenas usuarios admin podem autenticar aqui. A navegacao publica continua totalmente separada.',
        'login_label' => 'Usuario ou email',
        'login_placeholder' => 'admin ou admin@example.com',
        'password_label' => 'Senha',
        'password_placeholder' => 'Digite sua senha',
        'submit' => 'Entrar no painel',
        'throttled' => 'Muitas tentativas. Aguarde um minuto e tente novamente.',
    ],
    'dashboard' => [
        'title' => 'Dashboard admin',
        'summary' => 'Esta superficie admin e intencionalmente mais simples que a storefront, mas agora expoe os dados operacionais necessarios para catalogo e follow-up dos pedidos.',
    ],
    'games' => [
        'title' => 'Jogos',
        'summary' => 'Lista viva dos jogos registrados atualmente no catalogo.',
    ],
    'rarities' => [
        'title' => 'Raridades',
        'summary' => 'Lista viva dos rotulos de raridade disponiveis para os produtos.',
    ],
    'products' => [
        'title' => 'Produtos',
        'summary' => 'Visao operacional do inventario com imagem, game, raridade e estoque.',
    ],
    'orders' => [
        'title' => 'Pedidos',
        'summary' => 'Leia os pedidos criados e abra detalhes para o follow-up manual com o comprador.',
        'detail_title' => 'Detalhe do pedido',
        'detail_summary' => 'Esta tela deixa o contato do comprador e os itens do pedido legiveis para o fluxo manual de fulfillment.',
        'total_label' => 'Total do pedido',
        'contact_block_title' => 'Contato do comprador',
        'items_block_title' => 'Itens do pedido',
        'whatsapp_label' => 'WhatsApp',
        'quantity_label' => 'Qtd: :count',
    ],
    'tables' => [
        'id' => 'ID',
        'name' => 'Nome',
        'product' => 'Produto',
        'game' => 'Game',
        'rarity' => 'Raridade',
        'stock' => 'Estoque',
        'order' => 'Pedido',
        'contact' => 'Contato',
        'status' => 'Status',
        'items' => 'Itens',
    ],
    'shared' => [
        'empty_state' => 'Nada para mostrar ainda neste modulo.',
    ],
];
