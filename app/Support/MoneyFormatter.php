<?php

namespace App\Support;

use Illuminate\Support\Number;

class MoneyFormatter
{
    public static function brlFromCents(int $cents): string
    {
        $formatted = Number::currency($cents / 100, in: 'BRL', locale: app()->getLocale());

        return preg_replace('/^(R\$)\s*/', '$1 ', $formatted) ?? $formatted;
    }
}
