<?php

namespace App\Support;

class TrustedProxies
{
    /**
     * @return array<int, string>|string|null
     */
    public static function resolve(mixed $configured, string $environment): array|string|null
    {
        if (is_string($configured)) {
            $configured = trim($configured);

            if ($configured === '') {
                $configured = null;
            }
        }

        if ($configured === null && in_array($environment, ['local', 'testing'], true)) {
            return '*';
        }

        if (is_string($configured)) {
            if ($configured === '*') {
                return '*';
            }

            return array_values(array_filter(
                array_map('trim', explode(',', $configured)),
                static fn (string $proxy): bool => $proxy !== '',
            ));
        }

        return is_array($configured) ? $configured : null;
    }
}
