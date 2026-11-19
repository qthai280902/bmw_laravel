<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user?->isAdmin()) {
            abort(403, 'Unauthorized access to BMW Admin Portal.');
        }

        if ($roles !== [] && ! in_array($user->normalizedRole(), $roles, true)) {
            abort(403, 'Unauthorized access to this BMW Admin section.');
        }

        return $next($request);
    }
}
