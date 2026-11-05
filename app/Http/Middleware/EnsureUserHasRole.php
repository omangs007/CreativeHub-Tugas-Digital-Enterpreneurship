<?php

namespace App\Http\Middleware;

use App\Enums\RoleName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemakaian: ->middleware('role:freelancer') atau ->middleware('role:freelancer,admin').
 * Pasang setelah middleware `auth`.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = $user !== null && collect($roles)
            ->contains(fn (string $role) => $user->hasRole(RoleName::from($role)));

        abort_unless($allowed, Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
