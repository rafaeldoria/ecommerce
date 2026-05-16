<?php

namespace App\Http\Middleware;

use App\Modules\Admin\Exceptions\AdminMfaSetupRequired;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminApiMfaIsConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('security.admin_mfa.required', false)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user !== null && $user->hasConfirmedMfa()) {
            return $next($request);
        }

        throw new AdminMfaSetupRequired(__('general.errors.mfa_setup_required'));
    }
}
