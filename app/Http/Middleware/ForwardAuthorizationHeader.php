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
            $auth = $this->resolveAuthHeader();

            if ($auth) {
                $request->headers->set('Authorization', $auth);
            }
        }

        return $next($request);
    }

    private function resolveAuthHeader(): ?string
    {
        // Check common $_SERVER locations in priority order.
        // Apache + PHP-FPM (cPanel) may strip the header; mod_rewrite rescues it
        // with the HTTP_AUTHORIZATION env var. After the final internal redirect to
        // index.php the var gains a REDIRECT_ prefix (or even double-REDIRECT_).
        $candidates = [
            'HTTP_AUTHORIZATION',
            'REDIRECT_HTTP_AUTHORIZATION',
            'REDIRECT_REDIRECT_HTTP_AUTHORIZATION',  // double-redirect edge case
            'HTTP_HTTP_AUTHORIZATION',               // some Nginx/FastCGI setups
        ];

        foreach ($candidates as $key) {
            if (! empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        // getallheaders() works on mod_php and some PHP-FPM setups
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $name => $value) {
                if (strtolower($name) === 'authorization') {
                    return $value;
                }
            }
        }

        return null;
    }
}
