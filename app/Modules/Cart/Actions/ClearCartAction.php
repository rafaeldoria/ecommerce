<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Contracts\CartStore;

class ClearCartAction
{
    public function __construct(
        private readonly CartStore $cartStore,
    ) {}

    public function execute(): void
    {
        $this->cartStore->clear();
    }
}
