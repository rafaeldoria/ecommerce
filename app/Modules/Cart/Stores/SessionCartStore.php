<?php

namespace App\Modules\Cart\Stores;

use App\Modules\Cart\Contracts\CartStore;
use Illuminate\Contracts\Session\Session;

class SessionCartStore implements CartStore
{
    private const SESSION_KEY = 'cart.items';

    public function __construct(
        private readonly Session $session,
    ) {}

    public function all(): array
    {
        return array_values($this->session->get(self::SESSION_KEY, []));
    }

    public function put(array $items): void
    {
        $normalized = [];

        foreach ($items as $item) {
            $normalized[(string) $item['product_id']] = $item;
        }

        $this->session->put(self::SESSION_KEY, $normalized);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }
}
