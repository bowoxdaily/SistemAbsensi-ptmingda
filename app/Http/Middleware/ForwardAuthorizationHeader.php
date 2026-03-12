<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * On Apache + PHP-FPM/CGI (cgi-fcgi, common on cPanel), the Authorization
 * header is stripped by Apache before it reaches PHP. This middleware recovers
 * the Bearer token from multiple fallback locations:
 *
 *  1. Standard $_SERVER vars (works on mod_php / some FPM configs)
 *  2. X-Authorization header  (Apache passes custom headers through; client
 *     can use this instead of Authorization on cPanel hosts)
 *  3. X-Api-Token header (same reason as above)
 *
 * External clients that hit 401 on cPanel should switch to:
 *   X-Authorization: Bearer <token>
 */
class ForwardAuthorizationHeader
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->headers->has('Authorization')) {
            $auth = $this->resolveAuthHeader($request);

            if ($auth) {
                $request->headers->set('Authorization', $auth);
            }
        }

        return $next($request);
    }

    private function resolveAuthHeader(Request $request): ?string
    {
        // ── Strategy 1: $_SERVER vars (mod_rewrite E= passthrough) ──────────────
        $candidates = [
            'HTTP_AUTHORIZATION',
            'REDIRECT_HTTP_AUTHORIZATION',
            'REDIRECT_REDIRECT_HTTP_AUTHORIZATION',
            'HTTP_HTTP_AUTHORIZATION',
        ];

        foreach ($candidates as $key) {
            if (! empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        // ── Strategy 2: getallheaders() (mod_php / some FPM builds) ─────────────
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strtolower($name) === 'authorization') {
                    return $value;
                }
            }
        }

        // ── Strategy 3: X-Authorization header ──────────────────────────────────
        // Apache passes custom headers to PHP even in cgi-fcgi mode.
        // External clients on cPanel hosts should use this header instead.
        $xAuth = $request->headers->get('X-Authorization')
            ?? ($_SERVER['HTTP_X_AUTHORIZATION'] ?? null);

        if ($xAuth) {
            // Accept both "Bearer TOKEN" and plain "TOKEN" formats
            return str_starts_with($xAuth, 'Bearer ') ? $xAuth : 'Bearer ' . $xAuth;
        }

        // ── Strategy 4: X-Api-Token header ──────────────────────────────────────
        $xToken = $request->headers->get('X-Api-Token')
            ?? ($_SERVER['HTTP_X_API_TOKEN'] ?? null);

        if ($xToken) {
            return 'Bearer ' . ltrim(str_replace('Bearer', '', $xToken));
        }

        return null;
    }
}
