<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * In Apache + PHP-FPM (common on cPanel), the Authorization header is stripped
 * before it reaches PHP. This middleware recovers it from multiple possible
 * locations in $_SERVER so Sanctum can read the Bearer token.
 *
 * Sources checked (in priority order):
 *  1. $_SERVER['HTTP_AUTHORIZATION']          — standard; set by Apache mod_setenvif
 *  2. $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] — set by mod_rewrite [E=...] after one internal redirect
 *  3. $_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION'] — double-redirect (root .htaccess → public/)
 *  4. getallheaders()                         — PHP built-in; works for mod_php / LiteSpeed SAPI
 *  5. HTTP_AUTHORIZATION from getallheaders() — case-insensitive scan of all headers
 */
class ForwardAuthorizationHeader
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->headers->has('Authorization')) {
            $auth = null;

            // PHP-FPM: Apache mod_setenvif sets this directly (no REDIRECT_ prefix).
            // Also set by mod_rewrite [E=HTTP_AUTHORIZATION:...] in root .htaccess.
            if (! empty($_SERVER['HTTP_AUTHORIZATION'])) {
                $auth = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (! empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                // After one internal mod_rewrite redirect (public/.htaccess → index.php)
                $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (! empty($_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION'])) {
                // After two internal mod_rewrite redirects (root .htaccess + public/.htaccess)
                $auth = $_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (function_exists('getallheaders')) {
                // mod_php / LiteSpeed SAPI fallback — returns original request headers
                $headers = getallheaders();
                $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            }

            if ($auth) {
                $request->headers->set('Authorization', $auth);
            }
        }

        return $next($request);
    }
}
