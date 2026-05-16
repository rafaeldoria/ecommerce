<?php

namespace App\Modules\Admin\DTOs;

class AdminMfaSetupResult
{
    /**
     * @param  array<int, string>  $recoveryCodes
     */
    public function __construct(
        public readonly string $qrCodeSvg,
        public readonly string $qrCodeUrl,
        public readonly array $recoveryCodes,
    ) {}
}
