<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * In Apache + PHP-FPM (common on cPanel), the Authorization header is stripped
 * before it reaches PHP. This middleware recovers it from multiple possible
 * locations in $_SERVER so Sanctum can read the Bearer token.
 */
class ForwardAuthorizationHeader
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->headers->has('Authorization')) {
            $auth = null;

            // PHP-FPM: Apache mod_rewrite sets this via [E=HTTP_AUTHORIZATION:...]
            // After the final RewriteRule to index.php, it may get REDIRECT_ prefix
            if (! empty($_SERVER['HTTP_AUTHORIZATION'])) {
                $auth = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (! empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (function_exists('getallheaders')) {
                // mod_php fallback
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
