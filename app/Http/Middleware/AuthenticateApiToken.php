<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Static token authentication for the REST API. Expects the token configured in
 * config('api.token') as bearer token. Deliberately stateless (no Sanctum, no DB table).
 */
class AuthenticateApiToken {
    public function handle(Request $request, Closure $next): Response {
        $expected = (string) config('api.token');
        $provided = (string) $request->bearerToken();

        // Fail closed: no configured server token or a missing/wrong bearer -> 401.
        // hash_equals compares in constant time (timing-attack protection).
        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
