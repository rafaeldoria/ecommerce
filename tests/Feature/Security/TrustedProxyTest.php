<?php

namespace Tests\Feature\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    #[Test]
    public function forwarded_client_ip_headers_are_not_trusted_by_default(): void
    {
        Route::get('/_test/request-ip', fn (Request $request) => response()->json([
            'ip' => $request->ip(),
        ]));

        $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders(['X-Forwarded-For' => '198.51.100.20'])
            ->get('/_test/request-ip')
            ->assertOk()
            ->assertJsonPath('ip', '203.0.113.10');
    }
}
