<?php

namespace App\Modules\Cart\Actions;

use App\Modules\Cart\Contracts\CartStore;

class GetCurrentCartAction
{
    public function __construct(
        private readonly CartStore $cartStore,
    ) {}

    public function execute(): array
    {
        return $this->cartStore->all();
    }
}
