<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coarse role gate: `->middleware('role:admin,registrar')`.
 *
 * Per-record ownership (faculty seeing only their own courses, students only
 * their own records) is enforced by the policies, not here.
 */
class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // The 403 is logged centrally in bootstrap/app.php so that policy
        // denials and middleware denials are recorded the same way.
        abort_unless($user && $user->hasRole(...$roles), 403);

        return $next($request);
    }
}
