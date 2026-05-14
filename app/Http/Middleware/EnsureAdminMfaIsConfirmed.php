<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMfaIsConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!config('security.admin_mfa.required', false)) {
            return $next($request);
        }

        if ($user === null || $user->hasConfirmedMfa()) {
            return $next($request);
        }

        if ($request->routeIs('admin.security', 'admin.logout')) {
            return $next($request);
        }

        return redirect()->route('admin.security');
    }
}
