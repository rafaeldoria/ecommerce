<?php

namespace Tests\Unit\Security;

use App\Support\TrustedProxies;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    #[Test]
    public function it_trusts_all_proxies_only_for_local_like_environments_by_default(): void
    {
        $this->assertSame('*', TrustedProxies::resolve(null, 'local'));
        $this->assertSame('*', TrustedProxies::resolve(null, 'testing'));
        $this->assertNull(TrustedProxies::resolve(null, 'production'));
    }

    #[Test]
    public function it_parses_configured_proxy_lists(): void
    {
        $this->assertSame(
            ['10.0.0.1', '192.168.0.0/16'],
            TrustedProxies::resolve('10.0.0.1, 192.168.0.0/16', 'production'),
        );
    }
}
